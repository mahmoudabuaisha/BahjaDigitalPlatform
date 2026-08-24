<?php

namespace App\Filament\Admin\Resources\AttendanceReports;

use App\Filament\Admin\Resources\AttendanceReports\Pages\ListAttendanceReports;
use App\Models\AttendanceReport;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * طابور التحقق من الحضور (القسم 12.1): أرقام الأثر الرسمية
 * لا تدخل التقارير إلا بعد تحقق الإدارة، وكل تصحيح مسجَّل بسببه.
 */
class AttendanceReportResource extends Resource
{
    protected static ?string $model = AttendanceReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $modelLabel = 'تقرير حضور';

    protected static ?string $pluralModelLabel = 'الحضور';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المحتوى';

    protected static ?int $navigationSort = 5;

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
        $unverified = AttendanceReport::whereNull('verified_at')->count();

        return $unverified > 0 ? (string) $unverified : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['event.team', 'submitter', 'verifier']))
            ->columns([
                TextColumn::make('event.title')
                    ->label('الفعالية')
                    ->weight('bold')
                    ->limit(32)
                    ->description(fn (AttendanceReport $record): ?string => $record->event?->team?->name)
                    ->searchable(),
                TextColumn::make('event.start_date')
                    ->label('التاريخ')
                    ->date('Y/m/d')
                    ->sortable(),
                TextColumn::make('children_actual')
                    ->label('الأطفال')
                    ->numeric()
                    ->description(fn (AttendanceReport $record): string => 'المتوقّع: '.($record->event?->expected_children ?? '—')),
                TextColumn::make('guardians_actual')
                    ->label('المرافقون')
                    ->numeric(),
                TextColumn::make('submitter.name')
                    ->label('سجّله')
                    ->placeholder('—'),
                TextColumn::make('verified_at')
                    ->label('التحقق')
                    ->badge()
                    ->formatStateUsing(fn (): string => 'متحقَّق')
                    ->color('success')
                    ->placeholder('بانتظار التحقق'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('verified_at')
                    ->label('التحقق')
                    ->nullable()
                    ->placeholder('الكل')
                    ->trueLabel('متحقَّق منها')
                    ->falseLabel('بانتظار التحقق')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('verified_at'),
                        false: fn ($query) => $query->whereNull('verified_at'),
                    ),
            ])
            ->recordActions([
                Action::make('verify')
                    ->label('تحقق')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (AttendanceReport $record): bool => $record->verified_at === null)
                    ->requiresConfirmation()
                    ->modalHeading('التحقق من الحضور')
                    ->modalDescription('بعد التحقق تدخل الأرقام في تقارير الأثر الرسمية.')
                    ->action(function (AttendanceReport $record): void {
                        $record->update(['verified_by' => auth()->id(), 'verified_at' => now()]);

                        AuditLog::record('attendance.verified', $record->event);

                        Notification::make()->title('تمّ التحقق')->success()->send();
                    }),
                Action::make('correct')
                    ->label('تصحيح')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->modalHeading('تصحيح إداري للحضور')
                    ->schema([
                        TextInput::make('children_actual')->label('عدد الأطفال')->numeric()->required()->minValue(0)->maxValue(5000),
                        TextInput::make('guardians_actual')->label('عدد المرافقين')->numeric()->required()->minValue(0)->maxValue(5000),
                        Textarea::make('reason')->label('سبب التصحيح (إلزامي ويُسجَّل)')->required()->maxLength(500)->rows(2),
                    ])
                    ->fillForm(fn (AttendanceReport $record): array => $record->only(['children_actual', 'guardians_actual']))
                    ->action(function (AttendanceReport $record, array $data): void {
                        $before = $record->only(['children_actual', 'guardians_actual']);

                        $record->update([
                            'children_actual' => $data['children_actual'],
                            'guardians_actual' => $data['guardians_actual'],
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);

                        $record->event?->forceFill([
                            'actual_children' => $data['children_actual'],
                            'actual_caregivers' => $data['guardians_actual'],
                        ])->save();

                        AuditLog::record('attendance.corrected', $record->event, $before,
                            ['children_actual' => $data['children_actual'], 'guardians_actual' => $data['guardians_actual']],
                            $data['reason']);

                        Notification::make()->title('صُحّح الحضور وسُجّل السبب')->success()->send();
                    }),
            ])
            ->emptyStateHeading('لا تقارير حضور بعد')
            ->emptyStateDescription('تظهر هنا فور تسجيل الفرق حضور فعالياتها المنتهية.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendanceReports::route('/'),
        ];
    }
}
