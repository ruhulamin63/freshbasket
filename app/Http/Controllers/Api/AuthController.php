<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->validated());

        return $this->tokenResponse($result['token'], $result['user'], Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->auth->login($request->validated());

        return $this->tokenResponse($token, $this->auth->guard()->user());
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user('api'))]);
    }

    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return response()->json(['message' => 'Successfully logged out.']);
    }

    public function refresh(): JsonResponse
    {
        $token = $this->auth->refresh();

        return $this->tokenResponse($token, $this->auth->guard()->user());
    }

    private function tokenResponse(string $token, mixed $user, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $this->auth->guard()->factory()->getTTL() * 60,
                'user' => new UserResource($user),
            ],
        ], $status);
    }
}
