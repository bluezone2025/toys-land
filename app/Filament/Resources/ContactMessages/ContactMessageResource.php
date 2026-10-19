<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ManageContactMessages;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'رسالة';

    protected static ?string $pluralModelLabel = 'رسائل التواصل';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::where('is_read', false)->count();

        return $count ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('name')->label('الاسم'),
            TextEntry::make('phone')->label('النقال')->copyable(),
            TextEntry::make('subject')->label('الموضوع'),
            TextEntry::make('created_at')->label('التاريخ')->dateTime('Y/m/d h:i A', 'Asia/Kuwait'),
            TextEntry::make('message')->label('الرسالة')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            IconColumn::make('is_read')->label('')->boolean()->trueIcon(Heroicon::OutlinedEnvelopeOpen)->falseIcon(Heroicon::Envelope)->falseColor('warning'),
            TextColumn::make('name')->label('الاسم')->searchable()->description(fn (ContactMessage $r) => $r->phone),
            TextColumn::make('subject')->label('الموضوع'),
            TextColumn::make('message')->label('الرسالة')->limit(60),
            TextColumn::make('created_at')->label('التاريخ')->since()->sortable(),
        ])->filters([
            TernaryFilter::make('is_read')->label('مقروءة'),
        ])->recordActions([
            ViewAction::make()->after(fn (ContactMessage $r) => $r->update(['is_read' => true])),
            Action::make('whatsapp')->label('رد واتساب')->icon(Heroicon::OutlinedChatBubbleLeftRight)->color('success')
                ->url(fn (ContactMessage $r) => 'https://wa.me/'.preg_replace('/\D/', '', $r->phone), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ])->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageContactMessages::route('/')];
    }
}
