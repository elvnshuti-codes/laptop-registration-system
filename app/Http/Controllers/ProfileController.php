<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $user->name = $validated['name'];
        $user->save();

        // Receptionists continue straight to their screen after confirming their name.
        if (! $user->hasRole('Administrator') && $user->can('manage visitor reception')) {
            return redirect()->route('reception.index')->with('success', 'Profile saved.');
        }

        return back()->with('success', 'Profile saved.');
    }
}