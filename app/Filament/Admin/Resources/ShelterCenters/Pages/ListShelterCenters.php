<?php

namespace App\Filament\Admin\Resources\ShelterCenters\Pages;

use App\Filament\Admin\Resources\ShelterCenters\ShelterCenterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShelterCenters extends ListRecords
{
    protected static string $resource = ShelterCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
