<?php

namespace App\Filament\Admin\Resources\ContactMessages\Tables;

use App\Filament\Admin\Resources\ContactMessages\Actions\ContactMessageActions;
use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('handledBy:id,name'))
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (ContactMessage $record): string => ContactMessageResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->state(fn (ContactMessage $record): string => $record->isHandled() ? 'handled' : 'new')
                    ->formatStateUsing(fn (string $state): string => $state === 'handled' ? 'تمّت المعالجة' : 'جديدة')
                    ->color(fn (string $state): string => $state === 'handled' ? 'success' : 'danger'),
                TextColumn::make('created_at')
                    ->label('وصلت في')
                    ->dateTime('Y/m/d H:i')
                    ->description(fn (ContactMessage $record): string => $record->created_at->diffForHumans())
                    ->sortable(),
                TextColumn::make('name')
                    ->label('المرسِل')
                    ->weight('bold')
                    ->searchable()
                    ->description(fn (ContactMessage $record): ?string => $record->phone ?: $record->email),
                TextColumn::make('subject')
                    ->label('الموضوع والرسالة')
                    // الموضوع الغائب لا يُخفي معاينة الرسالة تحته
                    ->state(fn (ContactMessage $record): string => $record->subject ?: 'بلا موضوع')
                    ->color(fn (ContactMessage $record): ?string => $record->subject ? null : 'gray')
                    ->searchable(['subject', 'message'])
                    ->limit(60)
                    ->description(fn (ContactMessage $record): string => Str::limit($record->message, 110))
                    ->wrap(),
                TextColumn::make('handledBy.name')
                    ->label('عالجها')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make()->label('فتح'),
                ActionGroup::make([
                    ContactMessageActions::replyWhatsapp(),
                    ContactMessageActions::replyEmail(),
                    ContactMessageActions::markHandled(),
                    ContactMessageActions::reopen(),
                    DeleteAction::make()->label('حذف'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ContactMessageActions::markHandledBulk(),
                    DeleteBulkAction::make()->label('حذف المحدَّد'),
                ]),
            ])
            ->emptyStateHeading('لا رسائل هنا')
            ->emptyStateDescription('كل ما يُكتب في صفحة «تواصلوا معنا» يصل إلى هذا الصندوق.');
    }
}
