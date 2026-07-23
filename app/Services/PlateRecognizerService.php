<?php

namespace App\Services;

use App\Services\Contracts\OcrProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service de reconnaissance de plaque d'immatriculation via Plate Recognizer API.
 */
class PlateRecognizerService implements OcrProviderInterface
{
    /**
     * Clé de configuration / Token API de Plate Recognizer.
     */
    private string $token;

    /**
     * URL de l'API Plate Recognizer.
     */
    private string $url;

    /**
     * Timeout pour les requêtes réseau (en secondes).
     */
    private int $timeout;

    /**
     * Constructeur du service.
     */
    public function __construct()
    {
        $this->token = config('services.platerecognizer.token', '');
        $this->url = config('services.platerecognizer.url', 'https://api.platerecognizer.com/v1/plate-reader/');
        $this->timeout = (int) config('services.platerecognizer.timeout', 15);
    }

    /**
     * Extrait le numéro d'immatriculation avec le score de confiance le plus élevé.
     *
     * @return string|null La plaque détectée en majuscules, ou null.
     */
    public function extract(UploadedFile|string $image): ?string
    {
        // Vérification de la configuration du Token
        if (empty($this->token)) {
            Log::error('[PlateRecognizerService] Token API de Plate Recognizer non configuré.');

            return null;
        }

        try {
            // Déterminer le chemin du fichier image
            $filePath = $image instanceof UploadedFile ? $image->getRealPath() : $image;

            if (! file_exists($filePath)) {
                Log::error('[PlateRecognizerService] Le fichier image est introuvable.', [
                    'path' => $filePath,
                ]);

                return null;
            }

            $fileContents = file_get_contents($filePath);
            $fileName = $image instanceof UploadedFile ? $image->getClientOriginalName() : basename($filePath);

            Log::info('[PlateRecognizerService] Envoi de l\'image à Plate Recognizer...', [
                'file_name' => $fileName,
                'file_size' => strlen($fileContents).' octets',
            ]);

            // Envoi de la requête multipart/form-data via Http Facade
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Token '.$this->token,
                ])
                ->attach('upload', $fileContents, $fileName)
                ->post($this->url);

            // Vérification de l'échec de la requête
            if ($response->failed()) {
                Log::error('[PlateRecognizerService] Échec de la requête vers Plate Recognizer.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            // Vérification si des plaques ont été détectées
            if (! isset($data['results']) || empty($data['results'])) {
                Log::info('[PlateRecognizerService] Aucune plaque d\'immatriculation détectée sur l\'image.', [
                    'response' => $data,
                ]);

                return null;
            }

            // Si plusieurs plaques sont détectées, trier par score décroissant pour obtenir la plus fiable
            $bestResult = collect($data['results'])
                ->sortByDesc('score')
                ->first();

            if (! $bestResult || empty($bestResult['plate'])) {
                Log::warning('[PlateRecognizerService] Structure de résultat invalide.', [
                    'results' => $data['results'],
                ]);

                return null;
            }

            // Normalisation de la plaque
            $plate = strtoupper(trim($bestResult['plate']));

            Log::info('[PlateRecognizerService] Détection réussie.', [
                'plate' => $plate,
                'score' => $bestResult['score'] ?? null,
                'region' => $bestResult['region']['code'] ?? null,
                'candidates' => count($data['results']),
            ]);

            return $plate;

        } catch (ConnectionException $e) {
            Log::error('[PlateRecognizerService] Erreur de connexion ou timeout réseau avec Plate Recognizer.', [
                'message' => $e->getMessage(),
                'timeout' => $this->timeout,
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('[PlateRecognizerService] Exception levée lors de la reconnaissance de plaque.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}
