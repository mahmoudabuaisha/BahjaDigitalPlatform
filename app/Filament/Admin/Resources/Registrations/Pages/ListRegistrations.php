<?php

namespace App\Filament\Admin\Resources\Registrations\Pages;

use App\Filament\Admin\Resources\Registrations\RegistrationResource;
use Filament\Resources\Pages\ListRecords;

class ListRegistrations extends ListRecords
{
    protected static string $resource = RegistrationResource::class;
}
