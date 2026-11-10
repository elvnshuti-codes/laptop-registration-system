<?php

namespace App\Http\Controllers;

use App\Models\UnservedReason;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnservedReasonController extends Controller
{
    // Admin list of the reasons Entry officers can choose from.
    public function index()
    {
        $reasons = UnservedReason::orderBy('is_system')->orderBy('name')->get();

        return view('admin.reasons.index', compact('reasons'));
    }

    public function store(Request $request)
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('unserved_reasons', 'name')],
        ]);

        UnservedReason::create([
            'name' => $validated['name'],
            'requires_note' => $request->boolean('requires_note'),
            'is_system' => false,
        ]);

        return back()->with('success', "Reason added: {$validated['name']}");
    }

    public function update(Request $request, UnservedReason $reason)
    {
        if ($reason->is_system) {
            return back()->with('error', 'This built-in reason cannot be changed.');
        }

        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('unserved_reasons', 'name')->ignore($reason->id)],
        ]);

        $reason->update([
            'name' => $validated['name'],
            'requires_note' => $request->boolean('requires_note'),
        ]);

        return back()->with('success', "Reason updated: {$validated['name']}");
    }

    public function destroy(UnservedReason $reason)
    {
        if ($reason->is_system) {
            return back()->with('error', 'This built-in reason cannot be deleted.');
        }

        $reason->delete();

        return back()->with('success', "Reason deleted: {$reason->name}");
    }
}
