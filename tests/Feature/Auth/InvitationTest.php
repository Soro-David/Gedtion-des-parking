<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

test('set password screen can be rendered with valid token', function () {
    $user = User::factory()->create([
        'invitation_token' => 'valid-token',
        'invitation_expires_at' => Carbon::now()->addDays(7),
    ]);

    $response = $this->get('/invitation/valid-token');

    $response->assertStatus(200);
});

test('user can set password and upload avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'invitation_token' => 'valid-token',
        'invitation_expires_at' => Carbon::now()->addDays(7),
    ]);

    $avatar = UploadedFile::fake()->image('avatar.jpg');

    $response = $this->post('/invitation', [
        'token' => 'valid-token',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
        'avatar' => $avatar,
    ]);


    $response->assertRedirect(route('login'));

    $user->refresh();

    $this->assertTrue(Hash::check('new-password-123', $user->password));
    $this->assertNull($user->invitation_token);
    $this->assertNull($user->invitation_expires_at);
    $this->assertNotNull($user->email_verified_at);
    $this->assertNotNull($user->avatar);
    
    Storage::disk('public')->assertExists($user->avatar);
});
