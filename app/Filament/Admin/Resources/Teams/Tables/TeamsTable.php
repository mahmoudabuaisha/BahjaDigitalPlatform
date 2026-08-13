<?php

namespace App\Filament\Admin\Resources\Teams\Tables;

use App\Models\Team;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(url('/images/team-placeholder.svg')),
                TextColumn::make('name')
                    ->label('الفريق')
                    ->searchable()
                    ->description(fn (Team $record): ?string => $record->contact_name),
                TextColumn::make('whatsapp_phone')
                    ->label('واتساب')
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->label('معتمد')
                    ->boolean(),
                TextColumn::make('events_count')
                    ->label('الفعاليات')
                    ->counts('events'),
                TextColumn::make('created_at')
                    ->label('انضم في')
                    ->date('Y/m/d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('activate')
                    ->label('اعتماد الفريق')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Team $record): bool => ! $record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('اعتماد الفريق')
                    ->modalDescription('سيتمكن مسؤول الفريق من الدخول للوحته ورفع الفعاليات. تواصلوا معه عبر واتساب لإبلاغه.')
                    ->action(function (Team $record): void {
                        $record->update(['is_active' => true]);

                        Notification::make()
                            ->title('تم اعتماد الفريق')
                            ->body($record->whatsapp_phone
                                ? 'أبلغوا الفريق عبر واتساب: '.$record->whatsapp_phone
                                : null)
                            ->success()
                            ->send();
                    }),
                ActionGroup::make([
                    EditAction::make()->label('تعديل'),
                    Action::make('deactivate')
                        ->label('إيقاف الفريق')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->visible(fn (Team $record): bool => $record->is_active)
                        ->requiresConfirmation()
                        ->action(fn (Team $record) => $record->update(['is_active' => false])),
                    DeleteAction::make()->label('حذف'),
                ]),
            ]);
    }
}
