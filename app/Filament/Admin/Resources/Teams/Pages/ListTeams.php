<?php

namespace App\Filament\Admin\Resources\Teams\Pages;

use App\Filament\Admin\Resources\Teams\TeamResource;
use App\Models\Team;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTeams extends ListRecords
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('فريق جديد'),
        ];
    }

    public function getTabs(): array
    {
        $pendingCount = Team::where('is_active', false)->count();

        return [
            'active' => Tab::make('الفرق المعتمدة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            'pending' => Tab::make('طلبات جديدة')
                ->badge($pendingCount ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
            'all' => Tab::make('الكل'),
        ];
    }
}
