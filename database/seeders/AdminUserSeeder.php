<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.email');
        $password = config('admin.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->command?->warn('Admin not seeded: configure ADMIN_EMAIL and ADMIN_PASSWORD.');

            return;
        }

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => config('admin.name', 'System Administrator'), 'password' => $password]
        );
        $admin->syncRoles([RoleName::Admin->value]);
    }
}
