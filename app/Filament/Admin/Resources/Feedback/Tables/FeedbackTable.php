<?php

namespace App\Filament\Admin\Resources\Feedback\Tables;

use App\Enums\FeedbackSource;
use App\Models\Feedback;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FeedbackTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rating')
                    ->label('التقييم')
                    ->formatStateUsing(fn (?int $state): string => $state
                        ? str_repeat('★', $state).str_repeat('☆', 5 - $state)
                        : '—')
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('source')
                    ->label('المصدر')
                    ->badge(),
                TextColumn::make('event.title')
                    ->label('الفعالية')
                    ->placeholder('تقييم عام')
                    ->limit(30),
                TextColumn::make('message')
                    ->label('الرسالة')
                    ->limit(50)
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('contact_name')
                    ->label('الاسم')
                    ->placeholder('—')
                    ->description(fn (Feedback $record): ?string => $record->contact_phone),
                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('source')
                    ->label('المصدر')
                    ->options(FeedbackSource::class),
                SelectFilter::make('rating')
                    ->label('التقييم')
                    ->options([5 => '★★★★★', 4 => '★★★★', 3 => '★★★', 2 => '★★', 1 => '★']),
            ])
            ->recordActions([
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ]);
    }
}
