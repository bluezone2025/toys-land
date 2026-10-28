<?php

namespace App\Filament\Widgets\Tracking;

use App\Support\TrackingReports;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformHealth extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected ?string $heading = 'حالة الربط مع المنصات الإعلانية (آخر 24 ساعة)';

    public const NAMES = ['meta' => 'Meta (فيسبوك وإنستغرام)', 'tiktok' => 'TikTok', 'snap' => 'Snapchat', 'ga4' => 'Google Analytics 4'];

    protected function getStats(): array
    {
        $health = TrackingReports::platformHealth();
        if (! $health) {
            return [Stat::make('الإرسال من السيرفر غير مفعّل', '—')->description('أضف مفاتيح Conversions API من الإعدادات ← التتبع والبيكسلات')->color('gray')];
        }

        return collect($health)->map(fn ($h, $platform) => Stat::make(self::NAMES[$platform] ?? $platform, number_format($h['ok']).' حدث وصل')
            ->description(($h['failed'] ? $h['failed'].' فشل · ' : '').($h['pending'] ? $h['pending'].' بالانتظار' : ($h['failed'] ? '' : 'كل شي تمام ✅')))
            ->color($h['failed'] ? 'danger' : 'success'))->values()->all();
    }
}
