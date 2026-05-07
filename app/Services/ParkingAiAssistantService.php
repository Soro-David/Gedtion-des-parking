<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service d'assistant IA parking basé sur Gemini.
 * Répond aux questions sur les parkings disponibles, tarifs, itinéraires, etc.
 */
class ParkingAiAssistantService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
        $this->model  = config('services.gemini.model', 'gemini-1.5-flash');
    }

    /**
     * Répond à une question utilisateur en tenant compte du contexte des parkings.
     *
     * @param  string  $question       Question posée par l'utilisateur.
     * @param  array   $parkingsData   Liste des parkings (nom, adresse, capacity, available...).
     * @param  array   $history        Historique de la conversation [[role, text], ...].
     * @return string  Réponse de l'IA.
     */
    public function ask(string $question, array $parkingsData = [], array $history = []): string
    {
        if (empty($this->apiKey)) {
            return 'Clé API Gemini non configurée. Veuillez vérifier GEMINI_API_KEY dans le fichier .env.';
        }

        // Construire le contexte système avec les données parkings
        $parkingContext = '';
        if (!empty($parkingsData)) {
            $parkingContext = "\n\n=== DONNÉES DES PARKINGS DISPONIBLES ===\n";
            foreach ($parkingsData as $p) {
                $available = max(0, ($p['capacity'] ?? 0) - ($p['active_sessions_count'] ?? 0));
                $status    = ($p['is_active'] ?? false) ? 'Actif' : 'Inactif';
                $name      = $p['name'] ?? '';
                $reference = $p['reference'] ?? 'N/A';
                $address   = $p['address'] ?? '';
                $lat       = $p['latitude'] ?? '';
                $lng       = $p['longitude'] ?? '';
                $capacity  = $p['capacity'] ?? 0;
                $price     = $p['price'] ?? 0;
                $parkingContext .= "- **{$name}** (Réf: {$reference})\n";
                $parkingContext .= "  Adresse: {$address}\n";
                $parkingContext .= "  Coordonnées: lat={$lat}, lng={$lng}\n";
                $parkingContext .= "  Capacité: {$capacity} places | Disponibles: {$available} | Statut: {$status}\n";
                $parkingContext .= "  Tarif de base: {$price} FCFA\n\n";
            }
            $parkingContext .= "=========================================\n";
        }

        $systemPrompt = "Tu es ParkBot, l'assistant intelligent de l'application de gestion de parkings ParkApp.
Tu aides les utilisateurs (administrateurs, superviseurs, agents) à trouver des places disponibles,
comprendre les tarifs, localiser les parkings sur la carte, et obtenir des recommandations.
Réponds toujours en français, de manière concise et précise.
Si l'utilisateur demande un parking, propose le plus adapté selon les données disponibles.
Si tu ne sais pas, dis-le honnêtement.{$parkingContext}";

        // Construire les messages de l'historique
        $contents = [];

        // Ajouter le système en tant que premier message user/model
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $systemPrompt]],
        ];
        $contents[] = [
            'role' => 'model',
            'parts' => [['text' => "Bonjour ! Je suis ParkBot, votre assistant parking. Comment puis-je vous aider ?"]],
        ];

        // Historique
        foreach ($history as $msg) {
            $contents[] = [
                'role'  => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['text']]],
            ];
        }

        // Question courante
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $question]],
        ];

        $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->apiKey}";

        $caBundle = config('services.gemini.ca_bundle', '');

        try {
            $client = Http::timeout(30);

            if ($caBundle !== '' && file_exists($caBundle)) {
                $client = $client->withOptions(['verify' => $caBundle]);
            } else {
                $client = $client->withOptions(['verify' => false]);
            }

            $response = $client->post($url, [
                'contents'         => $contents,
                'generationConfig' => [
                    'temperature'     => 0.7,
                    'maxOutputTokens' => 800,
                ],
            ]);

            if (!$response->successful()) {
                Log::error('[ParkingAI] Erreur API Gemini', ['status' => $response->status(), 'body' => $response->body()]);
                return 'Désolé, une erreur est survenue lors de la communication avec l\'IA. Veuillez réessayer.';
            }

            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text']
                ?? 'Désolé, je n\'ai pas pu générer une réponse. Veuillez reformuler votre question.';

        } catch (\Throwable $e) {
            Log::error('[ParkingAI] Exception', ['message' => $e->getMessage()]);
            return 'Une erreur technique est survenue. Veuillez réessayer.';
        }
    }
}
