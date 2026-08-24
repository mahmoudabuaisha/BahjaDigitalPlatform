<?php

namespace App\Filament\Admin\Resources\ImpactTargets;

use App\Filament\Admin\Resources\ImpactTargets\Pages\ManageImpactTargets;
use App\Models\AttendanceReport;
use App\Models\ImpactTarget;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** أهداف الأثر بفترات (القسم 14): «1000 طفل و3000 مستفيد غير مباشر» */
class ImpactTargetResource extends Resource
{
    protected static ?string $model = ImpactTarget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $modelLabel = 'هدف أثر';

    protected static ?string $pluralModelLabel = 'أهداف الأثر';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المحتوى';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')
                ->label('اسم الهدف')
                ->placeholder('مثل: هدف الربع الثالث 2026')
                ->required()
                ->maxLength(120),
            DatePicker::make('period_start')->label('بداية الفترة')->required(),
            DatePicker::make('period_end')->label('نهاية الفترة')->required()->afterOrEqual('period_start'),
            TextInput::make('children_target')
                ->label('هدف الأطفال المستفيدين')
                ->numeric()
                ->required()
                ->minValue(1),
            TextInput::make('indirect_target')
                ->label('هدف المستفيدين غير المباشرين')
                ->numeric()
                ->default(0)
                ->minValue(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->label('الهدف')->weight('bold'),
                TextColumn::make('period_start')
                    ->label('الفترة')
                    ->formatStateUsing(fn (ImpactTarget $record): string => $record->period_start->format('Y/m/d').' — '.$record->period_end->format('Y/m/d')),
                TextColumn::make('children_target')
                    ->label('التقدّم نحو هدف الأطفال')
                    ->formatStateUsing(function (ImpactTarget $record): string {
                        // الأرقام الفعلية المتحقَّق منها وحدها تدخل المؤشر (القسم 14.2)
                        $actual = AttendanceReport::verified()
                            ->whereHas('event', fn ($query) => $query
                                ->whereDate('start_date', '>=', $record->period_start)
                                ->whereDate('start_date', '<=', $record->period_end))
                            ->sum('children_actual');

                        $percent = $record->children_target > 0
                            ? (int) round($actual / $record->children_target * 100)
                            : 0;

                        return number_format($actual).' من '.number_format($record->children_target).' ('.$percent.'%)';
                    }),
                TextColumn::make('indirect_target')
                    ->label('هدف غير المباشرين')
                    ->numeric(),
            ])
            ->defaultSort('period_start', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageImpactTargets::route('/'),
        ];
    }
}
