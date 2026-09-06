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
use Illuminate\Support\HtmlString;
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
                TextColumn::make('org_type')
                    ->label('النوع والمحافظة')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state?->label() ?? '—')
                    ->description(fn (TeamApplication $record): ?string => $record->area?->name),
                TextColumn::make('contact_phone')
                    ->label('واتساب')
                    ->description(fn (TeamApplication $record): string => $record->contact_email)
                    ->copyable(),
                TextColumn::make('volunteers_count')
                    ->label('القدرات')
                    ->formatStateUsing(fn (TeamApplication $record): string => ($record->volunteers_count ?? '—').' متطوعاً · '.($record->capacity_per_event ?? '—').' طفلاً/نشاط')
                    ->description(fn (TeamApplication $record): ?string => implode('، ', array_slice($record->activityLabels(), 0, 3)) ?: null),
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
                Action::make('details')
                    ->label('الملف الكامل')
                    ->icon('heroicon-o-identification')
                    ->color('gray')
                    ->modalHeading(fn (TeamApplication $record): string => 'طلب «'.$record->team_name.'»')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(function (TeamApplication $record): HtmlString {
                        $rows = [
                            'نوع الجهة' => $record->org_type?->label(),
                            'المحافظة' => $record->area?->name,
                            'المقر / التواجد' => $record->base_location,
                            'مسؤول الميدان' => $record->contact_name,
                            'واتساب' => $record->contact_phone,
                            'هاتف الطوارئ' => $record->emergency_phone,
                            'البريد الرسمي' => $record->contact_email,
                            'وصف النشاط' => $record->description,
                            'محافظات التغطية' => implode('، ', $record->coverageAreaNames()) ?: null,
                            'مراكز / مخيمات' => $record->geographic_scope,
                            'الأنشطة المتقنة' => implode('، ', $record->activityLabels()) ?: null,
                            'عدد المتطوعين' => $record->volunteers_count,
                            'الاستيعاب / نشاط' => $record->capacity_per_event ? $record->capacity_per_event.' طفلاً' : null,
                            'تعهد سلامة الأطفال' => $record->pledge_accepted_at
                                ? 'مقبول — '.$record->pledge_accepted_at->translatedFormat('j F Y H:i')
                                : 'الصيغة القديمة للنموذج (قبل إضافة الختم الزمني)',
                        ];

                        $html = '<table style="width:100%;font-size:.9rem;border-collapse:collapse">';

                        foreach ($rows as $label => $value) {
                            $html .= '<tr style="border-bottom:1px solid rgba(128,128,128,.15)">'
                                .'<td style="padding:.5rem .25rem;font-weight:600;white-space:nowrap;vertical-align:top">'.e($label).'</td>'
                                .'<td style="padding:.5rem .25rem">'.e($value ?? '—').'</td></tr>';
                        }

                        return new HtmlString($html.'</table>');
                    }),
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
