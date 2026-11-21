<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Covers the admin approval and rejection of new renter registrations.
 */
class RenterVerificationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesUsers;

    public function test_admin_approves_a_pending_renter(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $pending = $this->makeUser(User::ROLE_RENTER, User::STATUS_PENDING);

        $this->actingAs($admin)
            ->patch(route('admin.renters.approve', $pending))
            ->assertRedirect(route('admin.renters.index'));

        $this->assertSame(User::STATUS_APPROVED, $pending->fresh()->status);
    }

    public function test_admin_rejects_a_pending_renter(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $pending = $this->makeUser(User::ROLE_RENTER, User::STATUS_PENDING);

        $this->actingAs($admin)
            ->patch(route('admin.renters.reject', $pending))
            ->assertRedirect(route('admin.renters.index'));

        $this->assertSame(User::STATUS_REJECTED, $pending->fresh()->status);
    }

    public function test_a_renter_that_is_not_pending_cannot_be_changed(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $approved = $this->makeUser();

        $this->actingAs($admin)
            ->patch(route('admin.renters.reject', $approved))
            ->assertNotFound();

        $this->assertSame(User::STATUS_APPROVED, $approved->fresh()->status);
    }

    public function test_renter_cannot_approve_accounts(): void
    {
        $renter = $this->makeUser();
        $pending = $this->makeUser(User::ROLE_RENTER, User::STATUS_PENDING);

        $this->actingAs($renter)
            ->patch(route('admin.renters.approve', $pending))
            ->assertForbidden();

        $this->assertSame(User::STATUS_PENDING, $pending->fresh()->status);
    }

    public function test_verification_list_shows_only_pending_renters(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $pending = $this->makeUser(User::ROLE_RENTER, User::STATUS_PENDING);
        $approved = $this->makeUser();

        $this->actingAs($admin)
            ->get(route('admin.renters.index'))
            ->assertOk()
            ->assertSee($pending->email)
            ->assertDontSee($approved->email);
    }
}