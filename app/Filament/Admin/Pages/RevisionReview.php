<?php

namespace App\Filament\Admin\Pages;

use App\Enums\RevisionStatus;
use App\Models\Area;
use App\Models\Category;
use App\Models\EventRevision;
use App\Models\ShelterCenter;
use App\Services\RevisionService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * صندوق مراجعة التعديلات (القسم 12.1): فعالية منشورة أرسل فريقها
 * نسخة معدَّلة — تُعرض «قبل/بعد» ثم تُعتمد فتحلّ محل المنشورة، أو تُرفض بسبب.
 */
class RevisionReview extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static ?string $title = 'مراجعة التعديلات';

    protected static ?string $navigationLabel = 'مراجعة التعديلات';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المحتوى';

    protected static ?string $navigationParentItem = 'الفعاليات';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.admin.pages.revision-review';

    private const FIELD_LABELS = [
        'title' => 'اسم الفعالية',
        'description' => 'الوصف',
        'location_details' => 'العنوان التفصيلي',
        'directions' => 'كيف تصلون؟',
        'start_date' => 'التاريخ',
        'start_time' => 'وقت البداية',
        'end_time' => 'وقت النهاية',
        'category_id' => 'الفئة',
        'area_id' => 'المحافظة',
        'shelter_center_id' => 'مركز الإيواء',
        'image_path' => 'الصورة',
    ];

    public static function getNavigationBadge(): ?string
    {
        $pending = EventRevision::pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public function getSubheading(): ?string
    {
        return 'تعديلات الفرق على فعاليات منشورة — تبقى النسخة المنشورة ظاهرة حتى الاعتماد.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(EventRevision::query()->with(['event.team', 'submitter']))
            ->columns([
                TextColumn::make('event.title')
                    ->label('الفعالية')
                    ->weight('bold')
                    ->limit(34)
                    ->searchable(),
                TextColumn::make('event.team.name')
                    ->label('الفريق')
                    ->description(fn (EventRevision $record): ?string => $record->submitter?->name),
                TextColumn::make('version')
                    ->label('النسخة')
                    ->formatStateUsing(fn (int $state): string => 'v'.$state),
                TextColumn::make('payload')
                    ->label('الحقول المتغيّرة')
                    ->formatStateUsing(fn (EventRevision $record): string => collect($record->changedFields())
                        ->keys()
                        ->map(fn (string $field): string => self::FIELD_LABELS[$field] ?? $field)
                        ->implode('، ') ?: '—'),
                TextColumn::make('created_at')
                    ->label('أُرسلت')
                    ->since()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(RevisionStatus::class)
                    ->default(RevisionStatus::Pending->value),
            ])
            ->recordActions([
                Action::make('diff')
                    ->label('قبل / بعد')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (EventRevision $record): string => 'التعديل المقترح — '.$record->event->title)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(fn (EventRevision $record): HtmlString => $this->renderDiff($record)),
                Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (EventRevision $record): bool => $record->status === RevisionStatus::Pending)
                    ->requiresConfirmation()
                    ->modalHeading('اعتماد التعديل')
                    ->modalDescription('ستحلّ النسخة الجديدة محل المنشورة فوراً، ويصل إشعار للفريق وللعائلات المسجَّلة عند تغيّر الموعد.')
                    ->action(function (EventRevision $record): void {
                        app(RevisionService::class)->approve($record);

                        Notification::make()->title('اعتُمد التعديل وصار منشوراً')->success()->send();
                    }),
                Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (EventRevision $record): bool => $record->status === RevisionStatus::Pending)
                    ->modalHeading('رفض التعديل')
                    ->schema([
                        Textarea::make('reason')
                            ->label('سبب الرفض (يصل الفريق)')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->action(function (EventRevision $record, array $data): void {
                        app(RevisionService::class)->reject($record, $data['reason']);

                        Notification::make()->title('رُفض التعديل وبقيت النسخة المنشورة')->warning()->send();
                    }),
            ])
            ->emptyStateHeading('لا تعديلات بانتظار المراجعة')
            ->emptyStateIcon('heroicon-o-arrow-path-rounded-square');
    }

    private function renderDiff(EventRevision $revision): HtmlString
    {
        $rows = '';

        foreach ($revision->changedFields() as $field => $change) {
            $label = self::FIELD_LABELS[$field] ?? $field;
            $before = e($this->displayValue($field, $change['before']));
            $after = e($this->displayValue($field, $change['after']));

            $rows .= <<<HTML
                <tr>
                    <td style="padding:.5rem .75rem;font-weight:700;white-space:nowrap">{$label}</td>
                    <td style="padding:.5rem .75rem;color:#b91c1c;text-decoration:line-through">{$before}</td>
                    <td style="padding:.5rem .75rem;color:#047857;font-weight:600">{$after}</td>
                </tr>
                HTML;
        }

        if ($rows === '') {
            return new HtmlString('<p>لا فروق عن النسخة المنشورة — يمكن رفض هذه النسخة.</p>');
        }

        return new HtmlString(<<<HTML
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.9rem">
                    <thead>
                        <tr style="border-bottom:1px solid #e5e7eb;color:#6b7280">
                            <th style="padding:.5rem .75rem;text-align:start">الحقل</th>
                            <th style="padding:.5rem .75rem;text-align:start">المنشور الآن</th>
                            <th style="padding:.5rem .75rem;text-align:start">المقترح</th>
                        </tr>
                    </thead>
                    <tbody>{$rows}</tbody>
                </table>
            </div>
            HTML);
    }

    private function displayValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($field) {
            'category_id' => Category::find($value)?->name ?? (string) $value,
            'area_id' => Area::find($value)?->name ?? (string) $value,
            'shelter_center_id' => ShelterCenter::find($value)?->name ?? (string) $value,
            'start_time', 'end_time' => substr((string) $value, 0, 5),
            'image_path' => 'صورة جديدة',
            default => (string) $value,
        };
    }
}
