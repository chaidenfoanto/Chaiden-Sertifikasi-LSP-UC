<?php

namespace Tests\Unit;

use App\Services\RentalPlanner;
use App\Services\RentalPricing;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class RentalPricingTest extends TestCase
{
    private RentalPricing $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricing = new RentalPricing();
    }

    public function test_monthly_price_is_25_times_the_daily_price(): void
    {
        $this->assertSame(250000, $this->pricing->monthlyPrice(10000));
    }

    public function test_split_rental_is_priced_per_part(): void
    {
        $plan = (new RentalPlanner())->plan(CarbonImmutable::create(2027, 1, 1), 'daily', 45);
        $items = $this->pricing->priceItems($plan, 10000);

        $this->assertSame(250000, $items[0]['subtotal']);
        $this->assertSame(140000, $items[1]['subtotal']);
        $this->assertSame(390000, $this->pricing->subtotal($items));
    }

    public function test_charging_cost_uses_missing_kwh_times_price(): void
    {
        $this->assertSame(125000, $this->pricing->chargingCost(60, 10, 2500));
        $this->assertSame(30250, $this->pricing->chargingCost(40.5, 10.25, 1000));
    }

    public function test_charging_cost_never_goes_below_zero(): void
    {
        $this->assertSame(0, $this->pricing->chargingCost(50, 60, 1000));
    }
}