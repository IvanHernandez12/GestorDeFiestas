<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 13/noviembre/2026
 *
 * @modificado 24/noviembre/2026
 *
 * @descripción Prueba los endpoints de registro e inicio y cierre de sesión.
 */

use App\Models\Plan;
use App\Models\User;

test('returns a Sanctum token when login credentials are valid', function (): void {
    $user = User::factory()->create();

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonStructure(['token']);
    $this->assertDatabaseCount('personal_access_tokens', 1);

    $this->withToken($response->json('token'))
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});

test('returns unauthorized when login credentials are invalid', function (): void {
    $user = User::factory()->create();

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'incorrect-password',
    ])->assertUnauthorized()
        ->assertJsonPath('message', 'Las credenciales no son válidas.');

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('registers a user on the free plan and issues a token', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Organizadora de Prueba',
        'email' => 'organizer@example.com',
        'password' => 'strong-password',
        'password_confirmation' => 'strong-password',
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.email', 'organizer@example.com')
        ->assertJsonStructure(['token']);

    $freePlan = Plan::query()->where('slug', 'free')->firstOrFail();

    $this->assertDatabaseHas('users', [
        'email' => 'organizer@example.com',
        'plan_id' => $freePlan->id,
    ]);
    $this->assertDatabaseHas('plans', [
        'slug' => 'free',
        'max_events' => 1,
        'max_guests' => 50,
        'is_free' => true,
    ]);
});

test('revokes the current token on logout', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/auth/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Sesión cerrada correctamente.');

    $this->assertDatabaseCount('personal_access_tokens', 0);
    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
});
