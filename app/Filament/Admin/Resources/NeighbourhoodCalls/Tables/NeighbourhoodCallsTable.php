<?php

namespace App\Filament\Admin\Resources\NeighbourhoodCalls\Tables;

use App\Http\Controllers\NeighbourhoodCallController;
use App\Models\NeighbourhoodCall;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NeighbourhoodCallsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user:id,name', 'area:id,name', 'shelterCenter:id,name']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('تاريخ النداء')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
                TextColumn::make('shelterCenter.name')
                    ->label('المكان')
                    ->placeholder('بلا مكان محدَّد')
                    ->description(fn (NeighbourhoodCall $record): ?string => $record->area?->name)
                    ->searchable(),
                TextColumn::make('children_count')
                    ->label('الأطفال')
                    ->sortable(),
                TextColumn::make('age_band')
                    ->label('الأعمار')
                    ->placeholder('كل الأعمار')
                    ->formatStateUsing(fn (?string $state): ?string => NeighbourhoodCallController::AGE_BANDS[$state] ?? $state),
                TextColumn::make('note')
                    ->label('ما قالته العائلة')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('العائلة')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('answered_at')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? 'لُبّي' : 'قائم')
                    ->color(fn (?string $state): string => $state ? 'success' : 'warning')
                    ->placeholder('قائم'),
            ])
            ->filters([
                SelectFilter::make('area_id')
                    ->label('المحافظة')
                    ->relationship('area', 'name'),
                TernaryFilter::make('answered_at')
                    ->label('النداءات القائمة')
                    ->placeholder('الكل')
                    ->trueLabel('لُبّيت')
                    ->falseLabel('قائمة')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('answered_at'),
                        false: fn (Builder $query) => $query->whereNull('answered_at'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->recordActions([
                DeleteAction::make()->label('حذف'),
            ])
            ->emptyStateHeading('لا نداءات بعد')
            ->emptyStateDescription('حين لا تجد عائلة فعالية قريبة منها ترفع يدها، فيظهر نداؤها هنا.');
    }
}
