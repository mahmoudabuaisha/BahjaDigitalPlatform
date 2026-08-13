<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('الاسم')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label('البريد الإلكتروني')
                                ->email()
                                ->required()
                                ->unique(table: User::class, ignoreRecord: true),
                        ]),
                        Grid::make(2)->schema([
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
                                ->helperText('عند التعديل: اتركها فارغة للإبقاء على الحالية'),
                        ]),
                        Grid::make(2)->schema([
                            // إنشاء حسابات "مدير عام" حكر على المدير العام (تحكمه UserPolicy أيضاً)
                            Select::make('role')
                                ->label('الدور')
                                ->options(
                                    auth()->user()?->isSuperAdmin()
                                        ? UserRole::class
                                        : [UserRole::TeamManager->value => UserRole::TeamManager->getLabel()]
                                )
                                ->default(UserRole::TeamManager)
                                ->required()
                                ->live(),
                            Select::make('team_id')
                                ->label('الفريق (لمسؤولي الفرق)')
                                ->relationship('team', 'name')
                                ->searchable()
                                ->preload()
                                ->visible(fn (Get $get): bool => $get('role') === UserRole::TeamManager->value
                                    || $get('role') === UserRole::TeamManager),
                        ]),
                        Toggle::make('is_active')
                            ->label('حساب نشط')
                            ->default(true),
                    ]),
            ]);
    }
}
