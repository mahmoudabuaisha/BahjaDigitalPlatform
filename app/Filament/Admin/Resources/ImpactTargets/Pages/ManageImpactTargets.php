<?php

namespace App\Filament\Admin\Resources\ImpactTargets\Pages;

use App\Filament\Admin\Resources\ImpactTargets\ImpactTargetResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Table;

class ManageImpactTargets extends ManageRecords
{
    protected static string $resource = ImpactTargetResource::class;

    public function getSubheading(): ?string
    {
        return 'التقدّم يُحسب من الحضور المتحقَّق منه فقط — لا من الأعداد المتوقّعة.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('هدف جديد'),
        ];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)->recordActions([
            EditAction::make()->label('تعديل'),
            DeleteAction::make()->label('حذف'),
        ]);
    }
}
