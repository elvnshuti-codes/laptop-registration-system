<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentAlias;
use App\Services\StaffImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StaffImportController extends Controller
{
    private const EXTENSIONS = ['xlsx', 'xls', 'csv'];

    // Step 1: choose the two files.
    public function create()
    {
        return view('staff.import.create');
    }

    // Keep the uploaded files for a moment, then show what the import would do.
    public function store(Request $request)
    {
        $request->validate([
            'employees_file' => 'nullable|required_without:laptops_file|file|max:10240',
            'laptops_file' => 'nullable|required_without:employees_file|file|max:10240',
        ], [
            'employees_file.required_without' => 'Choose the employee list, the laptop list, or both.',
            'laptops_file.required_without' => 'Choose the employee list, the laptop list, or both.',
        ]);

        $token = (string) Str::uuid();

        foreach (['employees_file' => 'employees', 'laptops_file' => 'laptops'] as $field => $name) {
            $file = $request->file($field);

            if (! $file) {
                continue;
            }

            $extension = strtolower($file->getClientOriginalExtension());

            if (! in_array($extension, self::EXTENSIONS, true)) {
                Storage::deleteDirectory("staff-imports/{$token}");

                return back()->withErrors([$field => 'Please upload an Excel (.xlsx) or CSV file.']);
            }

            $file->storeAs("staff-imports/{$token}", "{$name}.{$extension}");
        }

        return redirect()->route('staff.import.show', $token);
    }

    // Step 2: the preview. Nothing is saved yet.
    public function show(string $token, StaffImportService $import)
    {
        $plan = $this->plan($token, $import);

        if (is_string($plan)) {
            return redirect()->route('staff.import.create')->withErrors(['import' => $plan]);
        }

        return view('staff.import.preview', [
            'plan' => $plan,
            'token' => $token,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

        // The administrator tells the system what an unrecognised department name means.
    // Choose an existing department, or type a new one. The answer is remembered for the next import.
    public function departments(Request $request, string $token)
    {
        $data = $request->validate([
            'map' => 'required|array',
            'map.*.text' => 'required|string|max:120',
            'map.*.department' => 'nullable|string|max:20',
            'map.*.new' => 'nullable|string|max:120',
        ]);

        $saved = 0;
        $created = 0;

        foreach ($data['map'] as $item) {
            $key = StaffImportService::departmentKey($item['text']);
            $new = trim(preg_replace('/\s+/u', ' ', (string) ($item['new'] ?? '')));
            $choice = $item['department'] ?? '';

            if ($key === '') {
                continue;
            }

            if ($new !== '') {
                // A typed name: use the department if it already exists, otherwise create it.
                $department = Department::firstOrCreate(['name' => $new]);
                $created += $department->wasRecentlyCreated ? 1 : 0;
                $departmentId = $department->id;
            } elseif ($choice === 'none') {
                $departmentId = null; // ignore this text, treat it as no department
            } elseif ($choice !== '' && Department::whereKey((int) $choice)->exists()) {
                $departmentId = (int) $choice;
            } else {
                continue; // "Not sure yet"
            }

            DepartmentAlias::updateOrCreate(['alias_key' => $key], ['department_id' => $departmentId]);
            $saved++;
        }

        $message = $saved > 0
            ? "{$saved} department name(s) saved" . ($created > 0 ? " ({$created} new department(s) created)" : '') . '. The preview below is updated.'
            : 'No department was chosen.';

        return redirect()->route('staff.import.show', $token)->with('success', $message);
    }

    // Step 3: save what the preview showed.
    public function apply(Request $request, string $token, StaffImportService $import)
    {
        $plan = $this->plan($token, $import);

        if (is_string($plan)) {
            return redirect()->route('staff.import.create')->withErrors(['import' => $plan]);
        }

        $result = $import->apply($plan, $request->user()->id);

        Storage::deleteDirectory("staff-imports/{$token}");

        $message = "Import done: {$result['created']} employee(s) added, {$result['updated']} updated, {$result['linked']} UNBS laptop(s) linked.";

        if ($result['flagged'] > 0) {
            $message .= " {$result['flagged']} record(s) need your review (see the Needs review badges).";
        }

        if ($result['not_linked'] > 0) {
            $message .= " {$result['not_linked']} laptop(s) could not be linked because something changed since the preview.";
        }

        return redirect()->route('staff.employees.index')->with('success', $message);
    }

    // The plan, or a message (string) when the files cannot be used.
    private function plan(string $token, StaffImportService $import): array|string
    {
        $employees = $this->path($token, 'employees');
        $laptops = $this->path($token, 'laptops');

        if (! $employees && ! $laptops) {
            return 'These files are no longer available. Please upload them again.';
        }

        try {
            return $import->buildPlan($employees, $laptops);
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        } catch (\Throwable $e) {
            report($e);

            return 'A file could not be read. Make sure it is a valid Excel (.xlsx) or CSV file.';
        }
    }

    private function path(string $token, string $name): ?string
    {
        foreach (Storage::files("staff-imports/{$token}") as $file) {
            if (str_starts_with(basename($file), $name . '.')) {
                return Storage::path($file);
            }
        }

        return null;
    }
}