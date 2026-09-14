<?php

namespace App\Filament\Admin\Resources\ContactMessages\Actions;

use App\Models\AuditLog;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

/**
 * إجراءات صندوق الرسائل — تُستخدم في جدول القائمة وصفحة العرض معاً.
 */
class ContactMessageActions
{
    public static function replyWhatsapp(): Action
    {
        return Action::make('replyWhatsapp')
            ->label('ردّ عبر واتساب')
            ->icon('heroicon-o-chat-bubble-oval-left')
            ->color('success')
            ->visible(fn (ContactMessage $record): bool => $record->whatsappUrl() !== null)
            ->url(fn (ContactMessage $record): string => (string) $record->whatsappUrl(), shouldOpenInNewTab: true);
    }

    public static function replyEmail(): Action
    {
        return Action::make('replyEmail')
            ->label('ردّ عبر البريد')
            ->icon('heroicon-o-envelope')
            ->color('info')
            ->visible(fn (ContactMessage $record): bool => $record->mailtoUrl() !== null)
            ->url(fn (ContactMessage $record): string => (string) $record->mailtoUrl());
    }

    public static function markHandled(): Action
    {
        return Action::make('markHandled')
            ->label('تمّت المعالجة')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (ContactMessage $record): bool => ! $record->isHandled())
            ->modalHeading('إغلاق الرسالة')
            ->modalDescription('تخرج الرسالة من قائمة «الجديدة» ويُذكر من عالجها ومتى.')
            ->modalSubmitActionLabel('تأكيد')
            ->schema([
                Textarea::make('note')
                    ->label('ملاحظة داخلية (اختياري)')
                    ->placeholder('مثلاً: رُدّ عبر واتساب، أو أُحيلت إلى فريق بسمة أمل')
                    ->maxLength(500)
                    ->rows(3),
            ])
            ->action(function (ContactMessage $record, array $data): void {
                $record->markHandled($data['note'] ?? null, auth()->id());

                AuditLog::record('contact.handled', $record, reason: $data['note'] ?? null);

                Notification::make()
                    ->title('أُغلقت الرسالة')
                    ->success()
                    ->send();
            });
    }

    public static function reopen(): Action
    {
        return Action::make('reopen')
            ->label('إعادة فتح')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->visible(fn (ContactMessage $record): bool => $record->isHandled())
            ->requiresConfirmation()
            ->modalHeading('إعادة فتح الرسالة')
            ->modalDescription('تعود إلى قائمة «الجديدة» بانتظار الردّ.')
            ->action(function (ContactMessage $record): void {
                $record->reopen();

                AuditLog::record('contact.reopened', $record);

                Notification::make()
                    ->title('أُعيد فتح الرسالة')
                    ->send();
            });
    }

    public static function markHandledBulk(): BulkAction
    {
        return BulkAction::make('markHandledBulk')
            ->label('تمّت معالجة المحدَّد')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('إغلاق الرسائل المحدَّدة')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                $records->each(function (ContactMessage $record): void {
                    if ($record->isHandled()) {
                        return;
                    }

                    $record->markHandled(null, auth()->id());
                    AuditLog::record('contact.handled', $record);
                });

                Notification::make()
                    ->title('أُغلقت الرسائل المحدَّدة')
                    ->success()
                    ->send();
            });
    }
}
