<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

/**
 * Application user, either an admin or a renter.
 */
class User extends Authenticatable implements JWTSubject
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

    /**
     * Get the identifier stored in the JWT "sub" claim.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Get custom claims added to the JWT payload (the user role).
     *
     * @return array<string, string>
     */
    public function getJWTCustomClaims(): array
    {
        return ['role' => $this->role];
    }
}