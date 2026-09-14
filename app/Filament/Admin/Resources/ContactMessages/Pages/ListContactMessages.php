<?php

namespace App\Filament\Admin\Resources\ContactMessages\Pages;

use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;

    public function getTabs(): array
    {
        $openCount = ContactMessage::open()->count();

        return [
            'open' => Tab::make('جديدة')
                ->badge($openCount ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->open()),
            'handled' => Tab::make('تمّت معالجتها')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('handled_at')),
            'all' => Tab::make('الكل'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'open';
    }
}
