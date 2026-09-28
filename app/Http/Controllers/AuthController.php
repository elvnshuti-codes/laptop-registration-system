<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller

{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->route('registrations.index');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

       public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
        
    }
    public function showChangePasswordForm()
{
    return view('auth.change-password');
}

public function changePassword(Request $request)
{
    $validated = $request->validate([
        'password' => 'required|string|min:8|confirmed',
    ]);

    auth()->user()->update([
        'password' => $validated['password'],
        'must_change_password' => false,
        'password_changed_at' => now(),
    ]);

    return redirect()->route('registrations.index')->with('success', 'Password changed successfully.');
}
}
