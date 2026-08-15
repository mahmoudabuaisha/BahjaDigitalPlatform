<?php

namespace App\Filament\Team\Resources\Events;

use App\Filament\Team\Resources\Events\Pages\CreateEvent;
use App\Filament\Team\Resources\Events\Pages\EditEvent;
use App\Filament\Team\Resources\Events\Pages\ListEvents;
use App\Filament\Team\Resources\Events\RelationManagers\RegistrationsRelationManager;
use App\Filament\Team\Resources\Events\Schemas\EventForm;
use App\Filament\Team\Resources\Events\Tables\EventsTable;
use App\Models\Event;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $modelLabel = 'فعالية';

    protected static ?string $pluralModelLabel = 'فعالياتنا';

    /** كل استعلامات هذا الـ Resource محصورة بفريق المستخدم الحالي */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('team_id', auth()->user()->team_id);
    }

    /** @return array<class-string> */
    public static function getRelations(): array
    {
        return [
            RegistrationsRelationManager::class,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return EventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
