<?php

namespace App\Filament\Admin\Resources\PlaceLinks;

use App\Filament\Admin\Resources\PlaceLinks\Pages\CreatePlaceLink;
use App\Filament\Admin\Resources\PlaceLinks\Pages\EditPlaceLink;
use App\Filament\Admin\Resources\PlaceLinks\Pages\ListPlaceLinks;
use App\Filament\Admin\Resources\PlaceLinks\Schemas\PlaceLinkForm;
use App\Filament\Admin\Resources\PlaceLinks\Tables\PlaceLinksTable;
use App\Models\PlaceLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PlaceLinkResource extends Resource
{
    protected static ?string $model = PlaceLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $modelLabel = 'مسافة بين مكانين';

    protected static ?string $pluralModelLabel = 'المسافات بين الأماكن';

    protected static string|UnitEnum|null $navigationGroup = 'البنية الأساسية';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return PlaceLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlaceLinksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlaceLinks::route('/'),
            'create' => CreatePlaceLink::route('/create'),
            'edit' => EditPlaceLink::route('/{record}/edit'),
        ];
    }
}
