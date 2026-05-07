<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LicensePlateOcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicensePlateOcrController extends Controller
{
    public function __construct(private LicensePlateOcrService $ocrService) {}

    /**
     * Reçoit une image et retourne la plaque d'immatriculation détectée.
     *
     * POST /api/ocr/license-plate
     * Body (multipart/form-data): image (fichier, max 5 Mo)
     */
    public function extract(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:5120', // 5 Mo max
        ]);

        $plate = $this->ocrService->extractFromUploadedFile($request->file('image'));

        if (! $plate) {
            return response()->json([
                'plate'   => null,
                'message' => 'Aucune plaque lisible détectée sur cette image.',
            ], 422);
        }

        return response()->json(['plate' => $plate]);
    }
}
