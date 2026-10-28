<?php

namespace App\Filament\Resources\TrackingEvents;

use App\Filament\Resources\TrackingEvents\Pages\ListTrackingEvents;
use App\Filament\Resources\VisitorProfiles\VisitorProfileResource;
use App\Models\AnalyticsEvent;
use App\Support\Tracking;
use App\Support\TrackingReports;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Raw event log with each event's delivery result per ad platform (debugging the pixels / CAPI). */
class TrackingEventResource extends Resource
{
    protected static ?string $model = AnalyticsEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = 'سلوك العملاء';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'tracking-events';

    protected static ?string $modelLabel = 'حدث';

    protected static ?string $pluralModelLabel = 'سجل الأحداث والبيكسلات';

    public const DISPATCH = ['sent' => 'وصل للمنصات', 'pending' => 'بالانتظار', 'failed' => 'فشل', 'skipped' => 'لا يرسل'];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(AnalyticsEvent::query()->with(['visitor', 'product:id,name_ar']))
            ->defaultSort('id', 'desc')
            ->poll('15s')
            ->recordUrl(fn (AnalyticsEvent $r) => $r->visitor ? VisitorProfileResource::getUrl('view', ['record' => $r->visitor]) : null)
            ->columns([
                TextColumn::make('created_at')->label('الوقت')->dateTime('m/d h:i:s A', 'Asia/Kuwait')->sortable(),
                TextColumn::make('type')->label('الحدث')->formatStateUsing(fn ($state) => TrackingReports::label($state))->weight('bold')
                    ->description(fn (AnalyticsEvent $r) => $r->type),
                TextColumn::make('visitor')->label('الزائر')->getStateUsing(fn (AnalyticsEvent $r) => $r->visitor?->displayName() ?? '—'),
                TextColumn::make('product.name_ar')->label('المنتج')->placeholder('—')->limit(30),
                TextColumn::make('value')->label('القيمة')->formatStateUsing(fn ($state) => \App\Support\TrackingReports::money($state))->placeholder('—'),
                TextColumn::make('source')->label('المصدر')->badge()->formatStateUsing(fn ($state) => $state === 'browser' ? 'المتصفح' : 'السيرفر')
                    ->color(fn ($state) => $state === 'browser' ? 'info' : 'gray'),
                TextColumn::make('dispatch_status')->label('Conversions API')->badge()->placeholder('—')
                    ->formatStateUsing(fn ($state) => self::DISPATCH[$state] ?? $state)
                    ->color(fn ($state) => ['sent' => 'success', 'pending' => 'warning', 'failed' => 'danger'][$state] ?? 'gray'),
                TextColumn::make('delivery')->label('النتيجة لكل منصة')->wrap()->size('sm')
                    ->getStateUsing(fn (AnalyticsEvent $r) => $r->delivery ? collect($r->delivery)->map(fn ($s, $p) => $p.': '.($s === 'ok' ? '✓' : $s))->values()->all() : null)
                    ->listWithLineBreaks()->placeholder('—'),
                TextColumn::make('event_id')->label('event_id')->copyable()->size('sm')->color('gray')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('path')->label('الصفحة')->limit(40)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')->label('الحدث')->options(collect(Tracking::LABELS)->map(fn ($l, $k) => $l.' ('.$k.')')->all())->multiple(),
                SelectFilter::make('dispatch_status')->label('حالة الإرسال')->options(self::DISPATCH),
                SelectFilter::make('source')->label('المصدر')->options(['server' => 'السيرفر', 'browser' => 'المتصفح']),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListTrackingEvents::route('/')];
    }
}
