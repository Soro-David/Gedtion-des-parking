<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parking;
use App\Services\ParkingAiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParkingAiAssistantController extends Controller
{
    public function __construct(private ParkingAiAssistantService $aiService) {}

    /**
     * POST /api/parking-ai/ask
     * Body: { question: string, history: [{role, text}] }
     */
    public function ask(Request $request): JsonResponse
    {
        $request->validate([
            'question'      => ['required', 'string', 'max:500'],
            'history'       => ['sometimes', 'array', 'max:20'],
            'history.*.role' => ['required', 'in:user,assistant'],
            'history.*.text' => ['required', 'string', 'max:2000'],
        ]);

        // Charger les parkings avec leur occupation courante
        $parkings = Parking::withCount(['sessions as active_sessions_count' => function ($q) {
                $q->where('status', 'occupied');
            }])
            ->where('is_active', true)
            ->select(['id', 'reference', 'name', 'address', 'latitude', 'longitude', 'capacity', 'price', 'is_active'])
            ->get()
            ->toArray();

        $answer = $this->aiService->ask(
            $request->input('question'),
            $parkings,
            $request->input('history', []),
        );

        return response()->json(['answer' => $answer]);
    }
}
