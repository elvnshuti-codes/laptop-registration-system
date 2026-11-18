<?php

namespace App\Http\Controllers;

use App\Models\GadgetSticker;
use App\Models\StaffDevice;
use App\Models\StaffEntryDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Administrator: pre-print stickers for personal gadgets that are not in the system, and see where they are used.
class GadgetStickerController extends Controller
{
    private const MAX_PER_BATCH = 500;

    private static function code(int $number): string
    {
        return sprintf('%s%04d', GadgetSticker::PREFIX, $number);
    }

    public function index()
    {
        $counts = GadgetSticker::selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
        $latest = GadgetSticker::latest('id')->first();

        return view('labels.gadgets', compact('counts', 'latest'));
    }

    public function generate(Request $request)
    {
        $data = $request->validate(['quantity' => 'required|integer|min:1|max:' . self::MAX_PER_BATCH]);

        [$first, $last] = DB::transaction(function () use ($data, $request) {
            $lastCode = GadgetSticker::orderByDesc('id')->lockForUpdate()->value('sticker_code');
            $lastNumber = $lastCode ? (int) substr($lastCode, strlen(GadgetSticker::PREFIX)) : 0;

            $start = $lastNumber + 1;
            $end = $lastNumber + $data['quantity'];

            $now = now();
            $rows = [];
            for ($n = $start; $n <= $end; $n++) {
                $rows[] = [
                    'sticker_code' => self::code($n), 'status' => 'unused',
                    'created_by' => $request->user()->id, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
            GadgetSticker::insert($rows);

            return [$start, $end];
        });

        return redirect()->route('labels.gadgets.sheet', ['from' => $first, 'to' => $last])
            ->with('success', 'Generated ' . self::code($first) . ' to ' . self::code($last) . '.');
    }

    public function sheet(Request $request)
    {
        $data = $request->validate(['from' => 'required|integer|min:1', 'to' => 'required|integer|gte:from']);

        if ($data['to'] - $data['from'] + 1 > self::MAX_PER_BATCH) {
            return back()->withErrors(['to' => 'You can print at most ' . self::MAX_PER_BATCH . ' stickers at a time.']);
        }

        $wanted = collect(range($data['from'], $data['to']))->map(fn ($n) => self::code($n));
        $codes = GadgetSticker::whereIn('sticker_code', $wanted)->orderBy('id')->pluck('sticker_code');

        return view('labels.gadget-sheet', compact('codes'));
    }

    // Every walk-in gadget: owner, what it is, seal number, and how often it has been through Entry.
    public function report()
    {
        $gadgets = StaffDevice::with(['employee', 'registrar'])->where('is_walk_in', true)
            ->orderByDesc('id')->get();

        $ids = $gadgets->pluck('id');

        $visits = StaffEntryDevice::selectRaw('staff_device_id, COUNT(*) as times, MAX(created_at) as last_in')
            ->whereIn('staff_device_id', $ids)->groupBy('staff_device_id')->get()->keyBy('staff_device_id');

        $inside = StaffEntryDevice::whereIn('staff_device_id', $ids)->whereNotNull('open_device_id')
            ->pluck('staff_device_id')->flip();

        return view('labels.gadget-report', compact('gadgets', 'visits', 'inside'));
    }
}