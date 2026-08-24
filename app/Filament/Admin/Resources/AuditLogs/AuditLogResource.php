<?php

namespace App\Filament\Admin\Resources\AuditLogs;

use App\Filament\Admin\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * السجل الرقابي (القسم 15.7) — قراءة فقط: لا إنشاء ولا تعديل ولا حذف
 * من اللوحة، فالسجل حجّة عند التدقيق.
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static ?string $modelLabel = 'سجل تدقيق';

    protected static ?string $pluralModelLabel = 'سجل التدقيق';

    protected static string|UnitEnum|null $navigationGroup = 'النظام';

    protected static ?int $navigationSort = 20;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('actor'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('الوقت')
                    ->dateTime('Y/m/d H:i:s')
                    ->sortable(),
                TextColumn::make('action')
                    ->label('العملية')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AuditLog::actionLabel($state))
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'approved') || str_contains($state, 'verified') => 'success',
                        str_contains($state, 'rejected') || str_contains($state, 'cancelled') || str_contains($state, 'suspended') || str_contains($state, 'deleted') => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('actor.name')
                    ->label('من قام بها')
                    ->placeholder('النظام'),
                TextColumn::make('subject_type')
                    ->label('على')
                    ->formatStateUsing(fn (?string $state, AuditLog $record): string => $state
                        ? class_basename($state).' #'.$record->subject_id
                        : '—'),
                TextColumn::make('reason')
                    ->label('السبب')
                    ->placeholder('—')
                    ->limit(40)
                    ->wrap(),
                TextColumn::make('after')
                    ->label('التغيير')
                    ->formatStateUsing(fn (AuditLog $record): string => collect($record->after ?? [])
                        ->map(fn ($value, $key) => $key.': '.(is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE)))
                        ->implode(' | ') ?: '—')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('action')
                    ->label('العملية')
                    ->options(fn (): array => AuditLog::query()
                        ->distinct()
                        ->pluck('action')
                        ->mapWithKeys(fn (string $action) => [$action => AuditLog::actionLabel($action)])
                        ->all()),
            ])
            ->emptyStateHeading('لا سجلات بعد')
            ->emptyStateDescription('تُسجَّل هنا قرارات الاعتماد والرفض والإلغاء والتصحيح تلقائياً.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
