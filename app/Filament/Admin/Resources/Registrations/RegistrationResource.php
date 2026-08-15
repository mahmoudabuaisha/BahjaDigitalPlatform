<?php

namespace App\Filament\Admin\Resources\Registrations;

use App\Enums\RegistrationStatus;
use App\Filament\Admin\Resources\Registrations\Pages\ListRegistrations;
use App\Filament\Admin\Resources\Registrations\Tables\RegistrationsTable;
use App\Models\Registration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RegistrationResource extends Resource
{
    protected static ?string $model = Registration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'تسجيل';

    protected static ?string $pluralModelLabel = 'التسجيلات';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المحتوى';

    protected static ?int $navigationSort = 2;

    // الحجوزات تُنشأ من الموقع ويردّ عليها الفريق المنظِّم — الإدارة تتابع وتحذف
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Registration::where('status', RegistrationStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function table(Table $table): Table
    {
        return RegistrationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegistrations::route('/'),
        ];
    }
}
