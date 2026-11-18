<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ReportAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class ReportAccessController extends Controller
{
    // Everyone, with a summary of the access they have.
    public function index()
    {
        $users = User::with(['roles', 'permissions'])->orderBy('name')->get();

        $labels = collect(ReportAccess::REPORTS + ReportAccess::AREAS + ReportAccess::STAFF + ReportAccess::GATE + ReportAccess::SCREENS)
            ->map(fn ($item) => $item[0])
            ->all();

        return view('admin.report-access.index', compact('users', 'labels'));
    }

    // One person's tick boxes.
    public function edit(User $user)
    {
        return view('admin.report-access.edit', [
            'user' => $user,
            'reports' => ReportAccess::REPORTS,
            'areas' => ReportAccess::AREAS,
            'staff' => ReportAccess::STAFF,
            'gate' => ReportAccess::GATE,
            'screens' => ReportAccess::SCREENS,
            'direct' => $user->getDirectPermissions()->pluck('name')->all(),
            'viaRole' => $user->getPermissionsViaRoles()->pluck('name')->all(),
            'isAdmin' => $user->hasRole('Administrator'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->hasRole('Administrator'), 403, 'The Administrator always has full access.');

        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in(ReportAccess::all())],
        ]);

        $chosen = collect($request->input('permissions', []))->unique()->values();

        // A report with no area would have nothing to show: ask for at least one area.
        // Access that comes from the person's role also counts.
        $needsArea = $chosen->intersect(['view reports', 'view visitor records'])->isNotEmpty();
        $roleAreas = $user->getPermissionsViaRoles()->pluck('name')->intersect(array_keys(ReportAccess::AREAS));
        $hasArea = $chosen->intersect(array_keys(ReportAccess::AREAS))->isNotEmpty() || $roleAreas->isNotEmpty();

        if ($needsArea && ! $hasArea) {
            return back()->withInput()->withErrors([
                'permissions' => 'Choose at least one area (Standard House data or Sample House data), so the person has something to see.',
            ]);
        }

        // Replace only the permissions managed on this screen; keep any other permission the person has.
        $others = $user->getDirectPermissions()->pluck('name')
            ->reject(fn ($name) => in_array($name, ReportAccess::all(), true));

        $user->syncPermissions($others->merge($chosen)->unique()->values()->all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.report-access.index')
            ->with('success', "Access updated for {$user->name}.");
    }
}