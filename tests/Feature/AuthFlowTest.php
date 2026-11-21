<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Covers registration, login with the JWT cookie, logout, and role access.
 */
class AuthFlowTest extends TestCase
{
    use RefreshDatabase;
    use CreatesUsers;

    /**
     * Valid registration input, with optional overrides.
     *
     * @param array<string, string> $override
     * @return array<string, string>
     */
    private function registrationData(array $override = []): array
    {
        return array_merge([
            'full_name' => 'Siti Aminah',
            'nik' => '7371000000000099',
            'phone_number' => '081234567890',
            'email' => 'siti@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $override);
    }

    /**
     * Invalid registration inputs paired with the field that must fail.
     *
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function invalidRegistrations(): array
    {
        return [
            'nik too short' => [['nik' => '12345'], 'nik'],
            'nik with letters' => [['nik' => '73710000000000AB'], 'nik'],
            'phone with letters' => [['phone_number' => '08ab'], 'phone_number'],
            'invalid email' => [['email' => 'not-an-email'], 'email'],
            'password too short' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
            'password not confirmed' => [['password_confirmation' => 'different'], 'password'],
            'missing name' => [['full_name' => ''], 'full_name'],
        ];
    }

    public function test_registration_creates_a_pending_renter(): void
    {
        $this->post(route('register.store'), $this->registrationData())
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', [
            'email' => 'siti@test.local',
            'role' => User::ROLE_RENTER,
            'status' => User::STATUS_PENDING,
        ]);
    }

    public function test_registration_ignores_role_and_status_sent_by_the_client(): void
    {
        $this->post(route('register.store'), $this->registrationData(['role' => 'admin', 'status' => 'approved']));

        $user = User::where('email', 'siti@test.local')->firstOrFail();

        $this->assertSame(User::ROLE_RENTER, $user->role);
        $this->assertSame(User::STATUS_PENDING, $user->status);
    }

    /**
     * @param array<string, string> $override
     */
    #[DataProvider('invalidRegistrations')]
    public function test_invalid_registration_is_rejected(array $override, string $field): void
    {
        $this->post(route('register.store'), $this->registrationData($override))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseMissing('users', ['email' => 'siti@test.local']);
    }

    public function test_registration_rejects_a_nik_that_is_already_used(): void
    {
        $existing = $this->makeUser();

        $this->post(route('register.store'), $this->registrationData(['nik' => $existing->nik]))
            ->assertSessionHasErrors('nik');
    }

    public function test_registration_rejects_an_email_that_is_already_used(): void
    {
        $existing = $this->makeUser();

        $this->post(route('register.store'), $this->registrationData(['email' => $existing->email]))
            ->assertSessionHasErrors('email');
    }

    public function test_approved_renter_logs_in_with_an_http_only_cookie(): void
    {
        $renter = $this->makeUser();

        $response = $this->post(route('login.store'), ['email' => $renter->email, 'password' => 'password123']);

        $response->assertRedirect(route('renter.dashboard'))->assertCookie('access_token');
        $this->assertTrue($response->getCookie('access_token', false)->isHttpOnly());
    }

    public function test_admin_is_redirected_to_the_admin_dashboard_after_login(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);

        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password123'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_pending_renter_cannot_log_in(): void
    {
        $pending = $this->makeUser(User::ROLE_RENTER, User::STATUS_PENDING);

        $this->post(route('login.store'), ['email' => $pending->email, 'password' => 'password123'])
            ->assertSessionHasErrors(['email' => 'Your account is still waiting for admin approval.'])
            ->assertCookieMissing('access_token');
    }

    public function test_rejected_renter_sees_a_clear_message(): void
    {
        $rejected = $this->makeUser(User::ROLE_RENTER, User::STATUS_REJECTED);

        $this->post(route('login.store'), ['email' => $rejected->email, 'password' => 'password123'])
            ->assertSessionHasErrors(['email' => 'Your registration was rejected by the admin.'])
            ->assertCookieMissing('access_token');
    }

    public function test_wrong_password_and_unknown_email_get_the_same_message(): void
    {
        $renter = $this->makeUser();

        $this->post(route('login.store'), ['email' => $renter->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'Invalid email or password.']);

        $this->post(route('login.store'), ['email' => 'nobody@test.local', 'password' => 'password123'])
            ->assertSessionHasErrors(['email' => 'Invalid email or password.']);
    }

    public function test_token_cookie_authenticates_later_requests_and_logout_clears_it(): void
    {
        $renter = $this->makeUser();
        $login = $this->post(route('login.store'), ['email' => $renter->email, 'password' => 'password123']);
        $token = $login->getCookie('access_token', false)->getValue();

        // Drop the guard state left by the login request, so only the cookie authenticates.
        $this->app['auth']->forgetGuards();

        $this->withUnencryptedCookie('access_token', $token)
            ->get(route('renter.dashboard'))
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withUnencryptedCookie('access_token', $token)
            ->post(route('logout'))
            ->assertRedirect(route('login'))
            ->assertCookieExpired('access_token');
    }

    public function test_logged_in_user_is_not_bounced_between_login_and_dashboard(): void
    {
        $renter = $this->makeUser();
        $login = $this->post(route('login.store'), ['email' => $renter->email, 'password' => 'password123']);
        $token = $login->getCookie('access_token', false)->getValue();

        // The login page must send a logged in user to the dashboard.
        $this->app['auth']->forgetGuards();
        $this->withUnencryptedCookie('access_token', $token)
            ->get(route('login'))
            ->assertRedirect(route('renter.dashboard'));

        // The dashboard must accept the same cookie instead of redirecting back.
        $this->app['auth']->forgetGuards();
        $this->withUnencryptedCookie('access_token', $token)
            ->get(route('renter.dashboard'))
            ->assertOk();
    }

    public function test_guest_is_redirected_to_login_from_protected_pages(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('renter.rentals.index'))->assertRedirect(route('login'));
    }

    public function test_renter_cannot_open_admin_pages(): void
    {
        $this->actingAs($this->makeUser());

        $this->get(route('admin.garages.create'))->assertForbidden();
        $this->get(route('admin.renters.index'))->assertForbidden();
        $this->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_admin_cannot_open_renter_pages(): void
    {
        $this->actingAs($this->makeUser(User::ROLE_ADMIN));

        $this->get(route('renter.rentals.create'))->assertForbidden();
        $this->get(route('renter.rentals.index'))->assertForbidden();
    }
}