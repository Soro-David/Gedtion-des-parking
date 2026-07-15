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
        // Augmenter la limite de temps d'exécution PHP pour éviter les coupures sur les requêtes lentes ou les tentatives multiples
        if (!ini_get('safe_mode')) {
            @set_time_limit(120);
        }

        $driver = config('services.ocr.driver', 'gemini');

        Log::info('[LicensePlateOcr] Début de l\'extraction', [
            'driver'        => $driver,
            'mime_type'     => $mimeType,
            'base64_length' => strlen($base64),
        ]);

        if ($driver === 'claude' || $driver === 'anthropic') {
            return $this->extractUsingClaude($base64, $mimeType);
        }

        return $this->extractUsingGemini($base64, $mimeType);
    }

    /**
     * Effectue la reconnaissance de plaque en utilisant le modèle Claude de Anthropic.
     */
    private function extractUsingClaude(string $base64, string $mimeType): ?string
    {
        $apiKey = config('services.anthropic.key', '');
        $model  = config('services.anthropic.model', 'claude-3-5-sonnet-latest');

        if (empty($apiKey)) {
            Log::error('[LicensePlateOcr] Clé API Anthropic non configurée (ANTHROPIC_API_KEY manquante).');
            return null;
        }

        $maxAttempts = 3;
        $retryDelay = 1000; // ms
        $response = null;

        $prompt = "Vous êtes un système expert de lecture de plaques d'immatriculation.\n\n"
            . "Votre mission est d'analyser l'image et d'extraire le numéro d'immatriculation exactement tel qu'il apparaît sur la plaque.\n\n"
            . "Règles importantes :\n\n"
            . "* Localisez la plaque d'immatriculation du véhicule.\n"
            . "* Lisez attentivement tous les caractères visibles.\n"
            . "* Respectez fidèlement les espaces, tirets et séparateurs présents sur la plaque.\n"
            . "* Ne modifiez pas le format de l'immatriculation.\n"
            . "* Ne corrigez pas automatiquement les caractères.\n"
            . "* Ne reformatez pas le texte.\n"
            . "* N'ajoutez aucun caractère non visible.\n"
            . "* N'inventez jamais de caractères manquants.\n"
            . "* Ignorez les logos, marques, autocollants, panneaux et tout autre texte présent dans l'image.\n"
            . "* Si plusieurs véhicules sont visibles, utilisez la plaque la plus proche et la plus lisible.\n"
            . "* Si la plaque est partiellement masquée, floue ou illisible, retournez NOT_FOUND.\n\n"
            . "Réponse obligatoire :\n\n"
            . "Retournez uniquement :\n\n"
            . "<plate>IMMATRICULATION_EXACTE</plate>\n\n"
            . "Exemple :\n\n"
            . "Si la plaque affiche :\n\n"
            . "1234 AB 01\n\n"
            . "Retournez :\n\n"
            . "<plate>1234 AB 01</plate>\n\n"
            . "Si la plaque affiche :\n\n"
            . "AB-123-CD\n\n"
            . "Retournez :\n\n"
            . "<plate>AB-123-CD</plate>\n\n"
            . "Si aucune plaque n'est lisible :\n\n"
            . "<plate>NOT_FOUND</plate>\n\n"
            . "Ne fournissez aucune explication, aucun commentaire et aucun texte supplémentaire.";

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'x-api-key'         => $apiKey,
                        'anthropic-version' => '2023-06-01',
                        'content-type'      => 'application/json',
                    ])
                    ->post('https://api.anthropic.com/v1/messages', [
                        'model'       => $model,
                        'max_tokens'  => 100,
                        'temperature' => 0,
                        'messages'    => [
                            [
                                'role'    => 'user',
                                'content' => [
                                    [
                                        'type'   => 'image',
                                        'source' => [
                                            'type'       => 'base64',
                                            'media_type' => $mimeType,
                                            'data'       => $base64,
                                        ],
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $prompt,
                                    ],
                                ],
                            ],
                        ],
                    ]);

                if ($response->successful()) {
                    break;
                }

                // Gérer les erreurs temporaires (429 rate limit, 529 overload, 503 service unavailable)
                if (in_array($response->status(), [429, 503, 529]) && $attempt < $maxAttempts) {
                    Log::warning("[LicensePlateOcr] Appel Claude retourné {$response->status()}. Tentative {$attempt}/{$maxAttempts}...");
                    usleep($retryDelay * 1000);
                    $retryDelay *= 2;
                    continue;
                }

                Log::error('[LicensePlateOcr] Échec de la requête Claude', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;

            } catch (\Throwable $e) {
                if ($attempt < $maxAttempts) {
                    Log::warning("[LicensePlateOcr] Exception lors de l'appel Claude (Tentative {$attempt}/{$maxAttempts}) : " . $e->getMessage());
                    usleep($retryDelay * 1000);
                    $retryDelay *= 2;
                    continue;
                }
                Log::error('[LicensePlateOcr] Exception finale lors de l\'appel Claude', [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
                return null;
            }
        }

        if (! $response || ! $response->successful()) {
            return null;
        }

        $raw = trim((string) $response->json('content.0.text', ''));

        Log::info('[LicensePlateOcr] Appel Claude réussi', [
            'raw_response' => $raw,
        ]);

        if ($raw === '') {
            return null;
        }

        $cleaned = $this->extractBestPlateCandidate($raw);

        if ($cleaned === null) {
            return null;
        }
        
        Log::info('[LicensePlateOcr] Plaque extraite avec succès (Claude)', [
            'cleaned_plate' => $cleaned,
        ]);

        return $cleaned;
    }

    /**
     * Effectue la reconnaissance de plaque en utilisant le modèle Gemini de Google.
     */
    private function extractUsingGemini(string $base64, string $mimeType): ?string
    {
        $apiKey = $this->apiKey;
        $model  = $this->model;

        if (empty($apiKey)) {
            Log::error('[LicensePlateOcr] Clé API Gemini non configurée (GEMINI_API_KEY manquante).');
            return null;
        }

        // Chemin vers le bundle CA (résout cURL error 60 en dev Windows)
        $caBundle = config('services.gemini.ca_bundle', env('CURL_CA_BUNDLE', ''));

        $maxAttempts = 3;
        $retryDelay = 1000; // ms
        $response = null;

        $prompt = "Vous êtes un système expert de lecture de plaques d'immatriculation.\n\n"
            . "Votre mission est d'analyser l'image et d'extraire le numéro d'immatriculation exactement tel qu'il apparaît sur la plaque.\n\n"
            . "Règles importantes :\n\n"
            . "* Localisez la plaque d'immatriculation du véhicule.\n"
            . "* Lisez attentivement tous les caractères visibles.\n"
            . "* Respectez fidèlement les espaces, tirets et séparateurs présents sur la plaque.\n"
            . "* Ne modifiez pas le format de l'immatriculation.\n"
            . "* Ne corrigez pas automatiquement les caractères.\n"
            . "* Ne reformatez pas le texte.\n"
            . "* N'ajoutez aucun caractère non visible.\n"
            . "* N'inventez jamais de caractères manquants.\n"
            . "* Ignorez les logos, marques, autocollants, panneaux et tout autre texte présent dans l'image.\n"
            . "* Si plusieurs véhicules sont visibles, utilisez la plaque la plus proche et la plus lisible.\n"
            . "* Si la plaque est partiellement masquée, floue ou illisible, retournez NOT_FOUND.\n\n"
            . "Réponse obligatoire :\n\n"
            . "Retournez uniquement :\n\n"
            . "<plate>IMMATRICULATION_EXACTE</plate>\n\n"
            . "Exemple :\n\n"
            . "Si la plaque affiche :\n\n"
            . "1234 AB 01\n\n"
            . "Retournez :\n\n"
            . "<plate>1234 AB 01</plate>\n\n"
            . "Si la plaque affiche :\n\n"
            . "AB-123-CD\n\n"
            . "Retournez :\n\n"
            . "<plate>AB-123-CD</plate>\n\n"
            . "Si aucune plaque n'est lisible :\n\n"
            . "<plate>NOT_FOUND</plate>\n\n"
            . "Ne fournissez aucune explication, aucun commentaire et aucun texte supplémentaire.";

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

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
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 100,
                        'temperature'     => 0,
                    ],
                ]);

                if ($response->successful()) {
                    break;
                }

                // Gérer les erreurs temporaires (429 rate limit, 503 service unavailable)
                if (in_array($response->status(), [429, 503]) && $attempt < $maxAttempts) {
                    Log::warning("[LicensePlateOcr] Appel Gemini retourné {$response->status()}. Tentative {$attempt}/{$maxAttempts} avec modèle {$model}...");
                    usleep($retryDelay * 1000);
                    $retryDelay *= 2;
                    continue;
                }

                Log::error('[LicensePlateOcr] Échec de la requête Gemini', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'model'  => $model,
                ]);
                return null;

            } catch (\Throwable $e) {
                if ($attempt < $maxAttempts) {
                    Log::warning("[LicensePlateOcr] Exception lors de l'appel Gemini (Tentative {$attempt}/{$maxAttempts}) : " . $e->getMessage());
                    usleep($retryDelay * 1000);
                    $retryDelay *= 2;
                    continue;
                }
                Log::error('[LicensePlateOcr] Exception finale lors de l\'appel Gemini', [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
                return null;
            }
        }

        if (! $response || ! $response->successful()) {
            return null;
        }

        try {
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
            
            Log::info('[LicensePlateOcr] Plaque extraite avec succès (Gemini)', [
                'cleaned_plate' => $cleaned,
            ]);

            return $cleaned;
        } catch (\Throwable $e) {
            Log::error('[LicensePlateOcr] Exception lors de l\'analyse de la réponse Gemini', [
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

        // 1. Essayer d'extraire depuis la balise XML complète <plate>...</plate>
        if (preg_match('/<PLATE>(.*?)<\/PLATE>/s', $upper, $matches)) {
            $candidate = trim($matches[1]);
            if ($candidate === 'NOT_FOUND') {
                return null;
            }
            // Conserver uniquement les lettres, chiffres, espaces et tirets
            $cleaned = preg_replace('/[^A-Z0-9\s\-]/', '', $candidate);
            $cleaned = trim($cleaned);
            return strlen(preg_replace('/[^A-Z0-9]/', '', $cleaned)) >= 4 ? $cleaned : null;
        }

        // 2. Gérer le cas où la balise de fermeture est absente suite à une coupure (ex: <PLATE>AB-123-CD)
        if (preg_match('/<PLATE>(.*?)$/s', $upper, $matches)) {
            $candidate = trim($matches[1]);
            if ($candidate === 'NOT_FOUND') {
                return null;
            }
            $cleaned = preg_replace('/[^A-Z0-9\s\-]/', '', $candidate);
            $cleaned = trim($cleaned);
            return strlen(preg_replace('/[^A-Z0-9]/', '', $cleaned)) >= 4 ? $cleaned : null;
        }

        // 3. Repli (Fallback) si la balise <plate> n'est pas présente
        if (preg_match('/\bNOT_FOUND\b/', $upper)) {
            return null;
        }

        // Nettoyer les balises ou mentions de balises pour éviter d'extraire le mot "PLATE"
        $fallbackSource = str_replace(['<PLATE>', '</PLATE>', '<PLATE/', 'PLATE'], '', $upper);

        preg_match_all('/[A-Z0-9]+(?:[\-\.][A-Z0-9]+)+|[0-9]{3,4}\s+[A-Z]{1,3}\s+[0-9]{1,2}|[A-Z0-9]{4,}/', $fallbackSource, $matches);
        $candidates = $matches[0] ?? [];

        $normalizedCandidates = collect($candidates)
            ->map(fn ($candidate) => trim(preg_replace('/[^A-Z0-9\s\-]/', '', (string) $candidate)))
            ->filter(fn ($candidate) => is_string($candidate) && strlen(preg_replace('/[^A-Z0-9]/', '', $candidate)) >= 4)
            ->values();

        if ($normalizedCandidates->isNotEmpty()) {
            return $normalizedCandidates
                ->sortByDesc(fn ($candidate) => strlen($candidate))
                ->first();
        }

        $fallback = trim(preg_replace('/[^A-Z0-9\s\-]/', '', $fallbackSource));

        return strlen(preg_replace('/[^A-Z0-9]/', '', $fallback)) >= 4 ? $fallback : null;
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
