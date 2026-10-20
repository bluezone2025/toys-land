<?php

namespace App\Filament\Resources\GuestCustomers\Pages;

use App\Filament\Resources\GuestCustomers\GuestCustomerResource;
use Filament\Resources\Pages\ListRecords;

class ListGuestCustomers extends ListRecords
{
    protected static string $resource = GuestCustomerResource::class;
}
