<?php

namespace App\Filament\Team\Resources\Events\Pages;

use App\Enums\EventStatus;
use App\Filament\Team\Resources\Events\EventResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('حذف'),
        ];
    }

    /** فعالية مرفوضة عُدّلت تعود تلقائياً لمسار الاعتماد */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->getRecord()->status === EventStatus::Rejected) {
            $data['status'] = EventStatus::Pending;
            $data['rejection_reason'] = null;
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        $status = $this->getRecord()->status;

        if ($status === EventStatus::Pending) {
            return Notification::make()
                ->title('حُفظت التعديلات وأُعيدت الفعالية لمراجعة الإدارة')
                ->success();
        }

        return Notification::make()
            ->title('حُفظت التعديلات')
            ->success();
    }
}
