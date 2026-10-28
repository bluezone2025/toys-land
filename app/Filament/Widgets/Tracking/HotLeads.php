<?php

namespace App\Filament\Widgets\Tracking;

use App\Filament\Resources\VisitorProfiles\VisitorProfileResource;
use App\Models\VisitorProfile;
use App\Support\WhatsApp;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Visitors with a cart or a started checkout and no order since: the people worth a message now. */
class HotLeads extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '🔥 عملاء محتملين ساخنين (عندهم سلة أو بدأوا الدفع ولم يطلبوا)';

    public function table(Table $table): Table
    {
        return $table
            ->query(VisitorProfile::query()->with('user')->where('cart_items', '>', 0)->where('last_seen_at', '>=', now()->subDays(3)))
            ->defaultSort('intent_score', 'desc')
            ->poll('30s')
            ->paginated([10, 25])
            ->emptyStateHeading('ما في سلات مفتوحة حالياً')
            ->recordUrl(fn (VisitorProfile $r) => VisitorProfileResource::getUrl('view', ['record' => $r]))
            ->columns([
                TextColumn::make('name')->label('العميل')->getStateUsing(fn (VisitorProfile $r) => $r->displayName())
                    ->description(fn (VisitorProfile $r) => $r->phone ?? $r->email),
                TextColumn::make('stage')->label('المرحلة')->badge()
                    ->formatStateUsing(fn ($state) => VisitorProfile::STAGES[$state] ?? $state)->color(fn ($state) => VisitorProfile::STAGE_COLORS[$state] ?? 'gray'),
                TextColumn::make('intent_score')->label('نية الشراء')->badge()->suffix('/100')
                    ->color(fn ($state) => $state >= 40 ? 'danger' : ($state >= 20 ? 'warning' : 'gray')),
                TextColumn::make('cart_value')->label('قيمة السلة')->formatStateUsing(fn ($state) => \App\Support\TrackingReports::money($state))->description(fn (VisitorProfile $r) => $r->cart_items.' قطعة'),
                TextColumn::make('last_source')->label('المصدر')->placeholder('مباشر')->description(fn (VisitorProfile $r) => $r->last_campaign),
                TextColumn::make('last_seen_at')->label('آخر ظهور')->since(),
            ])
            ->recordActions([
                Action::make('whatsapp')->label('واتساب')->icon(Heroicon::OutlinedChatBubbleLeftRight)->color('success')
                    ->visible(fn (VisitorProfile $r) => (bool) WhatsApp::normalize($r->phone))
                    ->url(fn (VisitorProfile $r) => 'https://wa.me/'.WhatsApp::normalize($r->phone).'?text='.urlencode('هلا '.($r->name ?? '').' 👋 لاحظنا إن عندك ألعاب في السلة بمتجر تويز لاند 🧸 تحب نساعدك تكمل طلبك؟ '.route('cart')), shouldOpenInNewTab: true),
            ]);
    }
}
