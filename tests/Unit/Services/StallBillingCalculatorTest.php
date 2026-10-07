<?php

namespace Tests\Unit\Services;

use App\Services\StallBillingCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class StallBillingCalculatorTest extends TestCase
{
    public function test_calculates_rent_surcharge_and_compounding_interest_in_centavos(): void
    {
        $calculator = new StallBillingCalculator;

        $statement = $calculator->calculate(
            '3.25',
            '2.40',
            '125.75',
            '2026-10-21',
            '2026-10-20',
            2,
            30,
            '2026-12-31',
        );

        $this->assertSame([
            'base_rent' => '980.85',
            'late_surcharge' => '245.21',
            'interest' => '49.53',
            'total_due' => '1275.59',
            'is_late' => true,
            'days_past_due' => 1,
            'revocation_flag_a' => true,
            'revocation_flag_b' => true,
            'annual_renewal_required' => false,
        ], $statement);
    }

    public function test_calculates_without_late_surcharge_on_the_due_date(): void
    {
        $statement = (new StallBillingCalculator)->calculate(
            '2.00',
            '2.00',
            '25.00',
            '2026-10-20',
            '2026-10-20',
            1,
            29,
            '2026-12-31',
        );

        $this->assertSame('100.00', $statement['base_rent']);
        $this->assertSame('0.00', $statement['late_surcharge']);
        $this->assertSame('2.00', $statement['interest']);
        $this->assertSame('102.00', $statement['total_due']);
        $this->assertFalse($statement['is_late']);
        $this->assertSame(0, $statement['days_past_due']);
        $this->assertFalse($statement['revocation_flag_a']);
        $this->assertFalse($statement['revocation_flag_b']);
        $this->assertFalse($statement['annual_renewal_required']);
    }

    public function test_rounds_half_centavo_surcharges_up(): void
    {
        $statement = (new StallBillingCalculator)->calculate(
            '1.00',
            '1.00',
            '0.02',
            '2026-10-21',
            '2026-10-20',
            0,
            0,
            '2026-12-31',
        );

        $this->assertSame('0.02', $statement['base_rent']);
        $this->assertSame('0.01', $statement['late_surcharge']);
        $this->assertSame('0.00', $statement['interest']);
        $this->assertSame('0.03', $statement['total_due']);
    }

    public function test_requires_renewal_after_december_31_of_the_contract_end_year(): void
    {
        $statement = (new StallBillingCalculator)->calculate(
            '2.00',
            '2.00',
            '25.00',
            '2027-01-01',
            '2026-12-20',
            0,
            0,
            '2026-06-30',
        );

        $this->assertTrue($statement['annual_renewal_required']);
        $this->assertSame(12, $statement['days_past_due']);
    }

    public function test_does_not_require_renewal_on_december_31_of_the_contract_end_year(): void
    {
        $statement = (new StallBillingCalculator)->calculate(
            '2.00',
            '2.00',
            '25.00',
            '2026-12-31',
            '2026-12-20',
            0,
            0,
            '2026-06-30',
        );

        $this->assertFalse($statement['annual_renewal_required']);
    }

    public function test_requires_the_due_date_to_be_the_twentieth_of_the_month(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new StallBillingCalculator)->calculate(
            '2.00',
            '2.00',
            '25.00',
            '2026-10-20',
            '2026-10-19',
            0,
            0,
            '2026-12-31',
        );
    }

    public function test_rejects_measurements_with_more_than_two_decimal_places(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new StallBillingCalculator)->calculate(
            '1.001',
            '2.00',
            '25.00',
            '2026-10-20',
            '2026-10-20',
            0,
            0,
            '2026-12-31',
        );
    }

    public function test_rejects_invalid_iso_dates(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new StallBillingCalculator)->calculate(
            '2.00',
            '2.00',
            '25.00',
            '2026-02-30',
            '2026-10-20',
            0,
            0,
            '2026-12-31',
        );
    }

    public function test_rejects_negative_unpaid_months_or_abandoned_days(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new StallBillingCalculator)->calculate(
            '2.00',
            '2.00',
            '25.00',
            '2026-10-20',
            '2026-10-20',
            -1,
            0,
            '2026-12-31',
        );
    }
}
