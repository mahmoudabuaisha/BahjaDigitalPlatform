<?php

namespace App\Http\Controllers;

use App\Enums\FeedbackSource;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('pages.contact');
    }

    public function store(Request $request): RedirectResponse
    {
        // فخ البوتات — البشر لا يرون الحقل ولا يملؤونه
        if (filled($request->input('website'))) {
            return redirect()->route('contact')->with('contact_sent', true);
        }

        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        Feedback::create([
            ...$data,
            'source' => FeedbackSource::Family,
        ]);

        return redirect()->route('contact')->with('contact_sent', true);
    }
}
