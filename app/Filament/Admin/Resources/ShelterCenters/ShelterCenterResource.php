<?php

namespace App\Filament\Admin\Resources\ShelterCenters;

use App\Filament\Admin\Resources\ShelterCenters\Pages\CreateShelterCenter;
use App\Filament\Admin\Resources\ShelterCenters\Pages\EditShelterCenter;
use App\Filament\Admin\Resources\ShelterCenters\Pages\ListShelterCenters;
use App\Filament\Admin\Resources\ShelterCenters\Schemas\ShelterCenterForm;
use App\Filament\Admin\Resources\ShelterCenters\Tables\ShelterCentersTable;
use App\Models\ShelterCenter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ShelterCenterResource extends Resource
{
    protected static ?string $model = ShelterCenter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?string $modelLabel = 'مركز إيواء';

    protected static ?string $pluralModelLabel = 'مراكز الإيواء';

    protected static string|UnitEnum|null $navigationGroup = 'البنية الأساسية';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return ShelterCenterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShelterCentersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShelterCenters::route('/'),
            'create' => CreateShelterCenter::route('/create'),
            'edit' => EditShelterCenter::route('/{record}/edit'),
        ];
    }
}
