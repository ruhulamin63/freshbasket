<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTGuard;

class AuthService
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    /** @param array{name: string, email: string, password: string} $data @return array{user: User, token: string} */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = $this->users->create($data);
            $user->assignRole(RoleName::User->value);

            return ['user' => $user, 'token' => $this->guard()->login($user)];
        });
    }

    /** @param array{email: string, password: string} $credentials */
    public function login(array $credentials): string
    {
        $token = $this->guard()->attempt([...$credentials, 'is_active' => true]);

        if (! $token) {
            throw ValidationException::withMessages(['email' => ['The supplied credentials are invalid.']]);
        }

        return $token;
    }

    public function logout(): void
    {
        $this->guard()->logout();
    }

    public function refresh(): string
    {
        return $this->guard()->refresh();
    }

    public function guard(): JWTGuard
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        return $guard;
    }
}
