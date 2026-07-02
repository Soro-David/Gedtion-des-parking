<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\User;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service de notifications FCM HTTP v1 avec Service Account.
 *
 * Pour l'activer :
 * 1. Télécharger le fichier JSON du compte de service Firebase depuis :
 *    Firebase Console → Paramètres → Comptes de service → Générer une nouvelle clé privée
 * 2. Placer le fichier dans : storage/app/firebase/service-account.json
 * 3. Ajouter storage/app/firebase/ dans .gitignore
 *
 * IMPORTANT : Ne jamais committer ce fichier dans le dépôt.
 */
class FcmNotificationService
{
    private const FCM_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const TOKEN_CACHE_KEY = 'fcm_oauth2_token';
    private const TOKEN_CACHE_TTL = 3500; // secondes (~58 min, token valide 1h)

    private ?string $projectId = null;

    public function __construct()
    {
        $this->projectId = $this->getProjectId();
    }

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Envoie une notification à un utilisateur spécifique via son token FCM.
     */
    public function sendToUser(
        User $user,
        string $title,
        string $body,
        array $data = [],
        ?string $type = null
    ): bool {
        $sender = auth()->user();
        if ($sender) {
            // L'expéditeur ne doit pas recevoir sa propre notification
            if ($user->id === $sender->id) {
                Log::debug("[FCM] Refus d'envoi : l'utilisateur #{$user->id} est l'expéditeur.");
                return false;
            }

            // Seuls les utilisateurs appartenant au même parking que l'expéditeur doivent recevoir la notification
            if (in_array($sender->role, [User::ROLE_ATTENDANT, User::ROLE_CAISSIER]) && 
                in_array($user->role, [User::ROLE_ATTENDANT, User::ROLE_CAISSIER])) {
                $senderParkingIds = $sender->parkings()->pluck('parkings.id')->toArray();
                $recipientParkingIds = $user->parkings()->pluck('parkings.id')->toArray();
                $commonParkings = array_intersect($senderParkingIds, $recipientParkingIds);
                if (empty($commonParkings)) {
                    Log::warning("[FCM] Refus d'envoi : le destinataire #{$user->id} ne partage pas de parking avec l'expéditeur #{$sender->id}.");
                    return false;
                }
            }
        }

        if (!$user->fcm_token) {
            Log::debug("[FCM] Pas de token FCM pour l'utilisateur #{$user->id}");
            return false;
        }

        $success = $this->sendToToken($user->fcm_token, $title, $body, $data);

        // Enregistrer dans l'historique quelle que soit la réussite
        $this->logNotification($user->id, $title, $body, $type ?? ($data['type'] ?? null), $data);

        return $success;
    }

    /**
     * Envoie une notification à plusieurs tokens FCM.
     *
     * @param array<string> $tokens
     */
    public function sendToMultiple(
        array $tokens,
        string $title,
        string $body,
        array $data = []
    ): int {
        $successCount = 0;
        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $title, $body, $data)) {
                $successCount++;
            }
        }
        return $successCount;
    }

    /**
     * Envoie une notification à tous les utilisateurs ayant un token FCM.
     *
     * @param array $userIds IDs des utilisateurs cibles (vide = tous)
     */
    public function sendToUsers(
        array $userIds,
        string $title,
        string $body,
        array $data = [],
        ?string $type = null
    ): int {
        $query = User::whereNotNull('fcm_token');
        if (!empty($userIds)) {
            $query->whereIn('id', $userIds);
        }
        $users = $query->get();

        $successCount = 0;
        foreach ($users as $user) {
            if ($this->sendToUser($user, $title, $body, $data, $type)) {
                $successCount++;
            }
        }
        return $successCount;
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    /**
     * Envoie une notification à un token FCM spécifique via FCM HTTP v1.
     */
    private function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = []
    ): bool {
        if (!$this->projectId) {
            Log::error('[FCM] projectId non configuré — vérifier storage/app/firebase/service-account.json');
            return false;
        }

        try {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return false;
            }

            // Convertir toutes les valeurs data en string (exigence FCM)
            $stringData = array_map('strval', $data);

            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'data' => $stringData,
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound'        => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'channel_id'   => 'parking_high_importance_v3',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1,
                            ],
                        ],
                    ],
                ],
            ];

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->when(app()->environment('local'), function ($http) {
                    return $http->withoutVerifying();
                })
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                    $payload
                );

            if ($response->successful()) {
                Log::debug('[FCM] Notification envoyée avec succès', [
                    'token_prefix' => substr($token, 0, 20),
                    'title' => $title,
                ]);
                return true;
            }

            // Si le token est invalide/expiré/malformé (400, 404, 410), l'effacer de la base
            if ($response->status() === 400 || $response->status() === 404 || $response->status() === 410) {
                User::where('fcm_token', $token)->update(['fcm_token' => null]);
                Log::info('[FCM] Token invalide/malformé supprimé de la base');
            }

            Log::warning('[FCM] Erreur envoi notification', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('[FCM] Exception lors de l\'envoi', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obtient un token OAuth2 valide (mis en cache pour éviter les requêtes répétées).
     */
    private function getAccessToken(): ?string
    {
        // Utiliser le cache pour éviter de régénérer le token à chaque requête
        return Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_CACHE_TTL, function () {
            $serviceAccountPath = storage_path('app/firebase/service-account.json');

            if (!file_exists($serviceAccountPath)) {
                Log::error('[FCM] Fichier service-account.json introuvable dans storage/app/firebase/');
                return null;
            }

            $credentials = new ServiceAccountCredentials(
                self::FCM_SCOPE,
                $serviceAccountPath
            );

            if (app()->environment('local')) {
                $guzzleClient = new \GuzzleHttp\Client(['verify' => false]);
                $httpHandler = \Google\Auth\HttpHandler\HttpHandlerFactory::build($guzzleClient);
                $token = $credentials->fetchAuthToken($httpHandler);
            } else {
                $token = $credentials->fetchAuthToken();
            }
            return $token['access_token'] ?? null;
        });
    }

    /**
     * Lit le project_id depuis le fichier service account.
     */
    private function getProjectId(): ?string
    {
        $serviceAccountPath = storage_path('app/firebase/service-account.json');

        if (!file_exists($serviceAccountPath)) {
            return null;
        }

        $content = json_decode(file_get_contents($serviceAccountPath), true);
        return $content['project_id'] ?? null;
    }

    /**
     * Enregistre une notification dans la table notification_logs.
     */
    private function logNotification(
        int $userId,
        string $title,
        string $body,
        ?string $type,
        array $data
    ): void {
        try {
            NotificationLog::create([
                'user_id' => $userId,
                'title'   => $title,
                'body'    => $body,
                'type'    => $type,
                'data'    => !empty($data) ? $data : null,
            ]);
        } catch (\Exception $e) {
            Log::warning('[FCM] Impossible d\'enregistrer la notification en base', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
