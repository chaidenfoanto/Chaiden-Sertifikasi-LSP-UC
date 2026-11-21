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

    public function test_renter_sees_only_renter_features_without_overlay(): void
    {
        $this->actingAs($this->makeUser(User::ROLE_RENTER))
            ->get('/features')
            ->assertOk()
            ->assertSee('New rental')
            ->assertDontSee('Create garages')
            ->assertDontSee('Log in or register to unlock these features.');
    }

    public function test_admin_sees_only_admin_features_without_overlay(): void
    {
        $this->actingAs($this->makeUser(User::ROLE_ADMIN))
            ->get('/features')
            ->assertOk()
            ->assertSee('Create garages')
            ->assertDontSee('New rental')
            ->assertDontSee('Log in or register to unlock these features.');
    }
}