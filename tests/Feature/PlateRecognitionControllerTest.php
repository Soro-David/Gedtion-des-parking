<?php

use App\Models\User;
use App\Services\Contracts\OcrProviderInterface;
use Illuminate\Http\UploadedFile;
use Mockery\MockInterface;

test('unauthenticated users cannot call the recognize API', function () {
    $this->postJson('/api/attendant/ocr/license-plate', [
        'image' => UploadedFile::fake()->image('vehicle.jpg'),
    ])->assertStatus(401);

    $this->postJson('/api/caissier/ocr/license-plate', [
        'image' => UploadedFile::fake()->image('vehicle.jpg'),
    ])->assertStatus(401);
});

test('authenticated users with attendant role can detect plate successfully', function () {
    $user = User::factory()->create(['role' => 'attendant']);

    // Mocker le contrat OcrProviderInterface
    $this->mock(OcrProviderInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('extract')
            ->once()
            ->andReturn('AA-123-BB');
    });

    $response = $this->actingAs($user)
        ->postJson('/api/attendant/ocr/license-plate', [
            'image' => UploadedFile::fake()->image('plate.jpg'),
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Plaque d\'immatriculation détectée avec succès.',
            'plate' => 'AA-123-BB',
        ]);
});

test('authenticated users with caissier role can detect plate successfully', function () {
    $user = User::factory()->create(['role' => 'caissier']);

    // Mocker le contrat OcrProviderInterface
    $this->mock(OcrProviderInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('extract')
            ->once()
            ->andReturn('XY-987-ZT');
    });

    $response = $this->actingAs($user)
        ->postJson('/api/caissier/ocr/license-plate', [
            'image' => UploadedFile::fake()->image('plate.jpg'),
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Plaque d\'immatriculation détectée avec succès.',
            'plate' => 'XY-987-ZT',
        ]);
});

test('users with unauthorized roles cannot call the recognize API', function () {
    $supervisor = User::factory()->create(['role' => 'supervisor']);
    $attendant = User::factory()->create(['role' => 'attendant']);
    $caissier = User::factory()->create(['role' => 'caissier']);

    // Un superviseur ne peut appeler aucun des deux
    $this->actingAs($supervisor)
        ->postJson('/api/attendant/ocr/license-plate', ['image' => UploadedFile::fake()->image('plate.jpg')])
        ->assertStatus(403);

    $this->actingAs($supervisor)
        ->postJson('/api/caissier/ocr/license-plate', ['image' => UploadedFile::fake()->image('plate.jpg')])
        ->assertStatus(403);

    // Un agent/attendant ne peut pas appeler la route caissier
    $this->actingAs($attendant)
        ->postJson('/api/caissier/ocr/license-plate', ['image' => UploadedFile::fake()->image('plate.jpg')])
        ->assertStatus(403);

    // Un caissier ne peut pas appeler la route attendant
    $this->actingAs($caissier)
        ->postJson('/api/attendant/ocr/license-plate', ['image' => UploadedFile::fake()->image('plate.jpg')])
        ->assertStatus(403);
});

test('it returns 422 if no plate is detected', function () {
    $user = User::factory()->create(['role' => 'attendant']);

    $this->mock(OcrProviderInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('extract')
            ->once()
            ->andReturn(null);
    });

    $response = $this->actingAs($user)
        ->postJson('/api/attendant/ocr/license-plate', [
            'image' => UploadedFile::fake()->image('no_plate.jpg'),
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Aucune plaque d\'immatriculation valide n\'a pu être détectée sur l\'image fournie.',
            'plate' => null,
        ]);
});

test('it validates that the uploaded file is an image', function () {
    $user = User::factory()->create(['role' => 'attendant']);

    $response = $this->actingAs($user)
        ->postJson('/api/attendant/ocr/license-plate', [
            'image' => UploadedFile::fake()->create('document.pdf', 500, 'application/pdf'),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});
