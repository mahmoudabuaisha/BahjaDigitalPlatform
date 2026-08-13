<?php

namespace App\Filament\Admin\Resources\Events\Tables;

use App\Enums\EventStatus;
use App\Filament\Admin\Resources\Events\Actions\EventActions;
use App\Models\Event;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('الفعالية')
                    ->searchable()
                    ->limit(35)
                    ->description(fn (Event $record): ?string => $record->shelterCenter?->name),
                TextColumn::make('team.name')
                    ->label('الفريق')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('area.name')
                    ->label('المنطقة')
                    ->toggleable(),
                TextColumn::make('start_date')
                    ->label('التاريخ')
                    ->date('Y/m/d')
                    ->sortable()
                    ->description(fn (Event $record): string => substr($record->start_time, 0, 5)),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                TextColumn::make('views_count')
                    ->label('المشاهدات')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('actual_children')
                    ->label('حضور الأطفال')
                    ->numeric()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                SelectFilter::make('area_id')
                    ->label('المنطقة')
                    ->relationship('area', 'name'),
                SelectFilter::make('team_id')
                    ->label('الفريق')
                    ->relationship('team', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category_id')
                    ->label('التصنيف')
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                EventActions::approve(),
                EventActions::reject(),
                ActionGroup::make([
                    ViewAction::make()->label('عرض'),
                    EditAction::make()->label('تعديل'),
                    EventActions::qrCode(),
                    DeleteAction::make()->label('حذف'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approveSelected')
                        ->label('اعتماد المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $approved = 0;

                            foreach ($records as $record) {
                                if ($record->status !== EventStatus::Pending) {
                                    continue;
                                }

                                $record->forceFill([
                                    'status' => EventStatus::Approved,
                                    'approved_by' => auth()->id(),
                                    'approved_at' => now(),
                                    'rejection_reason' => null,
                                ])->save();

                                $approved++;
                            }

                            Notification::make()
                                ->title("تم اعتماد {$approved} فعالية")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ]);
    }
}
