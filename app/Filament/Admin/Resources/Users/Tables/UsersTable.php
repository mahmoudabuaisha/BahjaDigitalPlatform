<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Filament\InitialsAvatarProvider;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('team'))
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->getStateUsing(fn (User $record): string => app(InitialsAvatarProvider::class)->get($record)),
                TextColumn::make('name')
                    ->label('المستخدم')
                    ->weight('bold')
                    ->description(fn (User $record): string => $record->team?->name ?? $record->role->getLabel())
                    ->searchable(),
                TextColumn::make('role')
                    ->label('الدور')
                    ->badge()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('نُسخ البريد'),
                TextColumn::make('phone')
                    ->label('رقم الجوال')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('is_active')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشط' : 'موقوف')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->label('الدور')
                    ->options(UserRole::class),
                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('كل الحالات')
                    ->trueLabel('نشط')
                    ->falseLabel('موقوف'),
                SelectFilter::make('team_id')
                    ->label('الفريق')
                    ->relationship('team', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->label('تعديل'),
                    Action::make('toggleActive')
                        ->label(fn (User $record): string => $record->is_active ? 'إيقاف الحساب' : 'تفعيل الحساب')
                        ->icon(fn (User $record): string => $record->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                        ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        // إيقاف المشرف لحسابه يقفل عليه اللوحة
                        ->visible(fn (User $record): bool => $record->id !== auth()->id())
                        ->action(function (User $record): void {
                            $record->update(['is_active' => ! $record->is_active]);

                            AuditLog::record('user.toggled', $record,
                                after: ['is_active' => $record->is_active]);

                            Notification::make()
                                ->title($record->is_active ? 'فُعّل الحساب' : 'أُوقف الحساب')
                                ->success()
                                ->send();
                        }),
                    DeleteAction::make()
                        ->label('حذف')
                        ->visible(fn (User $record): bool => $record->id !== auth()->id()),
                ]),
            ]);
    }
}
