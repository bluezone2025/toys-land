<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'الطلب #'.$this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            OrderResource::statusAction(),
            Action::make('invoice')->label('طباعة الفاتورة')->icon('heroicon-o-printer')->color('gray')
                ->url(fn () => route('invoice.show', $this->record), shouldOpenInNewTab: true),
            Action::make('whatsapp')->label('مراسلة واتساب')->icon('heroicon-o-chat-bubble-left-right')->color('success')
                ->url(fn () => 'https://wa.me/'.preg_replace('/\D/', '', $this->record->phone).'?text='.urlencode('مرحباً '.$this->record->name.'، بخصوص طلبك #'.$this->record->number.' من تويز لاند'), shouldOpenInNewTab: true),
        ];
    }
}
