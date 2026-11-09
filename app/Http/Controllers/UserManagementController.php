<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use Spatie\Permission\Models\Role;
class UserManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_unless(auth()->user()->can('manage users'), 403);

        $users = User::with('roles')->latest()->get();
    
        return view('users.index', ['users' => $users]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_unless(auth()->user()->can('manage users'), 403);

    $roles = Role::all();

    return view('users.create', ['roles' => $roles]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage users'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,name',
            'location' => ['required_if:role,Entry Officer', 'nullable', Rule::in(User::LOCATIONS)],
        ], [
            'location.required_if' => 'Choose where this Entry Officer works.',
        ]);
    
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'must_change_password' => true,
            // Only Entry Officers have a location.
            'location' => $validated['role'] === 'Entry Officer' ? $validated['location'] : null,
        ]);
    
        $user->assignRole($validated['role']);
    
        return redirect()->route('users.index')->with('success', 'User created successfully.');

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
    public function edit(User $user)
    {
        abort_unless(auth()->user()->can('manage users'), 403);

    $roles = Role::all();

    return view('users.edit', ['user' => $user, 'roles' => $roles]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->can('manage users'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|exists:roles,name',
            'location' => ['required_if:role,Entry Officer', 'nullable', Rule::in(User::LOCATIONS)],
            'password' => 'nullable|string|min:8',
        ], [
            'location.required_if' => 'Choose where this Entry Officer works.',
        ]);
    
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            // Only Entry Officers have a location.
            'location' => $validated['role'] === 'Entry Officer' ? $validated['location'] : null,
        ]);

        if (!empty($validated['password'])) {
        $user->update([
            'password' => $validated['password'],
            'must_change_password' => true,
        ]);
        }
    
    
        $user->syncRoles([$validated['role']]);
    
        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        abort_unless(auth()->user()->can('manage users'), 403);

    abort_if($user->id === auth()->id(), 403, 'You cannot delete your own account.');

    $user->delete();

    return redirect()->route('users.index')->with('success', 'User deleted.');
    }
}