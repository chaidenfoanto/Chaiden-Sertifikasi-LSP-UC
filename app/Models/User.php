<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Application user, either an admin or a renter.
 */
class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_RENTER = 'renter';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'full_name',
        'nik',
        'phone_number',
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Attribute casting rules.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Determine whether the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Determine whether the user is a renter.
     */
    public function isRenter(): bool
    {
        return $this->role === self::ROLE_RENTER;
    }

    /**
     * Determine whether the account has been approved by an admin.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}