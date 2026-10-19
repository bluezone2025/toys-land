<?php

namespace App\Filament\Resources\AgeGroups\Pages;

use App\Filament\Resources\AgeGroups\AgeGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAgeGroups extends ManageRecords
{
    protected static string $resource = AgeGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
