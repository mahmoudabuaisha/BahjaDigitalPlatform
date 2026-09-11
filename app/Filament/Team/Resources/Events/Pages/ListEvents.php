<?php

namespace App\Filament\Team\Resources\Events\Pages;

use App\Enums\EventStatus;
use App\Filament\Team\Resources\Events\EventResource;
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
        $needsAttendance = EventResource::getEloquentQuery()
            ->where('status', EventStatus::Completed)
            ->whereNull('actual_children')
            ->count();

        return [
            'all' => Tab::make('الكل'),
            'draft' => Tab::make('مسودات')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', EventStatus::Draft)),
            'pending' => Tab::make('بانتظار الاعتماد')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', EventStatus::Pending)),
            'approved' => Tab::make('معتمدة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', EventStatus::Approved)),
            'needs_attendance' => Tab::make('بانتظار تسجيل الحضور')
                ->badge($needsAttendance ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', EventStatus::Completed)
                    ->whereNull('actual_children')),
            'rejected' => Tab::make('مرفوضة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', EventStatus::Rejected)),
        ];
    }
}
