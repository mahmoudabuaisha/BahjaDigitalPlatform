<?php

namespace App\Filament\Admin\Resources\NewsletterSubscribers;

use App\Filament\Admin\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use App\Filament\Admin\Resources\NewsletterSubscribers\Tables\NewsletterSubscribersTable;
use App\Models\NewsletterSubscriber;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * مشتركو النشرة البريدية: من يصلهم أسبوعياً، ومن ينتظر التأكيد، ومن ألغى.
 * الاشتراك يأتي من الموقع وحده — الإدارة تقرأ وتحذف وترسل النشرة يدوياً.
 */
class NewsletterSubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static ?string $modelLabel = 'مشترك';

    protected static ?string $pluralModelLabel = 'مشتركو النشرة';

    protected static ?string $recordTitleAttribute = 'email';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المحتوى';

    protected static ?int $navigationSort = 7;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return NewsletterSubscribersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterSubscribers::route('/'),
        ];
    }
}
