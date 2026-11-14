<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    // Suggestions for the Position box. Any position can be typed.
    public const POSITIONS = [
        'Executive Director', 'Director', 'Principal Officer', 'Senior Officer', 'Officer', 'Graduate Trainee',
    ];

    public function index()
    {
        $employees = Employee::withCount([
            'devices as organization_count' => fn ($q) => $q->where('is_active', true)->where('owner_type', 'organization'),
            'devices as personal_count' => fn ($q) => $q->where('is_active', true)->where('owner_type', 'personal'),
        ])->orderBy('name')->get();

        return view('staff.employees.index', ['employees' => $employees]);
    }

    public function create()
    {
        return view('staff.employees.create', $this->formData());
    }

    public function store(Request $request)
    {
        $employee = Employee::create($this->validated($request) + ['is_active' => true]);

        return redirect()
            ->route('staff.employees.index')
            ->with('success', "Employee added: {$employee->name} ({$employee->employee_id_number})");
    }

    public function edit(Employee $employee)
    {
        return view('staff.employees.edit', $this->formData() + ['employee' => $employee]);
    }

    public function update(Request $request, Employee $employee)
    {
        $employee->update($this->validated($request, $employee));

        return redirect()
            ->route('staff.employees.index')
            ->with('success', "Employee updated: {$employee->name}");
    }

    // Deactivate instead of delete, so past records keep their owner.
    public function toggle(Employee $employee)
    {
        $employee->update(['is_active' => ! $employee->is_active]);

        return back()->with('success', $employee->name . ($employee->is_active ? ' is active again.' : ' was deactivated.'));
    }

    // The administrator has looked at an imported record that was flagged.
    public function reviewed(Employee $employee)
    {
        $employee->update(['needs_review' => false, 'review_note' => null]);

        return back()->with('success', "{$employee->name} marked as reviewed.");
    }

    // A temporary record from the import (TEMP-0001...) is merged into the real employee it belongs to.
    public function mergeForm(Employee $employee)
    {
        $this->requireTemporary($employee);

        return view('staff.employees.merge', [
            'employee' => $employee->load('devices'),
            'targets' => Employee::where('id', '!=', $employee->id)
                ->where('employee_id_number', 'not like', 'TEMP-%')
                ->orderBy('name')
                ->get(['employee_id_number', 'name', 'department']),
        ]);
    }

    public function merge(Request $request, Employee $employee)
    {
        $this->requireTemporary($employee);

        $request->validate(['target' => 'required|string|max:300']);

        // The box holds "ID · Name"; the ID is the part before the dot.
        $id = strtoupper(trim(explode('·', (string) $request->input('target'))[0]));

        $target = Employee::where('employee_id_number', $id)
            ->where('id', '!=', $employee->id)
            ->where('employee_id_number', 'not like', 'TEMP-%')
            ->first();

        if (! $target) {
            return back()->withInput()->withErrors(['target' => 'Choose a person from the list (the text before the dot is the employee ID).']);
        }

        // The same device limits as when registering by hand.
        $moving = $employee->devices()->where('is_active', true)->get();
        $limits = ['organization' => StaffDeviceController::MAX_ORGANIZATION, 'personal' => StaffDeviceController::MAX_PERSONAL];

        foreach ($limits as $type => $max) {
            $incoming = $moving->where('owner_type', $type)->count();
            $has = $target->devices()->where('is_active', true)->where('owner_type', $type)->count();

            if ($incoming > 0 && $has + $incoming > $max) {
                return back()->withInput()->withErrors([
                    'target' => "{$target->name} already has {$has} active {$type} device(s), so another cannot be added. Retire one first.",
                ]);
            }
        }

        DB::transaction(function () use ($employee, $target) {
            $employee->devices()->update(['employee_id' => $target->id]);
            $employee->delete();
        });

        return redirect()
            ->route('staff.employees.index')
            ->with('success', "Moved {$moving->count()} active device(s) to {$target->name} and removed {$employee->employee_id_number}.");
    }

    private function requireTemporary(Employee $employee): void
    {
        abort_unless(str_starts_with($employee->employee_id_number, 'TEMP-'), 404);
    }

    private function formData(): array
    {
        return [
            'positions' => collect(self::POSITIONS)
                ->merge(Employee::query()->distinct()->orderBy('position')->pluck('position'))
                ->unique()->values(),
            'departments' => Employee::query()->distinct()->orderBy('department')->pluck('department'),
        ];
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        $request->merge([
            'employee_id_number' => strtoupper(trim((string) $request->input('employee_id_number'))),
            'name' => trim((string) $request->input('name')),
            'position' => trim((string) $request->input('position')),
            'department' => trim((string) $request->input('department')),
        ]);

        return $request->validate([
            'employee_id_number' => ['required', 'string', 'max:30', Rule::unique('employees', 'employee_id_number')->ignore($employee?->id)],
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'position' => 'required|string|max:120',
        ]);
    }
}