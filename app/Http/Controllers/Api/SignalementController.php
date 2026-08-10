<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Parking;
use App\Models\ParkingSession;
use App\Models\Signalement;
use App\Services\FcmNotificationService;
use App\Services\SmsNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SignalementController extends Controller
{
    /**
     * Enregistre un nouveau signalement de véhicule et notifie les collègues.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'parking_id'    => 'required|exists:parkings,id',
            'license_plate' => 'required|string|max:50',
            'motif'         => 'required|string|max:1000',
            'type'          => 'nullable|string|in:interne,proprietaire',
            'phone'         => 'required_if:type,proprietaire|nullable|string|max:20',
        ]);

        $user = auth()->user();

        // Vérifier que le parking est affecté à l'utilisateur connecté
        $assignedIds = $user->parkings()->pluck('parkings.id')->toArray();
        if (!in_array((int)$request->parking_id, $assignedIds)) {
            return response()->json([
                'message' => 'Ce parking ne vous est pas affecté.'
            ], 403);
        }

        $type = $request->input('type', 'interne');

        // Créer le signalement
        $signalement = Signalement::create([
            'user_id'       => $user->id,
            'parking_id'    => $request->parking_id,
            'license_plate' => ParkingSession::normalizeLicensePlate($request->license_plate),
            'motif'         => $request->motif,
            'type'          => $type,
            'phone'         => $request->phone,
            'sms_status'    => ($type === 'proprietaire') ? 'pending' : null,
        ]);

        $parking = Parking::findOrFail($request->parking_id);

        Log::info('Signalement enregistré', [
            'signalement_id' => $signalement->id,
            'user_id'         => $user->id,
            'parking_id'      => $parking->id,
            'license_plate'   => $signalement->license_plate,
            'type'            => $type,
        ]);

        if ($type === 'proprietaire') {
            // Préparer et envoyer/simuler le SMS au propriétaire
            try {
                app(SmsNotificationService::class)->dispatchOwnerSms($signalement);
            } catch (\Exception $e) {
                Log::error('[SMS Signalement] Erreur lors de la préparation/envoi du SMS', [
                    'error' => $e->getMessage(),
                    'signalement_id' => $signalement->id,
                ]);
            }
        } else {
            // Envoyer la notification FCM à tous les agents et caissiers du même parking (sauf le créateur)
            try {
                $recipients = $parking->users()
                    ->whereIn('role', ['attendant', 'caissier'])
                    ->where('users.id', '!=', $user->id)
                    ->pluck('users.id')
                    ->toArray();

                if (!empty($recipients)) {
                    app(FcmNotificationService::class)->sendToUsers(
                        $recipients,
                        'Nouveau signalement',
                        "Un véhicule a été signalé dans le parking {$parking->name} par {$user->name}.",
                        [
                            'type'       => 'rapport',
                            'id'         => (string) $signalement->id,
                            'parking_id' => (string) $parking->id,
                        ],
                        'rapport'
                    );
                }
            } catch (\Exception $e) {
                Log::warning('[FCM Signalement] Erreur envoi notification signalement', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Signalement enregistré avec succès.',
            'data'    => $signalement,
        ]);
    }

    /**
     * Recherche les véhicules ayant des sessions d'enregistrement dans ce parking (pour l'autocomplétion).
     */
    public function searchVehicles(Request $request): JsonResponse
    {
        $request->validate([
            'parking_id' => 'required|exists:parkings,id',
            'q'          => 'nullable|string',
        ]);

        $query = $request->input('q', '');
        $parkingId = $request->input('parking_id');

        $licensePlates = ParkingSession::where('parking_id', $parkingId)
            ->where('license_plate', 'LIKE', '%' . $query . '%')
            ->select('license_plate')
            ->distinct()
            ->limit(15)
            ->pluck('license_plate');

        return response()->json([
            'success' => true,
            'data'    => $licensePlates,
        ]);
    }

    /**
     * Récupère la liste des signalements des parkings de l'utilisateur.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $assignedIds = $user->parkings()->pluck('parkings.id')->toArray();

        $signalements = Signalement::with(['user', 'parking'])
            ->whereIn('parking_id', $assignedIds)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $signalements,
        ]);
    }

    /**
     * Récupère le détail d'un signalement.
     */
    public function show($id): JsonResponse
    {
        $user = auth()->user();
        $assignedIds = $user->parkings()->pluck('parkings.id')->toArray();

        $signalement = Signalement::with(['user', 'parking'])
            ->whereIn('parking_id', $assignedIds)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $signalement,
        ]);
    }

    /**
     * Récupère le numéro de téléphone et le nom du propriétaire d'un véhicule à partir de sa plaque.
     */
    public function getVehicleOwnerPhone(Request $request): JsonResponse
    {
        $request->validate([
            'license_plate' => 'required|string',
        ]);

        $licensePlate = ParkingSession::normalizeLicensePlate($request->license_plate);

        $driver = Driver::where('license_plate', $licensePlate)->with('user')->first();

        $phone = $driver?->user?->phone;
        $name = $driver?->user?->name;

        // Si aucun numéro n'est trouvé via Driver, on cherche dans les sessions de stationnement
        if (empty($phone)) {
            $session = ParkingSession::where('license_plate', $licensePlate)
                ->orderBy('started_at', 'desc')
                ->first();
            if ($session && !empty($session->phone)) {
                $phone = $session->phone;
                $name = 'Propriétaire'; // Libellé par défaut si on n'a que le numéro
            }
        }

        return response()->json([
            'success' => true,
            'phone'   => $phone,
            'name'    => $name,
        ]);
    }
}
