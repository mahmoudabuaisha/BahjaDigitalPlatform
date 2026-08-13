<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('BAHJA_ADMIN_EMAIL', 'admin@bahja.ps');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'إدارة بهجة',
                'password' => env('BAHJA_ADMIN_PASSWORD', 'ChangeMe!2026'),
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
            ],
        );
    }
}
