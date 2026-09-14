<?php

namespace App\Filament\Admin\Resources\ContactMessages\Schemas;

use App\Models\ContactMessage;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\HtmlString;

class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('الرسالة')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('subject')
                            ->label('الموضوع')
                            ->placeholder('بلا موضوع')
                            ->weight('bold')
                            ->size(TextSize::Large),
                        TextEntry::make('message')
                            ->label('نصّ الرسالة')
                            // الأسطر كما كتبتها العائلة — يُعقَّم النص قبل السماح بالأسطر
                            ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(nl2br(e($state))))
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Section::make('المرسِل')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('name')->label('الاسم'),
                            TextEntry::make('phone')
                                ->label('رقم الجوال')
                                ->placeholder('لم يُترك رقم')
                                ->icon('heroicon-o-phone')
                                ->copyable()
                                ->copyMessage('نُسخ الرقم'),
                            TextEntry::make('email')
                                ->label('البريد الإلكتروني')
                                ->placeholder('لم يُترك بريد')
                                ->icon('heroicon-o-envelope')
                                ->copyable()
                                ->copyMessage('نُسخ البريد'),
                        ]),
                        TextEntry::make('created_at')
                            ->label('وصلت في')
                            ->dateTime('Y/m/d H:i'),
                    ]),

                Section::make('المعالجة')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('status')
                                ->label('الحالة')
                                ->badge()
                                ->state(fn (ContactMessage $record): string => $record->isHandled() ? 'تمّت المعالجة' : 'جديدة')
                                ->color(fn (ContactMessage $record): string => $record->isHandled() ? 'success' : 'danger'),
                            TextEntry::make('handled_at')
                                ->label('أُغلقت في')
                                ->dateTime('Y/m/d H:i')
                                ->placeholder('—'),
                            TextEntry::make('handledBy.name')
                                ->label('بواسطة')
                                ->placeholder('—'),
                        ]),
                        TextEntry::make('note')
                            ->label('ملاحظة داخلية')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
