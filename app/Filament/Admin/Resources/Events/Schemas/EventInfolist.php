<?php

namespace App\Filament\Admin\Resources\Events\Schemas;

use App\Models\Event;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('الفعالية')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('title')->label('العنوان'),
                            TextEntry::make('status')->label('الحالة')->badge(),
                        ]),
                        TextEntry::make('description')
                            ->label('الوصف')
                            ->placeholder('بدون وصف')
                            ->columnSpanFull(),
                        TextEntry::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->color('danger')
                            ->visible(fn (Event $record): bool => filled($record->rejection_reason))
                            ->columnSpanFull(),
                        ImageEntry::make('image_path')
                            ->label('الصورة')
                            ->disk('public')
                            ->visible(fn (Event $record): bool => filled($record->image_path)),
                    ]),

                Section::make('المكان والزمان')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('area.name')->label('المنطقة'),
                            TextEntry::make('shelterCenter.name')
                                ->label('المركز')
                                ->placeholder('موقع حر'),
                            TextEntry::make('location_details')
                                ->label('تفاصيل الموقع')
                                ->placeholder('—'),
                        ]),
                        TextEntry::make('directions')
                            ->label('كيف تصلون؟')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        Grid::make(3)->schema([
                            TextEntry::make('start_date')->label('التاريخ')->date('Y/m/d'),
                            TextEntry::make('start_time')
                                ->label('البداية')
                                ->formatStateUsing(fn (string $state): string => substr($state, 0, 5)),
                            TextEntry::make('end_time')
                                ->label('النهاية')
                                ->formatStateUsing(fn (?string $state): string => $state ? substr($state, 0, 5) : '—'),
                        ]),
                    ]),

                Section::make('الفريق والأعداد')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('team.name')->label('الفريق'),
                            TextEntry::make('category.name')->label('التصنيف')->placeholder('—'),
                            TextEntry::make('views_count')->label('المشاهدات')->numeric(),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('expected_children')->label('العدد المتوقع')->placeholder('—'),
                            TextEntry::make('actual_children')->label('حضور الأطفال الفعلي')->placeholder('لم يُسجَّل بعد'),
                            TextEntry::make('actual_caregivers')->label('حضور المرافقين')->placeholder('لم يُسجَّل بعد'),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('approver.name')
                                ->label('اعتمدها')
                                ->placeholder('—'),
                            TextEntry::make('approved_at')
                                ->label('تاريخ الاعتماد')
                                ->dateTime('Y/m/d H:i')
                                ->placeholder('—'),
                        ]),
                    ]),
            ]);
    }
}
