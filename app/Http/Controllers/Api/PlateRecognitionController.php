<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contracts\OcrProviderInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Contrôleur gérant la reconnaissance de plaques d'immatriculation.
 */
class PlateRecognitionController extends Controller
{
    private OcrProviderInterface $ocrProvider;

    /**
     * Injection de dépendance via le constructeur.
     * Laravel résoudra l'interface OcrProviderInterface vers l'implémentation liée dans AppServiceProvider.
     */
    public function __construct(OcrProviderInterface $ocrProvider)
    {
        $this->ocrProvider = $ocrProvider;
    }

    /**
     * Traite l'image du véhicule et retourne la plaque d'immatriculation détectée.
     */
    public function recognize(Request $request): JsonResponse
    {
        // 1. Validation de l'image (doit être un fichier image valide, max 10 Mo)
        $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:10240', // 10 Mo maximum
            ],
        ]);

        try {
            // 2. Appel au service OCR via l'interface
            $plate = $this->ocrProvider->extract($request->file('image'));

            // 3. Si aucune plaque n'est détectée, retourner une erreur 422 claire et explicite
            if (! $plate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune plaque d\'immatriculation valide n\'a pu être détectée sur l\'image fournie.',
                    'plate' => null,
                ], 422);
            }

            // 4. Retourner la réponse JSON standardisée en cas de succès
            return response()->json([
                'success' => true,
                'message' => 'Plaque d\'immatriculation détectée avec succès.',
                'plate' => $plate,
            ], 200);

        } catch (\Throwable $e) {
            // 5. Journalisation des exceptions inattendues dans les logs Laravel
            Log::error('[PlateRecognitionController] Erreur lors de la reconnaissance de la plaque.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur interne est survenue lors de l\'analyse de l\'image. Veuillez réessayer.',
                'plate' => null,
            ], 500);
        }
    }
}
