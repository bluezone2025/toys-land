<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep the analytics tables small: drop raw page views older than 6 months.
Schedule::call(function () {
    \App\Models\Visit::where('created_at', '<', now()->subMonths(6))->delete();
    \App\Models\AnalyticsEvent::where('created_at', '<', now()->subMonths(12))->delete();
    // List impressions are the bulk of the rows and only matter for recent reports.
    \App\Models\AnalyticsEvent::where('type', 'view_item_list')->where('created_at', '<', now()->subMonths(3))->delete();
    \App\Models\VisitorProfile::where('last_seen_at', '<', now()->subMonths(12))->where('orders_count', 0)->delete();
})->daily()->name('prune-analytics');

// Server-side conversions (Meta / TikTok / Snapchat / GA4), batched every minute.
Schedule::command('tracking:dispatch')->everyMinute()->withoutOverlapping(10);

Schedule::command('checkout:recover')->everyTenMinutes()->withoutOverlapping();

Schedule::command('cart:remind')->hourly();

Schedule::command('campaigns:run')->hourly();

// cPanel has no long-running workers: drain the mail queue every minute in small batches.
// Order emails ("default") always go before promotional ones ("newsletter").
Schedule::command('queue:work --queue=default,newsletter --stop-when-empty --max-jobs=40 --max-time=50 --tries=3')
    ->everyMinute()->withoutOverlapping(5);
