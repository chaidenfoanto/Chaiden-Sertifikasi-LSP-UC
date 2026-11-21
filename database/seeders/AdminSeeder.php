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
            ['email' => 'admin@garage.test'],
            [
                'full_name' => 'System Admin',
                'password' => 'admin12345',
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_APPROVED,
            ]
        );
    }
}