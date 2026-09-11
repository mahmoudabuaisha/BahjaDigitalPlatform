<?php

namespace App\Filament\Admin\Resources\NeighbourhoodCalls;

use App\Filament\Admin\Resources\NeighbourhoodCalls\Pages\ListNeighbourhoodCalls;
use App\Filament\Admin\Resources\NeighbourhoodCalls\Tables\NeighbourhoodCallsTable;
use App\Models\NeighbourhoodCall;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * نداءات العائلات مفصّلة — للإدارة وحدها.
 * لا إنشاء ولا تعديل: النداء تكتبه العائلة، والإدارة تقرأ وتحذف.
 */
class NeighbourhoodCallResource extends Resource
{
    protected static ?string $model = NeighbourhoodCall::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static ?string $modelLabel = 'نداء حيّ';

    protected static ?string $pluralModelLabel = 'نداءات الأحياء';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المحتوى';

    protected static ?int $navigationSort = 6;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return NeighbourhoodCallsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNeighbourhoodCalls::route('/'),
        ];
    }
}
