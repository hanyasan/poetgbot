<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

final class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        /** @var JWTGuard $jwtGuard */
        $jwtGuard = Auth::guard('api');
        $token = $jwtGuard->attempt([
            'email' => $request->email,
            'password' => $request->password,
        ]);

        /** @psalm-suppress RiskyTruthyFalsyComparison */
        if (! $token) {
            return response()->json([
                'success' => false,
                'data' => [
                    'message' => 'Unauthorized',
                ],
            ], 401);
        }

        return $this->response([
                'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        /** @var JWTGuard $jwtGuard */
        $jwtGuard = Auth::guard('api');
        $token = $jwtGuard->login($user);

        return $this->response([
            'message' => 'User created successfully',
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function logout(): JsonResponse
    {
        /** @var JWTGuard $jwtGuard */
        $jwtGuard = Auth::guard('api');
        $jwtGuard->logout();

        return $this->response([
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh(): JsonResponse
    {
        /** @var JWTGuard $jwtGuard */
        $jwtGuard = Auth::guard('api');

        return $this->response([
            'authorisation' => [
                'token' => $jwtGuard->refresh(),
                'type' => 'bearer',
            ]
        ]);
    }
}
