<?php

namespace App\Http\Controllers;

use App\Models\VisitPurpose;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VisitPurposeController extends Controller
{
    public function index()
    {
        $purposes = VisitPurpose::orderBy('is_system')->orderBy('name')->get();

        return view('admin.purposes.index', compact('purposes'));
    }

    public function store(Request $request)
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:visit_purposes,name',
        ]);

        if (! $request->boolean('at_main') && ! $request->boolean('at_rig')) {
            return back()->withInput()->withErrors(['name' => 'Choose where this purpose is offered: Main site, Rig, or both.']);
        }

        VisitPurpose::create([
            'name' => $validated['name'],
            'is_testing' => $request->boolean('is_testing'),
            'at_main' => $request->boolean('at_main'),
            'at_rig' => $request->boolean('at_rig'),
        ]);

        return back()->with('success', "Purpose \"{$validated['name']}\" added.");
    }

    public function update(Request $request, VisitPurpose $purpose)
    {
        if ($purpose->is_system) {
            return back()->with('error', "\"{$purpose->name}\" is built in and cannot be renamed.");
        }

        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('visit_purposes', 'name')->ignore($purpose->id)],
        ]);

        if (! $request->boolean('at_main') && ! $request->boolean('at_rig')) {
            return back()->withErrors(['name' => 'Choose where this purpose is offered: Main site, Rig, or both.']);
        }

        $purpose->update([
            'name' => $validated['name'],
            'is_testing' => $request->boolean('is_testing'),
            'at_main' => $request->boolean('at_main'),
            'at_rig' => $request->boolean('at_rig'),
        ]);

        return back()->with('success', 'Purpose updated. Existing visits and bookings keep their original wording.');
    }

    public function destroy(VisitPurpose $purpose)
    {
        if ($purpose->is_system) {
            return back()->with('error', "\"{$purpose->name}\" is built in and cannot be deleted.");
        }

        $name = $purpose->name;
        $purpose->delete();

        return back()->with('success', "Purpose \"{$name}\" deleted. Existing records arenot affected.");
    }
}