<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class StaffDevice extends Model
{
    // Barcode prefix by owner and device type: UL = UNBS laptop, UT = UNBS tablet, PL = personal laptop, PT = personal tablet.
    public const CODE_PREFIXES = [
        'organization' => ['Laptop' => 'UL', 'Tablet' => 'UT'],
        'personal' => ['Laptop' => 'PL', 'Tablet' => 'PT'],
    ];

    protected $fillable = [
        'employee_id', 'sticker_code', 'owner_type', 'device_type', 'make_model',
        'serial_number', 'asset_tag', 'is_active', 'sticker_printed_at', 'registered_by',
        'issued_at', 'issued_by', 'collected_at', 'collected_by', 'issue_closed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sticker_printed_at' => 'datetime',
            'issued_at' => 'datetime',
            'collected_at' => 'datetime',
            'issue_closed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    // Issued to the employee inside the premises and not yet checked out or cleared by Admin.
    public function scopeIssuedOpen(Builder $query): Builder
    {
        return $query->whereNotNull('issued_at')->whereNull('issue_closed_at');
    }

    public function isIssuedOpen(): bool
    {
        return $this->issued_at !== null && $this->issue_closed_at === null;
    }

    // "Laptop Lenovo ThinkPad (UL-01365)"
    public function label(): string
    {
        return trim($this->device_type . ' ' . $this->make_model) . ' (' . $this->sticker_code . ')';
    }

    // UNBS devices: the last digits of the asset tag (154-IT-LP-1365 -> UL-01365).
    // Anything else, or a tag without trailing digits: the device's own number.
    // A code that is already taken gets a letter: UL-01365, UL-01365A, UL-01365B...
    public static function makeCode(string $ownerType, string $deviceType, ?string $assetTag, int $number): string
    {
        $prefix = self::CODE_PREFIXES[$ownerType][$deviceType];

        if ($ownerType === 'organization' && $assetTag && preg_match('/(\d+)$/', $assetTag, $m)) {
            $base = sprintf('%s-%05d', $prefix, (int) substr($m[1], -4));
        } else {
            $base = sprintf('%s-%05d', $prefix, $number);
        }

        foreach (array_merge([''], range('A', 'Z')) as $letter) {
            if (! static::where('sticker_code', $base . $letter)->exists()) {
                return $base . $letter;
            }
        }

        throw ValidationException::withMessages([
            'asset_tag' => 'Too many devices end with the same digits. Please tell the administrator.',
        ]);
    }
}