<?php

namespace App\Filament\Admin\Resources\Feedback\Pages;

use App\Filament\Admin\Resources\Feedback\FeedbackResource;
use App\Models\Feedback;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListFeedback extends ListRecords
{
    protected static string $resource = FeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadCsv')
                ->label('تنزيل CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                    $handle = fopen('php://output', 'w');

                    fwrite($handle, "\xEF\xBB\xBF");

                    fputcsv($handle, ['التاريخ', 'المصدر', 'التقييم', 'الفعالية', 'الرسالة', 'الاسم', 'الجوال']);

                    Feedback::with('event:id,title')
                        ->orderBy('created_at')
                        ->lazy()
                        ->each(function (Feedback $feedback) use ($handle): void {
                            fputcsv($handle, [
                                $feedback->created_at->format('Y-m-d H:i'),
                                $feedback->source->getLabel(),
                                $feedback->rating,
                                $feedback->event?->title ?? 'تقييم عام',
                                $feedback->message,
                                $feedback->contact_name,
                                $feedback->contact_phone,
                            ]);
                        });

                    fclose($handle);
                }, 'bahja-feedback-'.now()->format('Y-m-d').'.csv', [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                ])),
        ];
    }
}
