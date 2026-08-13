<?php

namespace App\Http\Controllers;

use App\Enums\FeedbackSource;
use App\Models\Event;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function create(): View
    {
        return view('pages.feedback');
    }

    public function store(Request $request): RedirectResponse
    {
        // حقل فخ للبوتات: البشر لا يرونه ولا يملؤونه — نتجاهل الطلب بصمت
        if (filled($request->input('website'))) {
            return redirect()->route('feedback.create')->with('feedback_sent', true);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'source' => ['nullable', 'in:family,team'],
            'message' => ['nullable', 'string', 'max:1000'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
        ]);

        Feedback::create([
            ...$data,
            'source' => $data['source'] ?? FeedbackSource::Family->value,
        ]);

        return redirect()->route('feedback.create')->with('feedback_sent', true);
    }

    public function storeForEvent(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->status->isPubliclyVisible(), 404);

        if (filled($request->input('website'))) {
            return redirect()->route('events.show', $event)->with('feedback_sent', true);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $event->feedback()->create([
            ...$data,
            'source' => FeedbackSource::Family,
        ]);

        return redirect()->route('events.show', $event)->with('feedback_sent', true);
    }
}
