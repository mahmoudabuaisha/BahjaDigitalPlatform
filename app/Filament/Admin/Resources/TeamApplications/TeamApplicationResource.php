<?php

namespace App\Filament\Admin\Resources\TeamApplications;

use App\Enums\TeamApplicationStatus;
use App\Filament\Admin\Resources\TeamApplications\Pages\ListTeamApplications;
use App\Models\TeamApplication;
use App\Services\TeamApplicationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** طلبات انضمام الفرق (القسم 5.3): اعتماد أو رفض بسبب، ولا تشغيل قبل القرار */
class TeamApplicationResource extends Resource
{
    protected static ?string $model = TeamApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $modelLabel = 'طلب انضمام';

    protected static ?string $pluralModelLabel = 'طلبات الانضمام';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المحتوى';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = TeamApplication::where('status', TeamApplicationStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('team_name')
                    ->label('الفريق')
                    ->weight('bold')
                    ->description(fn (TeamApplication $record): string => $record->contact_name)
                    ->searchable(),
                TextColumn::make('contact_phone')
                    ->label('التواصل')
                    ->description(fn (TeamApplication $record): string => $record->contact_email)
                    ->copyable(),
                TextColumn::make('description')
                    ->label('النشاط')
                    ->limit(50)
                    ->wrap()
                    ->description(fn (TeamApplication $record): ?string => $record->geographic_scope),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('وصل')
                    ->since()
                    ->sortable(),
                TextColumn::make('decision_reason')
                    ->label('سبب القرار')
                    ->placeholder('—')
                    ->limit(35)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(TeamApplicationStatus::class)
                    ->default(TeamApplicationStatus::Pending->value),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TeamApplication $record): bool => $record->status === TeamApplicationStatus::Pending)
                    ->requiresConfirmation()
                    ->modalHeading('اعتماد الفريق')
                    ->modalDescription('يُنشأ الفريق وحساب مديره، وتظهر كلمة المرور مرة واحدة لتسليمها له عبر واتساب.')
                    ->action(function (TeamApplication $record): void {
                        $result = app(TeamApplicationService::class)->approve($record);

                        Notification::make()
                            ->title('اعتُمد فريق «'.$result['team']->name.'»')
                            ->body('بيانات الدخول — البريد: '.$result['manager']->email
                                .' | كلمة المرور: '.$result['password']
                                .' — سلِّموها للمسؤول الآن، فلن تظهر مرة أخرى.')
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (TeamApplication $record): bool => $record->status === TeamApplicationStatus::Pending)
                    ->modalHeading('رفض الطلب')
                    ->schema([
                        Textarea::make('reason')
                            ->label('سبب الرفض')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->action(function (TeamApplication $record, array $data): void {
                        app(TeamApplicationService::class)->reject($record, $data['reason']);

                        Notification::make()->title('رُفض الطلب')->warning()->send();
                    }),
            ])
            ->emptyStateHeading('لا طلبات انضمام')
            ->emptyStateDescription('حين يقدّم فريق طلباً من الموقع يظهر هنا.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeamApplications::route('/'),
        ];
    }
}
