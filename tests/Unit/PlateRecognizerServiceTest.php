<?php

use App\Services\PlateRecognizerService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('plate recognizer service extracts the plate with highest score', function () {
    Http::fake([
        'api.platerecognizer.com/*' => Http::response([
            'results' => [
                [
                    'plate' => 'ab123cd',
                    'score' => 0.75,
                ],
                [
                    'plate' => 'xy987zt',
                    'score' => 0.95,
                ],
                [
                    'plate' => 'pq456rs',
                    'score' => 0.50,
                ],
            ],
        ], 200),
    ]);

    // Créer un fichier temporaire pour simuler l'image
    $tempFile = tempnam(sys_get_temp_dir(), 'test_img');
    file_put_contents($tempFile, 'fake_image_content');

    config(['services.platerecognizer.token' => 'fake_token']);

    $service = new PlateRecognizerService;
    $result = $service->extract($tempFile);

    // Supprimer le fichier temporaire
    @unlink($tempFile);

    // Devrait retourner la plaque avec le score le plus élevé en majuscules (XY987ZT)
    expect($result)->toBe('XY987ZT');
});

test('plate recognizer service returns null when no plates are detected', function () {
    Http::fake([
        'api.platerecognizer.com/*' => Http::response([
            'results' => [],
        ], 200),
    ]);

    $tempFile = tempnam(sys_get_temp_dir(), 'test_img');
    file_put_contents($tempFile, 'fake_image_content');

    config(['services.platerecognizer.token' => 'fake_token']);

    $service = new PlateRecognizerService;
    $result = $service->extract($tempFile);

    @unlink($tempFile);

    expect($result)->toBeNull();
});
