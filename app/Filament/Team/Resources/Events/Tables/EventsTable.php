<?php

namespace App\Filament\Team\Resources\Events\Tables;

use App\Enums\EventStatus;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                    ->description(fn (Event $record): ?string => $record->shelterCenter?->name ?? $record->location_details),
                TextColumn::make('area.name')
                    ->label('المنطقة'),
                TextColumn::make('start_date')
                    ->label('التاريخ')
                    ->date('Y/m/d')
                    ->sortable()
                    ->description(fn (Event $record): string => substr($record->start_time, 0, 5)),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->description(fn (Event $record): ?string => $record->status === EventStatus::Rejected
                        ? $record->rejection_reason
                        : null),
                TextColumn::make('actual_children')
                    ->label('حضور الأطفال')
                    ->placeholder('لم يُسجَّل')
                    ->numeric(),
            ])
            ->defaultSort('start_date', 'desc')
            ->recordActions([
                Action::make('submit')
                    ->label('إرسال للاعتماد')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn (Event $record): bool => $record->status === EventStatus::Draft)
                    ->requiresConfirmation()
                    ->modalHeading('إرسال الفعالية للاعتماد')
                    ->modalDescription('ستراجعها إدارة بهجة وتظهر للعائلات بعد الاعتماد.')
                    ->action(function (Event $record): void {
                        $record->update(['status' => EventStatus::Pending]);

                        Notification::make()
                            ->title('أُرسلت الفعالية للاعتماد')
                            ->success()
                            ->send();
                    }),

                Action::make('attendance')
                    ->label('تسجيل الحضور')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->visible(fn (Event $record): bool => in_array($record->status, [EventStatus::Completed, EventStatus::Approved], true)
                        && $record->start_date->lte(today()))
                    ->modalHeading('تسجيل أعداد الحضور الفعلية')
                    ->modalDescription('هذه الأرقام تدخل في تقرير أثر المبادرة — سجّلوها بدقة.')
                    ->schema(fn (Event $record) => [
                        TextInput::make('actual_children')
                            ->label('عدد الأطفال الحاضرين')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(65000)
                            ->default($record->actual_children)
                            ->required(),
                        TextInput::make('actual_caregivers')
                            ->label('عدد المرافقين (أهالي)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(65000)
                            ->default($record->actual_caregivers),
                    ])
                    ->action(function (Event $record, array $data): void {
                        $record->update([
                            'actual_children' => $data['actual_children'],
                            'actual_caregivers' => $data['actual_caregivers'] ?? null,
                        ]);

                        Notification::make()
                            ->title('تم تسجيل الحضور — شكراً لكم')
                            ->success()
                            ->send();
                    }),

                EditAction::make()->label('تعديل'),

                Action::make('cancel')
                    ->label('إلغاء الفعالية')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Event $record): bool => in_array($record->status, [EventStatus::Pending, EventStatus::Approved], true)
                        && ! $record->start_date->isPast())
                    ->requiresConfirmation()
                    ->modalHeading('إلغاء الفعالية')
                    ->modalDescription('ستختفي الفعالية من الموقع العام. لا يمكن التراجع.')
                    ->action(function (Event $record): void {
                        $record->update(['status' => EventStatus::Cancelled]);

                        Notification::make()
                            ->title('أُلغيت الفعالية')
                            ->warning()
                            ->send();
                    }),

                DeleteAction::make()->label('حذف'),
            ])
            ->emptyStateHeading('لا فعاليات بعد')
            ->emptyStateDescription('أنشئوا أول فعالية لفريقكم وستظهر للعائلات بعد اعتماد الإدارة.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }
}
