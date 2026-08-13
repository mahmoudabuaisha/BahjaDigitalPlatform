<?php

namespace App\Filament\Admin\Resources\Events\Pages;

use App\Filament\Admin\Resources\Events\Actions\EventActions;
use App\Filament\Admin\Resources\Events\EventResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EventActions::approve(),
            EventActions::reject(),
            EventActions::qrCode(),
            EditAction::make()->label('تعديل'),
        ];
    }
}
