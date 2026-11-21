<?php

namespace Tests\Concerns;

use App\Models\User;

/**
 * Helper for tests that need users with known credentials.
 */
trait CreatesUsers
{
    private int $userCounter = 0;

    /**
     * Create a user whose password is "password123".
     */
    protected function makeUser(string $role = User::ROLE_RENTER, string $status = User::STATUS_APPROVED): User
    {
        $number = ++$this->userCounter;

        return User::create([
            'full_name' => "Test User {$number}",
            'nik' => $role === User::ROLE_RENTER ? sprintf('%016d', $number) : null,
            'phone_number' => '081234567890',
            'email' => "user{$number}@test.local",
            'password' => 'password123',
            'role' => $role,
            'status' => $status,
        ]);
    }
}