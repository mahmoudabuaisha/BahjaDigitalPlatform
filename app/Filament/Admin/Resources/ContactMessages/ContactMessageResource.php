<?php

namespace App\Filament\Admin\Resources\ContactMessages;

use App\Filament\Admin\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Admin\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Admin\Resources\ContactMessages\Schemas\ContactMessageInfolist;
use App\Filament\Admin\Resources\ContactMessages\Tables\ContactMessagesTable;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * صندوق رسائل «تواصلوا معنا»: الرسالة كاملة مع وسيلة الردّ، وعدّاد الجديد
 * منها على القائمة. لا إنشاء ولا تعديل — الرسالة تكتبها العائلة كما هي.
 */
class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $modelLabel = 'رسالة';

    protected static ?string $pluralModelLabel = 'رسائل التواصل';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المحتوى';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $open = ContactMessage::open()->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'رسائل جديدة بانتظار الردّ';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactMessageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactMessagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }
}
