<?php

namespace App\Filament\Admin\Resources\Teams\RelationManagers;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'حسابات المسؤولين';

    protected static ?string $modelLabel = 'حساب';

    protected static ?string $pluralModelLabel = 'الحسابات';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('البريد الإلكتروني (للدخول)')
                    ->email()
                    ->required()
                    ->unique(table: User::class, ignoreRecord: true),
                TextInput::make('phone')
                    ->label('الجوال')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('password')
                    ->label('كلمة المرور')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->minLength(8)
                    ->helperText('عند التعديل: اتركها فارغة للإبقاء على كلمة المرور الحالية'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('الاسم'),
                TextColumn::make('email')->label('البريد'),
                TextColumn::make('phone')->label('الجوال')->placeholder('—'),
                IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة مسؤول')
                    ->mutateDataUsing(function (array $data): array {
                        $data['role'] = UserRole::TeamManager;
                        $data['is_active'] = true;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
                Action::make('toggleActive')
                    ->label(fn (User $record): string => $record->is_active ? 'تعطيل' : 'تفعيل')
                    ->icon('heroicon-o-power')
                    ->color(fn (User $record): string => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->update(['is_active' => ! $record->is_active]);

                        Notification::make()
                            ->title($record->is_active ? 'تم تفعيل الحساب' : 'تم تعطيل الحساب')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}
