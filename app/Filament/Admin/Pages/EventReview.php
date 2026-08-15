<?php

namespace App\Filament\Admin\Pages;

use App\Enums\EventStatus;
use App\Filament\Admin\Resources\Events\Actions\EventActions;
use App\Filament\Admin\Resources\Events\EventResource;
use App\Models\Event;
use App\Support\Scene;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/**
 * شاشة مراجعة الفعاليات المقدَّمة من المنظِّمين قبل نشرها:
 * تبويب لكل حالة، وقبول أو رفض من الصفّ مباشرة.
 */
class EventReview extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $title = 'مراجعة الفعاليات';

    protected static ?string $navigationLabel = 'مراجعة الفعاليات';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المحتوى';

    protected static ?string $navigationParentItem = 'الفعاليات';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.admin.pages.event-review';

    public string $statusTab = 'pending';

    public static function getNavigationBadge(): ?string
    {
        $pending = Event::where('status', EventStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public function getSubheading(): ?string
    {
        return 'مراجعة الفعاليات المقدَّمة من المنظِّمين قبل نشرها.';
    }

    /** @return array<string, array{label: string, color: string, statuses: array<int, EventStatus>|null}> */
    public function tabs(): array
    {
        return [
            'all' => ['label' => 'الكل', 'color' => 'primary', 'statuses' => null],
            'pending' => ['label' => 'قيد المراجعة', 'color' => 'warning', 'statuses' => [EventStatus::Pending]],
            'approved' => ['label' => 'مقبولة', 'color' => 'success', 'statuses' => [EventStatus::Approved, EventStatus::Completed]],
            'rejected' => ['label' => 'مرفوضة', 'color' => 'danger', 'statuses' => [EventStatus::Rejected, EventStatus::Cancelled]],
        ];
    }

    /** @return array<string, int> */
    public function tabCounts(): array
    {
        $counts = Event::query()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $of = fn (array $statuses): int => collect($statuses)
            ->sum(fn (EventStatus $status): int => (int) ($counts[$status->value] ?? 0));

        return collect($this->tabs())
            ->map(fn (array $tab): int => $tab['statuses'] === null ? (int) $counts->sum() : $of($tab['statuses']))
            ->all();
    }

    public function selectTab(string $tab): void
    {
        $this->statusTab = array_key_exists($tab, $this->tabs()) ? $tab : 'all';

        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        $statuses = $this->tabs()[$this->statusTab]['statuses'] ?? null;

        return $table
            ->query(
                Event::query()
                    ->with(['team', 'area', 'category', 'creator'])
                    ->when($statuses, fn (Builder $query) => $query->whereIn('status', $statuses))
            )
            ->columns([
                ImageColumn::make('image_path')
                    ->label('')
                    ->disk('public')
                    ->height(44)
                    ->width(44)
                    ->extraImgAttributes(['class' => 'rounded-xl object-cover'])
                    ->defaultImageUrl(fn (Event $record): string => Scene::dataUri($record->category)),
                TextColumn::make('title')
                    ->label('الفعالية')
                    ->weight('bold')
                    ->limit(34)
                    ->searchable()
                    ->url(fn (Event $record): string => EventResource::getUrl('view', ['record' => $record])),
                TextColumn::make('team.name')
                    ->label('المنظِّم')
                    ->description(fn (Event $record): string => $record->creator?->name ?? 'منظِّم فعاليات')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label('التاريخ والمكان')
                    ->date('Y/m/d')
                    ->description(fn (Event $record): ?string => $record->area?->name)
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('الفئة')
                    ->badge()
                    ->color(fn (Event $record): string => match ($record->category?->color) {
                        'warning', 'danger', 'info', 'success' => $record->category->color,
                        default => 'primary',
                    }),
                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('حالة المراجعة')
                    ->badge(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([
                SelectFilter::make('status')
                    ->label('حالة المراجعة')
                    ->options(EventStatus::class),
                SelectFilter::make('area_id')
                    ->label('المكان')
                    ->relationship('area', 'name'),
                SelectFilter::make('category_id')
                    ->label('الفئة')
                    ->relationship('category', 'name'),
                Filter::make('start_date')
                    ->label('تاريخ الفعالية')
                    ->schema([
                        DatePicker::make('date')->label('تاريخ الفعالية'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('start_date', $date))),
            ])
            ->recordActions([
                EventActions::approve(),
                EventActions::reject(),
                ActionGroup::make([
                    ViewAction::make()
                        ->label('عرض')
                        ->url(fn (Event $record): string => EventResource::getUrl('view', ['record' => $record])),
                    EditAction::make()
                        ->label('تعديل')
                        ->url(fn (Event $record): string => EventResource::getUrl('edit', ['record' => $record])),
                    EventActions::qrCode(),
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
                            $approved = $records
                                ->filter(fn (Event $record): bool => $record->status === EventStatus::Pending)
                                ->each(fn (Event $record) => $record->forceFill([
                                    'status' => EventStatus::Approved,
                                    'approved_by' => auth()->id(),
                                    'approved_at' => now(),
                                    'rejection_reason' => null,
                                ])->save())
                                ->count();

                            Notification::make()->title("تم اعتماد {$approved} فعالية")->success()->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('لا فعاليات في هذا التبويب')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}
