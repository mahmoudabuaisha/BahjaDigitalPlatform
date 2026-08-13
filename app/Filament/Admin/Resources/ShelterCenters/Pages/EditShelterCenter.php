<?php

namespace App\Filament\Admin\Resources\ShelterCenters\Pages;

use App\Filament\Admin\Resources\ShelterCenters\ShelterCenterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShelterCenter extends EditRecord
{
    protected static string $resource = ShelterCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
