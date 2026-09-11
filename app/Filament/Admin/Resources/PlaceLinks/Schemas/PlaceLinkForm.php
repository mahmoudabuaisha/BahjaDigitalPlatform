<?php

namespace App\Filament\Admin\Resources\PlaceLinks\Schemas;

use App\Models\PlaceLink;
use App\Models\ShelterCenter;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PlaceLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('from_center_id')
                    ->label('المكان الأول')
                    ->options(fn (): array => self::centers())
                    ->searchable()
                    ->required(),
                Select::make('to_center_id')
                    ->label('المكان الثاني')
                    ->options(fn (): array => self::centers())
                    ->searchable()
                    ->required()
                    ->different('from_center_id')
                    ->rules([
                        // الصلة متماثلة: وجودها بأي اتجاه يمنع تكرارها
                        fn (Get $get, ?PlaceLink $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record): void {
                            $exists = PlaceLink::query()
                                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                                ->where(fn (Builder $query) => $query
                                    ->where(fn (Builder $inner) => $inner
                                        ->where('from_center_id', $get('from_center_id'))
                                        ->where('to_center_id', $value))
                                    ->orWhere(fn (Builder $inner) => $inner
                                        ->where('from_center_id', $value)
                                        ->where('to_center_id', $get('from_center_id'))))
                                ->exists();

                            if ($exists) {
                                $fail('توجد صلة مسجَّلة بين هذين المكانين — عدّلوها بدل إضافة صلة ثانية.');
                            }
                        },
                    ]),
                TextInput::make('walk_minutes')
                    ->label('دقائق المشي بينهما')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(240)
                    ->suffix('دقيقة')
                    ->helperText('تقدير مشي البالغ على الطريق المعتاد — لا مسافة على الخريطة.')
                    ->required(),
                TextInput::make('note')
                    ->label('ملاحظة (اختياري)')
                    ->placeholder('مثال: الطريق ترابي ويصعب على العربات')
                    ->maxLength(160)
                    ->columnSpanFull(),
            ]);
    }

    /** @return array<int, string> */
    private static function centers(): array
    {
        return ShelterCenter::query()
            ->with('area:id,name')
            ->where('is_active', true)
            ->orderBy('area_id')
            ->orderBy('name')
            ->get(['id', 'name', 'area_id'])
            ->mapWithKeys(fn (ShelterCenter $center): array => [
                $center->id => $center->name.' — '.($center->area?->name ?? 'بلا محافظة'),
            ])
            ->all();
    }
}
