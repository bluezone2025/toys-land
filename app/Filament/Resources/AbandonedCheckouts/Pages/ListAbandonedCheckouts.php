<?php

namespace App\Filament\Resources\AbandonedCheckouts\Pages;

use App\Filament\Resources\AbandonedCheckouts\AbandonedCheckoutResource;
use App\Filament\Widgets\Tracking\RecoveryStats;
use Filament\Resources\Pages\ListRecords;

class ListAbandonedCheckouts extends ListRecords
{
    protected static string $resource = AbandonedCheckoutResource::class;

    protected function getHeaderWidgets(): array
    {
        return [RecoveryStats::class];
    }

    public function getSubheading(): ?string
    {
        return 'أي عميل كتب رقمه أو إيميله في صفحة الدفع وما كمّل يظهر هنا فوراً. أرسل له واتساب برابط يرجّع سلته ويعبّي بياناته.';
    }
}
