<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Resources\Users\Widgets\UserStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'إدارة المستخدمين';

    public function getSubheading(): ?string
    {
        return 'عرض وإدارة جميع المستخدمين في المنصّة.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('إضافة مستخدم')->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UserStats::class,
        ];
    }
}
