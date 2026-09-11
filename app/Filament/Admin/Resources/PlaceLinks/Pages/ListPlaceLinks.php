<?php

namespace App\Filament\Admin\Resources\PlaceLinks\Pages;

use App\Filament\Admin\Resources\PlaceLinks\PlaceLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlaceLinks extends ListRecords
{
    protected static string $resource = PlaceLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
