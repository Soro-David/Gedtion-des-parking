<?php

use App\Models\User;
use App\Models\Parking;
use App\Models\ParkingAttendant;
use App\Models\Caissier;
use App\Models\ParkingSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('supervisor can see all attendants and cashiers of their parking lots in unified index', function () {
    // Create admin to create parkings
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    // Create supervisor
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

    // Create two active parkings
    $parkingA = Parking::create([
        'reference' => 'PKG-A',
        'name' => 'Parking A',
        'address' => '123 Main St',
        'capacity' => 100,
        'created_by' => $admin->id,
        'is_active' => true,
    ]);

    $parkingB = Parking::create([
        'reference' => 'PKG-B',
        'name' => 'Parking B',
        'address' => '456 Side St',
        'capacity' => 50,
        'created_by' => $admin->id,
        'is_active' => true,
    ]);

    // Assign supervisor to Parking A
    $supervisor->parkings()->attach($parkingA->id);

    // Create agent A (attendant) assigned to Parking A (should be visible because of parking)
    $agentA = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
    ParkingAttendant::create([
        'user_id' => $agentA->id,
        'created_by' => $admin->id,
    ]);
    $agentA->parkings()->attach($parkingA->id);

    // Create agent B (attendant) assigned to Parking B (should NOT be visible)
    $agentB = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
    ParkingAttendant::create([
        'user_id' => $agentB->id,
        'created_by' => $admin->id,
    ]);
    $agentB->parkings()->attach($parkingB->id);

    // Create agent C (attendant) created by supervisor but not assigned to any parking (should be visible because created by supervisor)
    $agentC = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
    ParkingAttendant::create([
        'user_id' => $agentC->id,
        'created_by' => $supervisor->id,
    ]);

    // Create cashier A assigned to Parking A (should be visible)
    $cashierA = User::factory()->create(['role' => User::ROLE_CAISSIER]);
    Caissier::create([
        'user_id' => $cashierA->id,
        'created_by' => $admin->id,
    ]);
    $cashierA->parkings()->attach($parkingA->id);

    // Create cashier B assigned to Parking B (should NOT be visible)
    $cashierB = User::factory()->create(['role' => User::ROLE_CAISSIER]);
    Caissier::create([
        'user_id' => $cashierB->id,
        'created_by' => $admin->id,
    ]);
    $cashierB->parkings()->attach($parkingB->id);

    // Call the supervisor user list API/page
    $response = $this->actingAs($supervisor)->get(route('supervisor.users.index'));

    $response->assertOk();

    // The returned Inertia page props should contain the users
    $users = $response->original->getData()['page']['props']['users'];

    $userIds = collect($users)->pluck('id');

    expect($userIds)->toContain($agentA->id);
    expect($userIds)->toContain($agentC->id);
    expect($userIds)->toContain($cashierA->id);
    expect($userIds)->not->toContain($agentB->id);
    expect($userIds)->not->toContain($cashierB->id);
});

test('supervisor can see all attendants of their parking lots in attendants index', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    $parkingA = Parking::create([
        'reference' => 'PKG-A',
        'name' => 'Parking A',
        'address' => '123 Main St',
        'capacity' => 100,
        'created_by' => $admin->id,
        'is_active' => true,
    ]);
    $parkingB = Parking::create([
        'reference' => 'PKG-B',
        'name' => 'Parking B',
        'address' => '456 Side St',
        'capacity' => 50,
        'created_by' => $admin->id,
        'is_active' => true,
    ]);

    $supervisor->parkings()->attach($parkingA->id);

    $agentA = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
    ParkingAttendant::create([
        'user_id' => $agentA->id,
        'created_by' => $admin->id,
    ]);
    $agentA->parkings()->attach($parkingA->id);

    $agentB = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
    ParkingAttendant::create([
        'user_id' => $agentB->id,
        'created_by' => $admin->id,
    ]);
    $agentB->parkings()->attach($parkingB->id);

    $response = $this->actingAs($supervisor)->get(route('supervisor.attendants.index'));
    $response->assertOk();

    $attendants = $response->original->getData()['page']['props']['attendants'];
    $attendantIds = collect($attendants)->pluck('id');

    expect($attendantIds)->toContain($agentA->id);
    expect($attendantIds)->not->toContain($agentB->id);
});

