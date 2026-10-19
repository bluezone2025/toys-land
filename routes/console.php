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
})->daily()->name('prune-analytics');
