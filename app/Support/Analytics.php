<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reporting queries shared by the admin dashboard and analytics widgets.
 */
class Analytics
{
    public const ACTIVE_MINUTES = 5;

    /** Orders that count as real sales (online paid, or cash on delivery not cancelled). */
    public static function sales(): Builder
    {
        return Order::query()
            ->where('status', '!=', 'cancelled')
            ->where(fn ($q) => $q->where('payment_status', 'paid')->orWhere('payment_method', 'cod'));
    }

    public static function activeVisitors(): int
    {
        return Visit::where('created_at', '>=', now()->subMinutes(self::ACTIVE_MINUTES))->distinct()->count('session_id');
    }

    public static function uniqueVisitors(Carbon $from, ?Carbon $to = null): int
    {
        return Visit::whereBetween('created_at', [$from, $to ?? now()])->distinct()->count('session_id');
    }

    /** @return array<string, float|int> date => value, filled for every day in range */
    public static function daily(Builder $query, string $aggregate, int $days): array
    {
        $rows = $query->clone()
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw("DATE(created_at) as d, {$aggregate} as v")
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('v', 'd');

        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $out[$date] = (float) ($rows[$date] ?? 0);
        }

        return $out;
    }

    /** @return array<string, int> */
    public static function countBy(string $column, int $days = 30, int $limit = 10, bool $sessions = true): array
    {
        return Visit::where('created_at', '>=', now()->subDays($days))
            ->whereNotNull($column)
            ->selectRaw("{$column} as k, ".($sessions ? 'COUNT(DISTINCT session_id)' : 'COUNT(*)').' as c')
            ->groupBy($column)->orderByDesc('c')->limit($limit)
            ->pluck('c', 'k')->map(fn ($v) => (int) $v)->all();
    }
}
