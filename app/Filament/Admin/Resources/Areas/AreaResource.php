<?php

namespace App\Filament\Admin\Resources\Areas;

use App\Filament\Admin\Resources\Areas\Pages\CreateArea;
use App\Filament\Admin\Resources\Areas\Pages\EditArea;
use App\Filament\Admin\Resources\Areas\Pages\ListAreas;
use App\Filament\Admin\Resources\Areas\RelationManagers\ShelterCentersRelationManager;
use App\Filament\Admin\Resources\Areas\Schemas\AreaForm;
use App\Filament\Admin\Resources\Areas\Tables\AreasTable;
use App\Models\Area;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AreaResource extends Resource
{
    protected static ?string $model = Area::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $modelLabel = 'منطقة';

    protected static ?string $pluralModelLabel = 'المناطق';

    protected static string|UnitEnum|null $navigationGroup = 'البنية الأساسية';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return AreaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AreasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ShelterCentersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAreas::route('/'),
            'create' => CreateArea::route('/create'),
            'edit' => EditArea::route('/{record}/edit'),
        ];
    }
}
