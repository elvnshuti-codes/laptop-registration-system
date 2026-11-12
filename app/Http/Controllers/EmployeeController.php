<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public const POSITIONS = [
        'Executive Director', 'Director', 'Principal Officer', 'Senior Officer', 'Officer', 'Graduate Trainee',
    ];

    public function index()
    {
        return view('staff.employees.index', ['employees' => Employee::orderBy('name')->get()]);
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

    private function formData(): array
    {
        return [
            'positions' => self::POSITIONS,
            'departments' => Employee::query()->distinct()->orderBy('department')->pluck('department'),
        ];
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        $request->merge([
            'employee_id_number' => strtoupper(trim((string) $request->input('employee_id_number'))),
            'name' => trim((string) $request->input('name')),
            'department' => trim((string) $request->input('department')),
        ]);

        return $request->validate([
            'employee_id_number' => ['required', 'string', 'max:30', Rule::unique('employees', 'employee_id_number')->ignore($employee?->id)],
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'position' => ['required', Rule::in(self::POSITIONS)],
        ]);
    }
}