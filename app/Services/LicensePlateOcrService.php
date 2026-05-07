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
        $this->model  = config('services.gemini.model', 'gemini-1.5-flash');
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

            $raw = trim($response->json('candidates.0.content.parts.0.text') ?? '');

            if ($raw === '' || strtoupper($raw) === 'NOT_FOUND') {
                return null;
            }

            // Nettoyage : supprime espaces, tirets, points éventuellement introduits
            return strtoupper(preg_replace('/[\s\-\.]+/', '', $raw));
        } catch (\Throwable $e) {
            Log::error('[LicensePlateOcr] Exception lors de l\'appel Gemini', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
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
