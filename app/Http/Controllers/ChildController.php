<?php

namespace App\Http\Controllers;

use App\Models\Child;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChildController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Auth::user()->children()->create($this->validated($request));

        return back()->with('child_saved', true);
    }

    public function update(Request $request, Child $child): RedirectResponse
    {
        $this->authorizeChild($child);

        $child->update($this->validated($request));

        return back()->with('child_saved', true);
    }

    public function destroy(Child $child): RedirectResponse
    {
        $this->authorizeChild($child);

        $child->delete();

        return back()->with('child_deleted', true);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'birth_date' => ['nullable', 'date', 'before:today', 'after:'.now()->subYears(19)->toDateString()],
            'gender' => ['nullable', 'in:male,female'],
            'grade' => ['nullable', 'string', 'max:60'],
        ]);
    }

    /** طفل وليّ أمر آخر لا يُقرأ ولا يُعدَّل */
    private function authorizeChild(Child $child): void
    {
        abort_unless($child->user_id === Auth::id(), 403);
    }
}
