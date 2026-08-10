<?php

namespace Tests\Feature;

use App\Models\Parking;
use App\Models\ParkingSession;
use App\Models\User;
use App\Models\Driver;
use App\Models\Signalement;
use App\Services\FcmNotificationService;
use App\Services\Contracts\SmsProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SignalementRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock FCM notification service
        $this->mock(FcmNotificationService::class, function ($mock) {
            $mock->shouldReceive('sendToUsers')->zeroOrMoreTimes()->andReturn(true);
        });
    }

    public function test_can_create_internal_signalement()
    {
        $user = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $parking = Parking::create([
            'reference'  => 'PKG-TEST-' . rand(100, 999),
            'name'       => 'Test Parking',
            'address'    => 'Test Address',
            'capacity'   => 100,
            'price'      => 100.0,
            'is_active'  => true,
            'created_by' => $user->id,
        ]);
        $user->parkings()->attach($parking->id);

        $response = $this->actingAs($user)->postJson('/api/signalements', [
            'parking_id'    => $parking->id,
            'license_plate' => '1234-AB-01',
            'motif'         => 'Vehicle blocking gate.',
            'type'          => 'interne',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('signalements', [
            'license_plate' => '1234-AB-01',
            'motif'         => 'Vehicle blocking gate.',
            'type'          => 'interne',
        ]);
    }

    public function test_can_create_owner_signalement_and_trigger_sms()
    {
        $user = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $parking = Parking::create([
            'reference'  => 'PKG-TEST-' . rand(100, 999),
            'name'       => 'Test Parking',
            'address'    => 'Test Address',
            'capacity'   => 100,
            'price'      => 100.0,
            'is_active'  => true,
            'created_by' => $user->id,
        ]);
        $user->parkings()->attach($parking->id);

        $smsMock = $this->mock(SmsProviderInterface::class);
        $smsMock->shouldReceive('sendSms')
            ->once()
            ->with('+2250700000000', Mockery::type('string'))
            ->andReturn(true);

        $response = $this->actingAs($user)->postJson('/api/signalements', [
            'parking_id'    => $parking->id,
            'license_plate' => '1234-AB-01',
            'motif'         => 'Wrong parking spot.',
            'type'          => 'proprietaire',
            'phone'         => '+2250700000000',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('signalements', [
            'license_plate' => '1234-AB-01',
            'motif'         => 'Wrong parking spot.',
            'type'          => 'proprietaire',
            'phone'         => '+2250700000000',
            'sms_status'    => 'sent',
        ]);
    }

    public function test_can_get_vehicle_owner_phone()
    {
        $user = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $driverUser = User::factory()->create(['role' => User::ROLE_DRIVER, 'phone' => '+22501020304']);
        Driver::create([
            'user_id'       => $driverUser->id,
            'created_by'    => $user->id,
            'license_plate' => '1234-AB-01',
        ]);

        $response = $this->actingAs($user)->getJson('/api/signalements/owner-phone?license_plate=1234-AB-01');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'phone'   => '+22501020304',
                'name'    => $driverUser->name,
            ]);
    }

    public function test_license_plate_prefix_stripping()
    {
        $user = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $parking = Parking::create([
            'reference'  => 'PKG-TEST-' . rand(100, 999),
            'name'       => 'Test Parking',
            'address'    => 'Test Address',
            'capacity'   => 100,
            'price'      => 100.0,
            'is_active'  => true,
            'created_by' => $user->id,
        ]);
        $user->parkings()->attach($parking->id);

        // Test with "CI-" prefix
        $response = $this->actingAs($user)->postJson('/api/signalements', [
            'parking_id'    => $parking->id,
            'license_plate' => 'CI-1234-AB',
            'motif'         => 'Blocking space',
            'type'          => 'interne',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('signalements', [
            'license_plate' => '1234-AB',
        ]);

        // Test with "CI " prefix
        $response = $this->actingAs($user)->postJson('/api/signalements', [
            'parking_id'    => $parking->id,
            'license_plate' => 'CI 5678 CD',
            'motif'         => 'Blocking space',
            'type'          => 'interne',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('signalements', [
            'license_plate' => '5678 CD',
        ]);
    }

    public function test_can_get_vehicle_owner_phone_fallback_to_parking_session()
    {
        $user = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $parking = Parking::create([
            'reference'  => 'PKG-TEST-' . rand(100, 999),
            'name'       => 'Test Parking',
            'address'    => 'Test Address',
            'capacity'   => 100,
            'price'      => 100.0,
            'is_active'  => true,
            'created_by' => $user->id,
        ]);
        $user->parkings()->attach($parking->id);

        // Enregistrer une session avec un numéro de téléphone
        ParkingSession::create([
            'parking_id'    => $parking->id,
            'license_plate' => '5678-CD-02',
            'phone'         => '+22509090909',
            'status'        => 'occupied',
            'started_at'    => now(),
            'created_by'    => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/signalements/owner-phone?license_plate=5678-CD-02');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'phone'   => '+22509090909',
                'name'    => 'Propriétaire',
            ]);
    }

    public function test_agent_parking_session_store_saves_phone()
    {
        $user = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $parking = Parking::create([
            'reference'  => 'PKG-TEST-' . rand(100, 999),
            'name'       => 'Test Parking',
            'address'    => 'Test Address',
            'capacity'   => 100,
            'price'      => 100.0,
            'is_active'  => true,
            'created_by' => $user->id,
        ]);
        $user->parkings()->attach($parking->id);

        $response = $this->actingAs($user)->postJson('/api/attendant/parking-sessions', [
            'parking_id'    => $parking->id,
            'license_plate' => '1234-AB-99',
            'phone'         => '07070707',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('parking_sessions', [
            'license_plate' => '1234-AB-99',
            'phone'         => '07070707',
        ]);
    }
}
