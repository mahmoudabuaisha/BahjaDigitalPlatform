<?php

namespace App\Filament\Admin\Resources\NewsletterSubscribers\Tables;

use App\Models\NewsletterSubscriber;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NewsletterSubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('area:id,name'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')
                    ->label('البريد')
                    ->weight('bold')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('نُسخ البريد'),
                TextColumn::make('area.name')
                    ->label('المحافظة')
                    ->placeholder('كل المحافظات'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->state(fn (NewsletterSubscriber $record): string => $record->statusLabel())
                    ->color(fn (NewsletterSubscriber $record): string => $record->statusColor()),
                TextColumn::make('created_at')
                    ->label('اشترك في')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
                TextColumn::make('last_sent_at')
                    ->label('آخر نشرة')
                    ->dateTime('Y/m/d H:i')
                    ->placeholder('لم تُرسل بعد')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('area_id')
                    ->label('المحافظة')
                    ->relationship('area', 'name'),
            ])
            ->recordActions([
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف المحدَّد'),
                ]),
            ])
            ->emptyStateHeading('لا مشتركين بعد')
            ->emptyStateDescription('نموذج الاشتراك في أسفل كل صفحة من الموقع، وكل من يؤكّد بريده يظهر هنا.');
    }
}
