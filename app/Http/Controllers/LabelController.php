<?php

namespace App\Http\Controllers;

use App\Models\VisitorSticker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabelController extends Controller
{
    private const STICKER_PREFIX = 'UNBS-VS-';
    private const MAX_PER_BATCH = 500;

    // Builds a sticker code from its number: 7 -> UNBS-VS-0007 (widens itself after 9999).
    private static function stickerCode(int $number): string
    {
        return sprintf('%s%04d', self::STICKER_PREFIX, $number);
    }

    // Landing page: generate a new batch or reprint a range.
    public function index()
    {
        $latest = VisitorSticker::latest('id')->first();
        $total = VisitorSticker::count();

        return view('labels.index', compact('latest', 'total'));
    }

    // Creates the next N sticker numbers inside a transaction, then shows the sheet.
    public function generateStickers(Request $request)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:' . self::MAX_PER_BATCH,
        ]);

        [$first, $last] = DB::transaction(function () use ($validated) {
            // Lock the newest row so two people can't generate overlapping numbers.
            $lastCode = VisitorSticker::orderByDesc('id')->lockForUpdate()->value('sticker_code');
            $lastNumber = $lastCode ? (int) substr($lastCode, strlen(self::STICKER_PREFIX)) : 0;

            $start = $lastNumber + 1;
            $end = $lastNumber + $validated['quantity'];

            $now = now();
            $rows = [];
            for ($n = $start; $n <= $end; $n++) {
                $rows[] = ['sticker_code' => self::stickerCode($n), 'created_at' => $now, 'updated_at' => $now];
            }
            VisitorSticker::insert($rows);

            return [$start, $end];
        });

        return redirect()
            ->route('labels.stickers.sheet', ['from' => $first, 'to' => $last])
            ->with('success', "Generated stickers " . self::stickerCode($first) . " to " . self::stickerCode($last) . ".");
    }

    // Printable sheet for a number range (only stickers that exist are shown).
    public function stickerSheet(Request $request)
    {
        $data = $request->validate([
            'from' => 'required|integer|min:1',
            'to'   => 'required|integer|gte:from',
        ]);

        if ($data['to'] - $data['from'] + 1 > self::MAX_PER_BATCH) {
            return back()->withErrors(['to' => 'You can print at most ' . self::MAX_PER_BATCH . ' stickers at a time.']);
        }

        $wanted = collect(range($data['from'], $data['to']))->map(fn ($n) => self::stickerCode($n));
        $existing = VisitorSticker::whereIn('sticker_code', $wanted)->pluck('sticker_code');
        $codes = $wanted->intersect($existing)->values();

        return view('labels.sheet', compact('codes'));
    }
}