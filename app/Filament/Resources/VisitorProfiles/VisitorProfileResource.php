<?php

namespace App\Filament\Resources\VisitorProfiles;

use App\Filament\Resources\VisitorProfiles\Pages\ListVisitorProfiles;
use App\Filament\Resources\VisitorProfiles\Pages\ViewVisitorProfile;
use App\Models\VisitorProfile;
use App\Support\WhatsApp;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class VisitorProfileResource extends Resource
{
    protected static ?string $model = VisitorProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'سلوك العملاء';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'visitors';

    protected static ?string $modelLabel = 'زائر';

    protected static ?string $pluralModelLabel = 'رحلات الزوار';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone', 'email'];
    }

    public static function whatsappUrl(VisitorProfile $r): ?string
    {
        $phone = WhatsApp::normalize($r->phone);

        return $phone ? 'https://wa.me/'.$phone.'?text='.urlencode('هلا '.($r->name ?? '').' 👋 معاك تويز لاند 🧸') : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_seen_at', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('name')->label('الزائر')->getStateUsing(fn (VisitorProfile $r) => $r->displayName())
                    ->description(fn (VisitorProfile $r) => $r->phone ?? $r->email)->searchable(['name', 'phone', 'email'])->weight('bold'),
                TextColumn::make('stage')->label('المرحلة')->badge()->sortable()
                    ->formatStateUsing(fn ($state) => VisitorProfile::STAGES[$state] ?? $state)->color(fn ($state) => VisitorProfile::STAGE_COLORS[$state] ?? 'gray'),
                TextColumn::make('intent_score')->label('نية الشراء')->badge()->suffix('/100')->sortable()
                    ->color(fn ($state) => $state >= 40 ? 'danger' : ($state >= 20 ? 'warning' : 'gray')),
                TextColumn::make('first_source')->label('جاء من')->placeholder('مباشر')
                    ->description(fn (VisitorProfile $r) => collect([$r->first_medium, $r->first_campaign])->filter()->implode(' / ') ?: null),
                TextColumn::make('sessions_count')->label('زيارات')->sortable()->description(fn (VisitorProfile $r) => $r->page_views.' صفحة'),
                TextColumn::make('cart_value')->label('السلة')->formatStateUsing(fn ($state) => \App\Support\TrackingReports::money($state))->sortable()->placeholder('—'),
                TextColumn::make('total_spent')->label('مشترياته')->formatStateUsing(fn ($state) => \App\Support\TrackingReports::money($state))->sortable()->description(fn (VisitorProfile $r) => $r->orders_count ? $r->orders_count.' طلب' : null),
                TextColumn::make('device')->label('الجهاز')->formatStateUsing(fn ($state) => ['mobile' => '📱', 'tablet' => '📲', 'desktop' => '💻'][$state] ?? $state)
                    ->description(fn (VisitorProfile $r) => $r->country),
                TextColumn::make('last_seen_at')->label('آخر ظهور')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('stage')->label('المرحلة')->options(VisitorProfile::STAGES)->multiple(),
                TernaryFilter::make('identified')->label('معروف (رقم أو إيميل)')
                    ->queries(true: fn (Builder $q) => $q->where(fn ($q) => $q->whereNotNull('phone')->orWhereNotNull('email')),
                        false: fn (Builder $q) => $q->whereNull('phone')->whereNull('email')),
                TernaryFilter::make('has_cart')->label('عنده سلة مفتوحة')
                    ->queries(true: fn (Builder $q) => $q->where('cart_items', '>', 0), false: fn (Builder $q) => $q->where('cart_items', 0)),
                SelectFilter::make('first_source')->label('المصدر')->options(fn () => VisitorProfile::whereNotNull('first_source')->distinct()->orderBy('first_source')->limit(100)->pluck('first_source', 'first_source')),
                Filter::make('today')->label('نشطين اليوم')->query(fn (Builder $q) => $q->where('last_seen_at', '>=', now()->startOfDay())),
            ])
            ->recordActions([
                ViewAction::make()->label('الرحلة'),
                Action::make('whatsapp')->label('واتساب')->icon(Heroicon::OutlinedChatBubbleLeftRight)->color('success')
                    ->visible(fn (VisitorProfile $r) => (bool) static::whatsappUrl($r))
                    ->url(fn (VisitorProfile $r) => static::whatsappUrl($r), shouldOpenInNewTab: true),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $money = fn ($v) => number_format((float) $v, 3).' د.ك';

        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make('الهوية')->columnSpan(1)->schema([
                    TextEntry::make('display')->label('الاسم')->getStateUsing(fn (VisitorProfile $r) => $r->displayName())->weight('bold'),
                    TextEntry::make('phone')->label('النقال')->placeholder('—')->copyable()->url(fn (VisitorProfile $r) => static::whatsappUrl($r), shouldOpenInNewTab: true),
                    TextEntry::make('email')->label('البريد')->placeholder('—')->copyable(),
                    TextEntry::make('user.name')->label('حساب مسجّل')->placeholder('بدون حساب'),
                    TextEntry::make('device')->label('الجهاز')->getStateUsing(fn (VisitorProfile $r) => collect([$r->device, $r->platform, $r->browser, $r->country, $r->locale])->filter()->implode(' · ')),
                ]),
                Section::make('من وين جاء')->columnSpan(1)->schema([
                    TextEntry::make('first')->label('أول زيارة')->getStateUsing(fn (VisitorProfile $r) => collect([$r->first_source ?? 'مباشر', $r->first_medium, $r->first_campaign])->filter()->implode(' / ')),
                    TextEntry::make('first_referrer')->label('الموقع المحيل')->placeholder('—'),
                    TextEntry::make('landing_page')->label('صفحة الهبوط')->placeholder('—'),
                    TextEntry::make('last')->label('آخر مصدر')->getStateUsing(fn (VisitorProfile $r) => collect([$r->last_source ?? 'مباشر', $r->last_medium, $r->last_campaign])->filter()->implode(' / ')),
                    TextEntry::make('first_seen_at')->label('أول ظهور')->dateTime('Y/m/d h:i A', 'Asia/Kuwait'),
                ]),
                Section::make('التفاعل والشراء')->columnSpan(1)->columns(2)->schema([
                    TextEntry::make('stage')->label('المرحلة')->badge()->formatStateUsing(fn ($state) => VisitorProfile::STAGES[$state] ?? $state)->color(fn ($state) => VisitorProfile::STAGE_COLORS[$state] ?? 'gray'),
                    TextEntry::make('intent_score')->label('نية الشراء')->suffix('/100')->badge()->color(fn ($state) => $state >= 40 ? 'danger' : ($state >= 20 ? 'warning' : 'gray')),
                    TextEntry::make('sessions_count')->label('زيارات'),
                    TextEntry::make('page_views')->label('صفحات'),
                    TextEntry::make('products_viewed')->label('منتجات شافها'),
                    TextEntry::make('events_count')->label('تفاعلات'),
                    TextEntry::make('cart_value')->label('السلة الحالية')->formatStateUsing($money)->belowContent(fn (VisitorProfile $r) => $r->cart_items.' قطعة'),
                    TextEntry::make('total_spent')->label('إجمالي مشترياته')->formatStateUsing($money)->belowContent(fn (VisitorProfile $r) => $r->orders_count.' طلب')->color('success'),
                    TextEntry::make('last_seen_at')->label('آخر ظهور')->since()->columnSpanFull(),
                ]),
            ]),
            Section::make('رحلة الزائر خطوة بخطوة')->description('كل صفحة زارها وكل حركة سواها، الأحدث أولاً')->columnSpanFull()->schema([
                ViewEntry::make('timeline')->hiddenLabel()->view('filament.visitor-timeline'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisitorProfiles::route('/'),
            'view' => ViewVisitorProfile::route('/{record}'),
        ];
    }
}
