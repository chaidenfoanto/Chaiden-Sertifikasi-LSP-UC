<?php

namespace Tests\Feature;

use App\Models\GarageType;
use App\Models\User;
use App\Services\GarageService;
use Database\Seeders\GarageTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the public home page and the locked features page.
 */
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'full_name' => 'Test ' . $role,
            'email' => $role . '@test.local',
            'password' => 'password123',
            'role' => $role,
            'status' => User::STATUS_APPROVED,
        ]);
    }

    public function test_home_page_lists_garage_types_with_daily_and_monthly_prices(): void
    {
        $this->seed(GarageTypeSeeder::class);
        $bicycle = GarageType::where('name', GarageType::BICYCLE)->first();
        app(GarageService::class)->createBatch($bicycle, 1, 5000, null);

        $this->get('/')
            ->assertOk()
            ->assertSee('Rp 5.000')
            ->assertSee('Rp 125.000');
    }

    public function test_visitor_sees_the_lock_overlay_on_the_features_page(): void
    {
        $this->get('/features')
            ->assertOk()
            ->assertSee('Log in or register to unlock these features.');
    }

    public function test_login_and_register_pages_have_no_footer(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('Garages for bicycles, motorcycles');
        $this->get(route('register'))->assertOk()->assertDontSee('Garages for bicycles, motorcycles');
    }

    public function test_home_and_features_pages_keep_the_footer(): void
    {
        $this->get('/')->assertOk()->assertSee('Garages for bicycles, motorcycles');
        $this->get('/features')->assertOk()->assertSee('Garages for bicycles, motorcycles');
    }

    public function test_renter_sees_only_renter_features_without_overlay(): void
    {
        $this->actingAs($this->makeUser(User::ROLE_RENTER))
            ->get('/features')
            ->assertOk()
            ->assertSee('New rental')
            ->assertDontSee('Create garages')
            ->assertDontSee('Log in or register to unlock these features.');
    }

    public function test_admin_is_redirected_from_features_to_the_dashboard(): void
    {
        $this->actingAs($this->makeUser(User::ROLE_ADMIN))
            ->get('/features')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_still_view_the_public_home_page(): void
    {
        $this->seed(GarageTypeSeeder::class);

        $this->actingAs($this->makeUser(User::ROLE_ADMIN))
            ->get('/')
            ->assertOk()
            ->assertSee('Go to dashboard');
    }
}