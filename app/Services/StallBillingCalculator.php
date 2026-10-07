<?php

namespace App\Services;

use DateTimeImmutable;
use InvalidArgumentException;
use OverflowException;

class StallBillingCalculator
{
    /**
     * @return array{
     *     base_rent: string,
     *     late_surcharge: string,
     *     interest: string,
     *     total_due: string,
     *     is_late: bool,
     *     days_past_due: int,
     *     revocation_flag_a: bool,
     *     revocation_flag_b: bool,
     *     annual_renewal_required: bool
     * }
     */
    public function calculate(
        string $length,
        string $width,
        string $sectionalRate,
        string $billingDate,
        string $dueDate,
        int $consecutiveUnpaidMonths,
        int $daysAbandoned,
        string $contractEndDate,
    ): array {
        $lengthInHundredths = $this->toMinorUnits($length, 'length', 6);
        $widthInHundredths = $this->toMinorUnits($width, 'width', 6);
        $rateInCentavos = $this->toMinorUnits($sectionalRate, 'sectional rate', 8);
        $billingDay = $this->parseDate($billingDate, 'billing date');
        $dueDay = $this->parseDate($dueDate, 'due date');
        $contractEndDay = $this->parseDate($contractEndDate, 'contract end date');

        if ($dueDay->format('j') !== '20') {
            throw new InvalidArgumentException('The due date must be the 20th day of the month.');
        }

        if ($consecutiveUnpaidMonths < 0 || $daysAbandoned < 0) {
            throw new InvalidArgumentException('Unpaid months and abandoned days cannot be negative.');
        }

        $baseRent = $this->calculateBaseRent($lengthInHundredths, $widthInHundredths, $rateInCentavos);
        $isLate = $billingDay > $dueDay;
        $daysPastDue = $isLate ? (int) $dueDay->diff($billingDay)->days : 0;
        $lateSurcharge = $isLate ? $this->roundHalfUp($baseRent, 25, 100) : 0;
        $balance = $baseRent + $lateSurcharge;
        $initialBalance = $balance;

        for ($month = 0; $month < $consecutiveUnpaidMonths; $month++) {
            $balance = $this->roundHalfUp($balance, 102, 100);
        }

        $renewalDeadline = $contractEndDay->setDate((int) $contractEndDay->format('Y'), 12, 31);

        return [
            'base_rent' => $this->formatCentavos($baseRent),
            'late_surcharge' => $this->formatCentavos($lateSurcharge),
            'interest' => $this->formatCentavos($balance - $initialBalance),
            'total_due' => $this->formatCentavos($balance),
            'is_late' => $isLate,
            'days_past_due' => $daysPastDue,
            'revocation_flag_a' => $consecutiveUnpaidMonths >= 2,
            'revocation_flag_b' => $daysAbandoned >= 30,
            'annual_renewal_required' => $billingDay > $renewalDeadline,
        ];
    }

    private function toMinorUnits(string $value, string $field, int $maximumWholeDigits): int
    {
        $pattern = '/^\d{1,'.$maximumWholeDigits.'}(?:\.\d{1,2})?$/D';
        if (! preg_match($pattern, $value)) {
            throw new InvalidArgumentException(ucfirst($field).' must be a positive decimal with at most two decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $minorUnits = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
        if ($minorUnits <= 0) {
            throw new InvalidArgumentException(ucfirst($field).' must be greater than zero.');
        }

        return $minorUnits;
    }

    private function calculateBaseRent(int $length, int $width, int $rate): int
    {
        $areaInHundredths = $length * $width;
        $maximumBaseRent = 9_999_999_999;
        $maximumNumerator = ($maximumBaseRent * 10_000) + 4_999;
        if ($areaInHundredths > intdiv($maximumNumerator, $rate)) {
            throw new OverflowException('The calculated base rent exceeds the supported currency range.');
        }

        return $this->roundHalfUp($areaInHundredths * $rate, 1, 10_000);
    }

    private function roundHalfUp(int $amount, int $numerator, int $denominator): int
    {
        if ($amount > intdiv(PHP_INT_MAX, $numerator)) {
            throw new OverflowException('The calculated amount exceeds the supported currency range.');
        }

        $scaledAmount = $amount * $numerator;

        return intdiv($scaledAmount, $denominator)
            + (($scaledAmount % $denominator) >= intdiv($denominator + 1, 2) ? 1 : 0);
    }

    private function parseDate(string $date, string $field): DateTimeImmutable
    {
        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($parsedDate === false || $parsedDate->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException(ucfirst($field).' must be a valid ISO date (YYYY-MM-DD).');
        }

        return $parsedDate;
    }

    private function formatCentavos(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
