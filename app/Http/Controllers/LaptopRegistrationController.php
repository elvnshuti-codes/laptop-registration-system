<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LaptopRegistration; 

class LaptopRegistrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $registrations = LaptopRegistration::all();

        return view('registrations.index', ['registrations' => $registrations]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('registrations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'employee_name' => 'required|string|max:255',
        'employee_id_number' => 'required|string|max:255',
        'department' => 'required|string|max:255',
        'laptop_type' => 'required|string|max:255',
    ]);

    $validated['checked_in_at'] = now();

    LaptopRegistration::create($validated);

    return redirect()->route('registrations.index')->with('success', 'Laptop checked in successfully.');
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LaptopRegistration $registration)
    {
        return view('registrations.edit', ['registration' => $registration]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LaptopRegistration $registration)
    {
        $validated = $request->validate([
            'employee_name' => 'required|string|max:255',
            'employee_id_number' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'laptop_type' => 'required|string|max:255',
        ]);
    
        if ($request->has('check_out')) {
            $validated['checked_out_at'] = now();
        }
    
        $registration->update($validated);
    
        return redirect()->route('registrations.index')->with('success', 'Registration updated successfully.');
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
