<?php

namespace App\Filament\Admin\Resources\PlaceLinks\Tables;

use App\Models\Area;
use App\Models\PlaceLink;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlaceLinksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['from.area', 'to.area']))
            ->defaultSort('walk_minutes')
            ->columns([
                TextColumn::make('from.name')
                    ->label('من')
                    ->description(fn (PlaceLink $record): ?string => $record->from?->area?->name)
                    ->searchable(),
                TextColumn::make('to.name')
                    ->label('إلى')
                    ->description(fn (PlaceLink $record): ?string => $record->to?->area?->name)
                    ->searchable(),
                TextColumn::make('walk_minutes')
                    ->label('المشي')
                    ->formatStateUsing(fn (int $state): string => PlaceLink::minutesLabel($state))
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 10 => 'success',
                        $state <= 25 => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('note')
                    ->label('ملاحظة')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                // الصلة تخصّ محافظةً إن كان أحد طرفيها فيها
                SelectFilter::make('area')
                    ->label('المحافظة')
                    ->options(fn (): array => Area::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $inner, $areaId) => $inner
                            ->where(fn (Builder $pair) => $pair
                                ->whereHas('from', fn (Builder $center) => $center->where('area_id', $areaId))
                                ->orWhereHas('to', fn (Builder $center) => $center->where('area_id', $areaId))))),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ])
            ->emptyStateHeading('لا صلات مسجَّلة بعد')
            ->emptyStateDescription('سجّلوا كم دقيقة مشياً بين الأماكن المتجاورة، فتعرف العائلات أقرب فعالية إليها.');
    }
}
