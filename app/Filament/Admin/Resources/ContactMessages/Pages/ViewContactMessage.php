<?php

namespace App\Filament\Admin\Resources\ContactMessages\Pages;

use App\Filament\Admin\Resources\ContactMessages\Actions\ContactMessageActions;
use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    public function getTitle(): string
    {
        return 'رسالة من '.$this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            ContactMessageActions::replyWhatsapp(),
            ContactMessageActions::replyEmail(),
            ContactMessageActions::markHandled(),
            ContactMessageActions::reopen(),
            DeleteAction::make()->label('حذف'),
        ];
    }
}