test('supervisor can see all cashiers of their parking lots in cashiers index', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    $parkingA = Parking::create([
        'reference' => 'PKG-A',
        'name' => 'Parking A',
        'address' => '123 Main St',
        'capacity' => 100,
        'created_by' => $admin->id,
        'is_active' => true,
    ]);
    $parkingB = Parking::create([
        'reference' => 'PKG-B',
        'name' => 'Parking B',
        'address' => '456 Side St',
        'capacity' => 50,
        'created_by' => $admin->id,
        'is_active' => true,
    ]);

    $supervisor->parkings()->attach($parkingA->id);

    $cashierA = User::factory()->create(['role' => User::ROLE_CAISSIER]);
    Caissier::create([
        'user_id' => $cashierA->id,
        'created_by' => $admin->id,
    ]);
    $cashierA->parkings()->attach($parkingA->id);

    $cashierB = User::factory()->create(['role' => User::ROLE_CAISSIER]);
    Caissier::create([
        'user_id' => $cashierB->id,
        'created_by' => $admin->id,
    ]);
    $cashierB->parkings()->attach($parkingB->id);

    $response = $this->actingAs($supervisor)->get(route('supervisor.caissiers.index'));
    $response->assertOk();

    $caissiers = $response->original->getData()['page']['props']['caissiers'];
    $caissierIds = collect($caissiers)->pluck('id');

    expect($caissierIds)->toContain($cashierA->id);
    expect($caissierIds)->not->toContain($cashierB->id);
});

test('supervisor can see all agents with due amounts of their parking lots in versements index', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    $parkingA = Parking::create([
        'reference' => 'PKG-A',
        'name' => 'Parking A',
        'address' => '123 Main St',
        'capacity' => 100,
        'created_by' => $admin->id,
        'is_active' => true,
    ]);
    $parkingB = Parking::create([
        'reference' => 'PKG-B',
        'name' => 'Parking B',
        'address' => '456 Side St',
        'capacity' => 50,
        'created_by' => $admin->id,
        'is_active' => true,
    ]);

    $supervisor->parkings()->attach($parkingA->id);

    $agentA = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
    ParkingAttendant::create([
        'user_id' => $agentA->id,
        'created_by' => $admin->id,
    ]);
    $agentA->parkings()->attach($parkingA->id);

    $agentB = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
    ParkingAttendant::create([
        'user_id' => $agentB->id,
        'created_by' => $admin->id,
    ]);
    $agentB->parkings()->attach($parkingB->id);

    // Create a closed session with money due for both agents to ensure they pass the total_due > 0 filter
    ParkingSession::create([
        'parking_id' => $parkingA->id,
        'license_plate' => 'AA-123-AA',
        'status' => 'released',
        'started_at' => now()->subHour(),
        'ended_at' => now(),
        'amount' => 500,
        'closed_by' => $agentA->id,
        'created_by' => $agentA->id,
    ]);

    ParkingSession::create([
        'parking_id' => $parkingB->id,
        'license_plate' => 'BB-123-BB',
        'status' => 'released',
        'started_at' => now()->subHour(),
        'ended_at' => now(),
        'amount' => 1000,
        'closed_by' => $agentB->id,
        'created_by' => $agentB->id,
    ]);

    $response = $this->actingAs($supervisor)->get(route('supervisor.versements.index'));
    $response->assertOk();

    $agents = $response->original->getData()['page']['props']['agents'];
    $agentIds = collect($agents)->pluck('id');

    expect($agentIds)->toContain($agentA->id);
    expect($agentIds)->not->toContain($agentB->id);
});
