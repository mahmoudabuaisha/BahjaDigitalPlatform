<?php

namespace App\Filament\Team\Resources\Events\Pages;

use App\Enums\EventStatus;
use App\Filament\Team\Resources\Events\EventResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected bool $saveAsDraft = false;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('إرسال للاعتماد')
                ->icon('heroicon-o-paper-airplane'),
            Action::make('saveAsDraft')
                ->label('حفظ كمسودة')
                ->color('gray')
                ->action(function (): void {
                    $this->saveAsDraft = true;
                    $this->create();
                }),
            $this->getCancelFormAction()->label('إلغاء'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()->team_id;
        $data['created_by'] = auth()->id();
        $data['status'] = $this->saveAsDraft ? EventStatus::Draft : EventStatus::Pending;

        return $data;
    }

    /** التكرار الأسبوعي: نسخ صفوف فعلية للأسابيع القادمة */
    protected function afterCreate(): void
    {
        $rawState = $this->form->getRawState();

        if (! ($rawState['repeat_weekly'] ?? false)) {
            return;
        }

        $weeks = max(2, min(8, (int) ($rawState['repeat_weeks'] ?? 2)));
        $record = $this->getRecord();

        for ($i = 1; $i < $weeks; $i++) {
            $copy = $record->replicate([
                'views_count',
                'approved_by',
                'approved_at',
                'actual_children',
                'actual_caregivers',
            ]);

            $copy->start_date = $record->start_date->copy()->addWeeks($i);
            $copy->save();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return $this->saveAsDraft
            ? 'حُفظت الفعالية كمسودة'
            : 'أُرسلت الفعالية للاعتماد';
    }
}
