<?php

namespace App\Filament\Admin\Resources\PlaceLinks\Pages;

use App\Filament\Admin\Resources\PlaceLinks\PlaceLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlaceLink extends EditRecord
{
    protected static string $resource = PlaceLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
