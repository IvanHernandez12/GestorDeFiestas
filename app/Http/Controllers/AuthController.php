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
 * @descripción Gestiona registro, inicio y cierre de sesion para la API.
 */

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Registra una cuenta con el plan gratuito y entrega un token de API.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'max:255', Password::min(8)],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $freePlan = Plan::query()->firstOrCreate(
                ['slug' => 'free'],
                [
                    'name' => 'Gratuito',
                    'price' => 0,
                    'max_events' => 1,
                    'max_guests' => 50,
                    'is_free' => true,
                ],
            );

            return User::query()->create([
                'plan_id' => $freePlan->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
        });

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('auth-token')->plainTextToken,
        ], 201);
    }

    /**
     * Valida las credenciales y entrega un token de API.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Las credenciales no son válidas.'], 401);
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('auth-token')->plainTextToken,
        ]);
    }

    /**
     * Revoca el token bearer utilizado en la petición actual.
     */
    public function logout(Request $request): JsonResponse
    {
        $currentAccessToken = $request->user()?->currentAccessToken();

        if ($currentAccessToken instanceof PersonalAccessToken) {
            $currentAccessToken->delete();
        }

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }
}
