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

            return $this->redirectToHome();
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

        $user = auth()->user();

        $user->update([
            'password' => $validated['password'],
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        // Receptionists set up their own profile right after their first password change.
        if (! $user->hasRole('Administrator') && $user->can('manage visitor reception')) {
            return redirect()->route('profile.edit')
                ->with('success', 'Password changed. Please confirm your name below.');
        }

        return $this->redirectToHome()->with('success', 'Password changed successfully.');
    }

    /**
     * Decide where a user lands after login / password change.
     * Administrators pass every permission check, so they are excluded explicitly
     * and go to the main registrations page. Officers land on their own screen.
     */
    private function redirectToHome()
    {
        $user = Auth::user();

        if (! $user->hasRole('Administrator')) {
            if ($user->can('manage gate visitors')) {
                return redirect()->route('gate.create');
            }

            if ($user->can('manage visitor devices')) {
                return redirect()->route('entry.index');
            }

            if ($user->can('manage visitor reception')) {
                return redirect()->route('reception.index');
            }
        }

        return redirect()->route('registrations.index');
    }
}