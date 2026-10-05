<?php

namespace App;

use App\Models\Bill;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class CollectionChart
{
    /** @return array{group: string, from: string, to: string, total: string, count: int, points: list<array{label: string, cents: int}>, maximum: int} */
    public function build(Builder $payments, string $group, ?string $from, ?string $to): array
    {
        $end = $to ? CarbonImmutable::parse($to) : CarbonImmutable::today();
        $start = $from ? CarbonImmutable::parse($from) : match ($group) {
            'daily' => $end->subDays(29),
            'weekly' => $end->startOfWeek(1)->subWeeks(11),
            'monthly' => $end->startOfMonth()->subMonths(11),
            'yearly' => $end->startOfYear()->subYears(4),
        };
        if ($start->gt($end)) {
            throw ValidationException::withMessages(['from' => 'The payment start date must be on or before the end date.']);
        }
        $points = [];
        for ($cursor = $this->bucket($start, $group); $cursor->lte($end); $cursor = $this->next($cursor, $group)) {
            if (count($points) >= 120) {
                throw ValidationException::withMessages(['from' => 'Choose a shorter date range or a larger grouping (maximum 120 chart periods).']);
            }
            $label = match ($group) {
                'daily' => $cursor->format('M d, Y'),
                'weekly' => 'Week of '.$cursor->format('M d, Y'),
                'monthly' => $cursor->format('M Y'),
                'yearly' => $cursor->format('Y'),
            };
            $points[$cursor->toDateString()] = ['label' => $label, 'cents' => 0];
        }
        $total = 0;
        $count = 0;
        $query = (clone $payments)->where('status', 'Paid')->whereNull('reversed_at')
            ->whereDate('paid_at', '>=', $start->toDateString())
            ->whereDate('paid_at', '<=', $end->toDateString());
        foreach ($query->select(['id', 'paid_at', 'amount'])->lazyById(500) as $payment) {
            $cents = Bill::cents($payment->amount);
            $key = $this->bucket(CarbonImmutable::instance($payment->paid_at), $group)->toDateString();
            $points[$key]['cents'] += $cents;
            $total += $cents;
            $count++;
        }

        return [
            'group' => $group, 'from' => $start->toDateString(), 'to' => $end->toDateString(),
            'total' => Bill::decimal($total), 'count' => $count,
            'points' => array_values($points), 'maximum' => max(array_column($points, 'cents')),
        ];
    }

    private function bucket(CarbonImmutable $date, string $group): CarbonImmutable
    {
        return match ($group) {
            'daily' => $date->startOfDay(),
            'weekly' => $date->startOfWeek(1),
            'monthly' => $date->startOfMonth(),
            'yearly' => $date->startOfYear(),
        };
    }

    private function next(CarbonImmutable $date, string $group): CarbonImmutable
    {
        return match ($group) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'monthly' => $date->addMonth(),
            'yearly' => $date->addYear(),
        };
    }
}
