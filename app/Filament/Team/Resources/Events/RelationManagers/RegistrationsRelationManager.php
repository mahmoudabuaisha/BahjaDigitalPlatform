<?php

namespace App\Filament\Team\Resources\Events\RelationManagers;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use App\Models\UserNotification;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** حجوزات العائلات في هذه الفعالية — القبول والاعتذار مع سبب يصل وليّ الأمر */
class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $title = 'حجوزات العائلات';

    public static function getBadge($ownerRecord, string $pageClass): ?string
    {
        $pending = $ownerRecord->registrations()->where('status', RegistrationStatus::Pending)->count();

        return $pending ? (string) $pending : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('child.name')->label('الطفل')->searchable()->placeholder('—'),
                TextColumn::make('user.name')->label('وليّ الأمر')->searchable(),
                TextColumn::make('user.phone')->label('الجوال')->placeholder('—'),
                TextColumn::make('note')->label('ملاحظة العائلة')->limit(30)->placeholder('—'),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('created_at')->label('تاريخ الطلب')->dateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(RegistrationStatus::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([
                Action::make('accept')
                    ->label('قبول')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Registration $record): bool => $record->status === RegistrationStatus::Pending)
                    ->requiresConfirmation()
                    ->action(function (Registration $record): void {
                        $record->update([
                            'status' => RegistrationStatus::Accepted,
                            'reviewed_at' => now(),
                        ]);

                        UserNotification::send(
                            $record->user_id,
                            'registration_accepted',
                            'تم قبول حجزكم في «'.$record->event->title.'»',
                            $record->child?->name.' — '.$record->event->start_date->translatedFormat('l j F').' الساعة '.substr($record->event->start_time, 0, 5),
                            route('events.show', $record->event),
                        );
                    }),

                Action::make('reject')
                    ->label('اعتذار مع سبب')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Registration $record): bool => $record->status === RegistrationStatus::Pending)
                    ->schema([
                        Textarea::make('review_note')
                            ->label('السبب — يصل وليّ الأمر كما هو')
                            ->required()
                            ->maxLength(300),
                    ])
                    ->action(function (Registration $record, array $data): void {
                        $record->update([
                            'status' => RegistrationStatus::Rejected,
                            'review_note' => $data['review_note'],
                            'reviewed_at' => now(),
                        ]);

                        UserNotification::send(
                            $record->user_id,
                            'registration_rejected',
                            'اعتذر الفريق عن حجزكم في «'.$record->event->title.'»',
                            $data['review_note'],
                            route('events.show', $record->event),
                        );
                    }),
            ]);
    }
}
