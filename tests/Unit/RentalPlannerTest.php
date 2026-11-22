<?php

namespace Tests\Unit;

use App\Services\RentalPlanner;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RentalPlannerTest extends TestCase
{
    private RentalPlanner $planner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->planner = new RentalPlanner();
    }

    private function day(int $year, int $month, int $day): CarbonImmutable
    {
        return CarbonImmutable::create($year, $month, $day)->startOfDay();
    }

    public function test_max_daily_days_is_days_in_month_minus_one(): void
    {
        $this->assertSame(29, $this->planner->maxDailyDays($this->day(2026, 11, 10)));
        $this->assertSame(30, $this->planner->maxDailyDays($this->day(2027, 1, 5)));
        $this->assertSame(27, $this->planner->maxDailyDays($this->day(2027, 2, 5)));
        $this->assertSame(28, $this->planner->maxDailyDays($this->day(2028, 2, 5)));
    }

    public function test_short_daily_rental_is_a_single_daily_item(): void
    {
        $plan = $this->planner->plan($this->day(2026, 11, 10), 'daily', 5);

        $this->assertCount(1, $plan);
        $this->assertSame('daily', $plan[0]['rental_type']);
        $this->assertSame(5, $plan[0]['days']);
        $this->assertSame('2026-11-14', $plan[0]['end_date']->toDateString());
    }

    public function test_monthly_rental_follows_days_in_start_month(): void
    {
        $plan = $this->planner->plan($this->day(2026, 11, 10), 'monthly', 1);

        $this->assertCount(1, $plan);
        $this->assertSame(30, $plan[0]['days']);
        $this->assertSame('2026-12-09', $plan[0]['end_date']->toDateString());
    }

    public function test_daily_rental_as_long_as_the_month_becomes_monthly(): void
    {
        $plan = $this->planner->plan($this->day(2026, 11, 10), 'daily', 30);

        $this->assertCount(1, $plan);
        $this->assertSame('monthly', $plan[0]['rental_type']);
    }

    public function test_45_days_in_a_31_day_month_is_one_month_plus_14_days(): void
    {
        $plan = $this->planner->plan($this->day(2027, 1, 1), 'daily', 45);

        $this->assertCount(2, $plan);
        $this->assertSame('monthly', $plan[0]['rental_type']);
        $this->assertSame(31, $plan[0]['days']);
        $this->assertSame('2027-01-31', $plan[0]['end_date']->toDateString());
        $this->assertSame('daily', $plan[1]['rental_type']);
        $this->assertSame(14, $plan[1]['days']);
        $this->assertSame('2027-02-01', $plan[1]['start_date']->toDateString());
        $this->assertSame('2027-02-14', $plan[1]['end_date']->toDateString());
    }

    public function test_split_uses_the_length_of_each_month_it_passes(): void
    {
        $plan = $this->planner->plan($this->day(2027, 2, 1), 'daily', 60);

        $this->assertCount(3, $plan);
        $this->assertSame(28, $plan[0]['days']);
        $this->assertSame(31, $plan[1]['days']);
        $this->assertSame('daily', $plan[2]['rental_type']);
        $this->assertSame(1, $plan[2]['days']);
        $this->assertSame('2027-04-01', $this->planner->endDate($plan)->toDateString());
    }

    public function test_single_monthly_rental_lasts_as_long_as_its_start_month(): void
    {
        $november = $this->planner->planSingle($this->day(2026, 11, 15), 'monthly', null);
        $december = $this->planner->planSingle($this->day(2026, 12, 1), 'monthly', null);

        $this->assertCount(1, $november);
        $this->assertSame(30, $november[0]['days']);
        $this->assertSame('2026-12-14', $november[0]['end_date']->toDateString());
        $this->assertSame(31, $december[0]['days']);
        $this->assertSame('2026-12-31', $december[0]['end_date']->toDateString());
    }

    public function test_single_daily_rental_may_last_29_days_in_november_even_from_the_15th(): void
    {
        $plan = $this->planner->planSingle($this->day(2026, 11, 15), 'daily', $this->day(2026, 12, 13));

        $this->assertSame('daily', $plan[0]['rental_type']);
        $this->assertSame(29, $plan[0]['days']);
    }

    public function test_single_daily_rental_of_30_days_in_november_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->planner->planSingle($this->day(2026, 11, 15), 'daily', $this->day(2026, 12, 14));
    }

    public function test_single_daily_rental_in_february_of_a_common_year_allows_27_days(): void
    {
        $plan = $this->planner->planSingle($this->day(2027, 2, 1), 'daily', $this->day(2027, 2, 27));

        $this->assertSame(27, $plan[0]['days']);

        $this->expectException(InvalidArgumentException::class);
        $this->planner->planSingle($this->day(2027, 2, 1), 'daily', $this->day(2027, 2, 28));
    }

    public function test_single_daily_rental_needs_an_end_date_that_is_not_before_the_start(): void
    {
        $sameDay = $this->planner->planSingle($this->day(2026, 11, 15), 'daily', $this->day(2026, 11, 15));
        $this->assertSame(1, $sameDay[0]['days']);

        $this->expectException(InvalidArgumentException::class);
        $this->planner->planSingle($this->day(2026, 11, 15), 'daily', $this->day(2026, 11, 14));
    }

    public function test_single_daily_rental_without_an_end_date_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->planner->planSingle($this->day(2026, 11, 15), 'daily', null);
    }

    public function test_invalid_quantity_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->planner->plan($this->day(2026, 11, 10), 'daily', 0);
    }

    public function test_unknown_rental_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->planner->plan($this->day(2026, 11, 10), 'weekly', 2);
    }
}