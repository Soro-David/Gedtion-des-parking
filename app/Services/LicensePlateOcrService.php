<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service réutilisable de reconnaissance de plaque d'immatriculation
 * via l'API Vision de Google Gemini (AI Studio).
 *
 * Usage depuis n'importe quel contrôleur :
 *   app(LicensePlateOcrService::class)->extractFromBase64($base64, $mime);
 *   app(LicensePlateOcrService::class)->extractFromPath($absolutePath);
 */
class LicensePlateOcrService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
        $this->model  = config('services.gemini.model', 'gemini-2.5-flash');
    }

    /**
     * Extrait la plaque d'immatriculation depuis une image encodée en base64.
     *
     * @param  string  $base64   Contenu de l'image encodé en base64.
     * @param  string  $mimeType MIME type de l'image (image/jpeg, image/png…).
     * @return string|null       La plaque en majuscules, ou null si non détectée.
     */
    public function extractFromBase64(string $base64, string $mimeType = 'image/jpeg'): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('[LicensePlateOcr] Clé API Gemini non configurée (GEMINI_API_KEY manquante).');
            return null;
        }

        $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->apiKey}";

        // Chemin vers le bundle CA (résout cURL error 60 en dev Windows)
        $caBundle = config('services.gemini.ca_bundle', env('CURL_CA_BUNDLE', ''));

        try {
            $client = Http::timeout(30);

            if ($caBundle !== '' && file_exists($caBundle)) {
                $client = $client->withOptions(['verify' => $caBundle]);
            } elseif ($caBundle === 'false' || config('app.env') === 'local') {
                // En dev local sans certificat configuré, désactiver la vérification SSL
                $client = $client->withOptions(['verify' => false]);
            }

            $response = $client->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data'      => $base64,
                                ],
                            ],
                            [
                                'text' => 'Extract the vehicle license plate number from this image. '
                                    . 'Return ONLY the plate number in uppercase, without spaces, dashes or any explanation. '
                                    . 'If no plate is visible or readable, return exactly: NOT_FOUND',
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 50,
                    'temperature'     => 0,
                ],
            ]);

            if (! $response->successful()) {
                Log::error('[LicensePlateOcr] Échec de la requête Gemini', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $parts = $response->json('candidates.0.content.parts', []);
            $raw = trim(collect($parts)
                ->pluck('text')
                ->filter(fn ($text) => is_string($text) && trim($text) !== '')
                ->implode(' '));

            if ($raw === '') {
                $raw = trim((string) ($response->json('candidates.0.content.parts.0.text') ?? ''));
            }

            Log::info('[LicensePlateOcr] Appel Gemini réussi', [
                'raw_response' => $raw,
            ]);

            if ($raw === '') {
                return null;
            }

            $cleaned = $this->extractBestPlateCandidate($raw);

            if ($cleaned === null) {
                return null;
            }
            
            Log::info('[LicensePlateOcr] Plaque extraite avec succès', [
                'cleaned_plate' => $cleaned,
            ]);

            return $cleaned;
        } catch (\Throwable $e) {
            Log::error('[LicensePlateOcr] Exception lors de l\'appel Gemini', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Extrait la meilleure candidate de plaque à partir du texte brut retourné par le modèle.
     */
    private function extractBestPlateCandidate(string $raw): ?string
    {
        $upper = strtoupper($raw);

        if (preg_match('/\bNOT_FOUND\b/', $upper)) {
            return null;
        }

        preg_match_all('/[A-Z0-9]+(?:[\s\-\.][A-Z0-9]+)*/', $upper, $matches);
        $candidates = $matches[0] ?? [];

        $normalizedCandidates = collect($candidates)
            ->map(fn ($candidate) => preg_replace('/[^A-Z0-9]+/', '', (string) $candidate))
            ->filter(fn ($candidate) => is_string($candidate) && strlen($candidate) >= 4)
            ->values();

        if ($normalizedCandidates->isNotEmpty()) {
            return $normalizedCandidates
                ->sortByDesc(fn ($candidate) => strlen($candidate))
                ->first();
        }

        $fallback = preg_replace('/[^A-Z0-9]+/', '', $upper);

        return $fallback !== '' ? $fallback : null;
    }

    /**
     * Extrait la plaque depuis un chemin absolu vers un fichier image.
     *
     * @param  string  $absolutePath Chemin absolu vers le fichier image.
     * @return string|null
     */
    public function extractFromPath(string $absolutePath): ?string
    {
        if (! file_exists($absolutePath)) {
            Log::warning('[LicensePlateOcr] Fichier introuvable : ' . $absolutePath);
            return null;
        }

        $mimeType = mime_content_type($absolutePath) ?: 'image/jpeg';
        $base64   = base64_encode(file_get_contents($absolutePath));

        return $this->extractFromBase64($base64, $mimeType);
    }

    /**
     * Extrait la plaque depuis un UploadedFile Laravel.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string|null
     */
    public function extractFromUploadedFile(\Illuminate\Http\UploadedFile $file): ?string
    {
        $base64   = base64_encode(file_get_contents($file->getRealPath()));
        $mimeType = $file->getMimeType() ?: 'image/jpeg';

        return $this->extractFromBase64($base64, $mimeType);
    }
}
