<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the single admin account (admins cannot self-register).
 */
class AdminSeeder extends Seeder
{
    /**
     * Create or update the default admin account.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin.chaiden@gmail.com'],
            [
                'full_name' => 'Administrator',
                'password' => 'superadmin',
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_APPROVED,
            ]
        );
    }
}