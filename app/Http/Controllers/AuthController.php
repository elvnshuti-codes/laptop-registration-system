<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectToHome();
        }

        return view('auth.login');
    }

    // The web address "/": sign-in page for guests, their own home for signed-in users.
    public function home()
    {
        return Auth::check() ? $this->redirectToHome() : redirect()->route('login');
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
        /**
     * Decide where a user lands after login / password change.
     * Officers land on their own screen; everyone else lands on the first page they are allowed to open.
     * Administrators pass every permission check, so they are handled first.
     */
        private function redirectToHome()
        {
            $user = Auth::user();
    
            if ($user->hasRole('Administrator')) {
                return redirect()->route('registrations.index');
            }
    
            // Screens that belong to a station, in the order they are checked.
            $landing = [
                'manage gate visitors' => 'gate.home',
                'manage visitor devices' => 'entry.index',
                'manage visitor reception' => 'reception.index',
                'manage sample reception' => 'sample.index',
                'view reports' => 'reports.index',
                'view visitor records' => 'records.index',
                'manage staff devices' => 'staff.employees.index',
                'view registrations' => 'registrations.index',
            ];
    
            foreach ($landing as $permission => $route) {
                if ($user->can($permission)) {
                    return redirect()->route($route);
                }
            }
    
            abort(403, 'No pages are assigned to your account yet. Please contact the administrator.');
        }
}