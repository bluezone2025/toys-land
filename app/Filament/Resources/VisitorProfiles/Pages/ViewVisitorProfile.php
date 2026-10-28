<?php

namespace App\Filament\Resources\VisitorProfiles\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\VisitorProfiles\VisitorProfileResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewVisitorProfile extends ViewRecord
{
    protected static string $resource = VisitorProfileResource::class;

    protected ?string $pollingInterval = '15s';

    public function getTitle(): string
    {
        return 'رحلة: '.$this->record->displayName();
    }

    protected function getHeaderActions(): array
    {
        $order = $this->record->orders()->latest('id')->first();

        return [
            Action::make('whatsapp')->label('مراسلة واتساب')->icon('heroicon-o-chat-bubble-left-right')->color('success')
                ->visible(fn () => (bool) VisitorProfileResource::whatsappUrl($this->record))
                ->url(fn () => VisitorProfileResource::whatsappUrl($this->record), shouldOpenInNewTab: true),
            Action::make('order')->label('آخر طلب #'.$order?->number)->icon('heroicon-o-shopping-bag')->color('gray')
                ->visible((bool) $order)
                ->url(fn () => $order ? OrderResource::getUrl('view', ['record' => $order]) : null),
        ];
    }
}
