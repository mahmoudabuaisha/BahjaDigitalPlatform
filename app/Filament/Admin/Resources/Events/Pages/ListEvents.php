<?php

namespace App\Filament\Admin\Resources\Events\Pages;

use App\Enums\EventStatus;
use App\Filament\Admin\Resources\Events\EventResource;
use App\Models\Event;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('فعالية جديدة'),
        ];
    }

    public function getTabs(): array
    {
        $countByStatus = Event::query()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => Tab::make('بانتظار الاعتماد')
                ->badge($countByStatus[EventStatus::Pending->value] ?? null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', EventStatus::Pending)),
            'approved' => Tab::make('معتمدة')
                ->badge($countByStatus[EventStatus::Approved->value] ?? null)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', EventStatus::Approved)),
            'completed' => Tab::make('منفَّذة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', EventStatus::Completed)),
            'rejected' => Tab::make('مرفوضة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', EventStatus::Rejected)),
            'all' => Tab::make('الكل'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'pending';
    }
}
