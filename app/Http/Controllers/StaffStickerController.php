<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\StaffDevice;
use Illuminate\Http\Request;

class StaffStickerController extends Controller
{
    // All active devices of one employee (reprint is always possible).
    public function employee(Employee $employee)
    {
        $employee->setRelation('devices', $employee->devices()
            ->where('is_active', true)->orderBy('owner_type')->orderBy('id')->get());

        return $this->sheet(collect([$employee]), "Stickers for {$employee->name}");
    }

    // Every active device whose sticker has not been marked as printed, grouped by employee.
    public function pending()
    {
        $unprinted = fn ($q) => $q->where('is_active', true)->whereNull('sticker_printed_at')->orderBy('owner_type')->orderBy('id');

        $employees = Employee::whereHas('devices', $unprinted)
            ->with(['devices' => $unprinted])
            ->orderBy('department')->orderBy('name')
            ->get();

        return $this->sheet($employees, 'Stickers not printed yet');
    }

    // Pressed after the stickers printed well.
    public function markPrinted(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|max:500',
            'ids.*' => 'integer|exists:staff_devices,id',
        ]);

        StaffDevice::whereIn('id', $data['ids'])->update(['sticker_printed_at' => now()]);

        return redirect()
            ->route('staff.employees.index')
            ->with('success', count($data['ids']) . ' sticker(s) marked as printed.');
    }

    private function sheet($employees, string $title)
    {
        return view('staff.stickers.sheet', [
            'employees' => $employees,
            'title' => $title,
            'ids' => $employees->flatMap(fn ($e) => $e->devices->pluck('id'))->values(),
        ]);
    }
}