<?php

namespace App\Http\Controllers;

use App\Models\SampleType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SampleTypeController extends Controller
{
    public function index()
    {
        $types = SampleType::orderBy('name')->get();

        return view('admin.sample-types.index', compact('types'));
    }

    public function store(Request $request)
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:sample_types,name',
        ]);

        SampleType::create($validated);

        return back()->with('success', "Sample type \"{$validated['name']}\" added.");
    }

    public function update(Request $request, SampleType $sampleType)
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('sample_types', 'name')->ignore($sampleType->id)],
        ]);

        $sampleType->update($validated);

        return back()->with('success', 'Sample type updated. Existing visits keep their original wording.');
    }

    public function destroy(SampleType $sampleType)
    {
        $name = $sampleType->name;
        $sampleType->delete();

        return back()->with('success', "Sample type \"{$name}\" deleted. Existing records are not affected.");
    }
}
