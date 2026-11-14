<?php

namespace App\Services;

use App\Models\Department;
use App\Models\DepartmentAlias;
use App\Models\Employee;
use App\Models\StaffDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Reads the employee list and the UNBS laptop list (Excel or CSV), matches each laptop to an employee
// by name and department, and saves the result. plan() changes nothing; apply() saves the plan.
class StaffImportService
{
    private const TAG_PATTERN = '/^[A-Z0-9._\/-]+$/';

    // ---------------------------------------------------------------- reading

    public function readEmployees(string $path): array
    {
        return $this->parseEmployees($this->load($path));
    }

    public function readLaptops(string $path): array
    {
        return $this->parseLaptops($this->load($path));
    }

    // The first sheet of the file as rows of cells.
    private function load(string $path): array
    {
        return IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
    }

    // Employee list: Employee ID, First name, Other name, Last name, Position, Department.
    public function parseEmployees(array $data): array
    {
        [$headerIndex, $headers] = $this->findHeader($data);

        $col = [
            'id' => $this->column($headers, ['employee id', 'staff id', 'employee number', 'employee no', 'id']),
            'first' => $this->column($headers, ['first name', 'firstname', 'first']),
            'other' => $this->column($headers, ['other name', 'other names', 'middle name', 'other', 'middle']),
            'last' => $this->column($headers, ['last name', 'lastname', 'surname', 'last']),
            'position' => $this->column($headers, ['position', 'designation', 'title']),
            'department' => $this->column($headers, ['department', 'dept']),
        ];

        $this->requireColumns('employee list', $col, ['id' => 'Employee ID', 'first' => 'First name', 'last' => 'Last name'], $headers);

        $rows = [];

        foreach (array_slice($data, $headerIndex + 1, null, true) as $i => $row) {
            $item = [
                'row' => $i + 1,
                'id' => strtoupper(str_replace(' ', '', $this->cell($row, $col['id']))),
                'first' => $this->cell($row, $col['first']),
                'other' => $this->cell($row, $col['other']),
                'last' => $this->cell($row, $col['last']),
                'position' => $this->cell($row, $col['position']),
                'department' => $this->cell($row, $col['department']),
            ];

            if (implode('', array_slice($item, 1)) === '') {
                continue; // an empty row
            }

            $rows[] = $item;
        }

        return $rows;
    }

    // Laptop list: Asset tag, Employee name, Department.
    public function parseLaptops(array $data): array
    {
        [$headerIndex, $headers] = $this->findHeader($data);

        $col = [
            'tag' => $this->column($headers, ['asset tag', 'asset', 'tag']),
            'name' => $this->column($headers, ['employee name', 'staff name', 'name', 'owner']),
            'department' => $this->column($headers, ['department', 'dept']),
        ];

        $this->requireColumns('laptop list', $col, ['tag' => 'Asset tag', 'name' => 'Employee name'], $headers);

        $rows = [];

        foreach (array_slice($data, $headerIndex + 1, null, true) as $i => $row) {
            $item = [
                'row' => $i + 1,
                'tag' => strtoupper(preg_replace('/\s+/u', '', $this->cell($row, $col['tag']))),
                'name' => $this->cell($row, $col['name']),
                'department' => $this->cell($row, $col['department']),
            ];

            if (implode('', array_slice($item, 1)) === '') {
                continue;
            }

            $rows[] = $item;
        }

        return $rows;
    }

    // The heading row is the first of the top rows that has at least two filled cells.
    private function findHeader(array $data): array
    {
        foreach (array_slice($data, 0, 10, true) as $i => $row) {
            $headers = array_map(fn ($cell) => mb_strtolower($this->text($cell)), $row);

            if (count(array_filter($headers, fn ($h) => $h !== '')) >= 2) {
                return [$i, $headers];
            }
        }

        throw new \RuntimeException('The file has no heading row. The first row must hold the column names.');
    }

    // Exact heading first, then a heading that contains the word.
    private function column(array $headers, array $needles): ?int
    {
        foreach ($needles as $needle) {
            $found = array_search($needle, $headers, true);

            if ($found !== false) {
                return $found;
            }
        }

        foreach ($needles as $needle) {
            if (strlen($needle) < 4) {
                continue;
            }

            foreach ($headers as $i => $heading) {
                if ($heading !== '' && str_contains($heading, $needle)) {
                    return $i;
                }
            }
        }

        return null;
    }

    private function requireColumns(string $list, array $col, array $required, array $headers): void
    {
        $missing = [];

        foreach ($required as $key => $label) {
            if ($col[$key] === null) {
                $missing[] = $label;
            }
        }

        if ($missing) {
            throw new \RuntimeException(
                "The {$list} is missing these columns: " . implode(', ', $missing)
                . '. Columns found: ' . (implode(', ', array_filter($headers)) ?: 'none') . '.'
            );
        }
    }

    private function cell(array $row, ?int $column): string
    {
        return $column === null ? '' : $this->text($row[$column] ?? '');
    }

    private function text(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    // ---------------------------------------------------------------- matching

    // Decides what would happen, without saving anything.
    // $existing: employees (id => name, department), asset_tags (tag => true),
    //            org_owners (employee id => asset tag), temp_max (highest TEMP number used so far),
    //            departments (department name or alias, as a key => official department name, or null for "no department").
    public function plan(array $employeeRows, array $laptopRows, array $existing = []): array
    {
        $existing += ['employees' => [], 'asset_tags' => [], 'org_owners' => [], 'temp_max' => 0, 'departments' => []];

        // Department names I could not recognise, with how often each was seen.
        $unknown = [];

        // A department as typed -> its official name and a key to compare by.
        // Unrecognised text counts as "department not known", so it never causes a false mismatch.
        $resolve = function (?string $raw, ?string $list) use ($existing, &$unknown): array {
            $raw = trim((string) $raw);

            if ($raw === '') {
                return ['name' => null, 'key' => ''];
            }

            $key = self::departmentKey($raw);

            if (array_key_exists($key, $existing['departments'])) {
                $name = $existing['departments'][$key];

                return ['name' => $name, 'key' => $name === null ? '' : self::departmentKey($name)];
            }

            if ($list !== null) {
                $unknown[$key]['text'] ??= $raw;
                $unknown[$key]['count'] = ($unknown[$key]['count'] ?? 0) + 1;
                $unknown[$key]['lists'][$list] = true;
            }

            return ['name' => $raw, 'key' => ''];
        };

        // 1. The employee list.
        $people = [];
        $skipped = [];

        foreach ($employeeRows as $r) {
            if ($r['id'] === '' || trim($r['first'] . $r['last']) === '') {
                $skipped[] = [
                    'list' => 'Employee list', 'row' => $r['row'],
                    'what' => trim("{$r['id']} {$r['first']} {$r['other']} {$r['last']}") ?: '(empty)',
                    'reason' => 'Missing employee ID or name.',
                ];
                continue;
            }

            if (isset($people[$r['id']])) {
                $skipped[] = [
                    'list' => 'Employee list', 'row' => $r['row'], 'what' => $r['id'],
                    'reason' => "Employee ID {$r['id']} appears more than once. The first one (row {$people[$r['id']]['row']}) was used.",
                ];
                continue;
            }

            $dept = $resolve($r['department'], 'Employee list');

            $people[$r['id']] = [
                'id' => $r['id'],
                'row' => $r['row'],
                // All the names that are filled in, kept in this order.
                'name' => implode(' ', array_filter([$r['first'], $r['other'], $r['last']], fn ($part) => $part !== '')),
                'position' => $r['position'] !== '' ? $r['position'] : null,
                'department' => $dept['name'],
                'dept_key' => $dept['key'],
                'action' => isset($existing['employees'][$r['id']]) ? 'update' : 'create',
            ];
        }

        // 2. Everyone a laptop can be matched to: the employee list, plus employees already in the system.
        $pool = [];

        foreach ($people as $p) {
            $pool[$p['id']] = $this->poolEntry($p['id'], $p['name'], $p['department'], $p['dept_key']);
        }

        foreach ($existing['employees'] as $id => $e) {
            $id = (string) $id;

            if (isset($pool[$id]) || str_starts_with($id, 'TEMP-')) {
                continue;
            }

            $known = $resolve($e['department'], null);
            $pool[$id] = $this->poolEntry($id, $e['name'], $known['name'], $known['key']);
        }

        // 3. The laptop list.
        $taken = $existing['asset_tags'];
        $tagRows = [];
        $hasDevice = $existing['org_owners'];
        $next = (int) $existing['temp_max'] + 1;

        $devices = [];
        $placeholders = [];
        $flags = [];
        $report = [];

        foreach ($laptopRows as $l) {
            $tag = $l['tag'];
            $line = [
                'row' => $l['row'], 'tag' => $tag, 'laptop_name' => $l['name'], 'laptop_dept' => $l['department'],
                'employee_id' => null, 'employee_name' => null, 'outcome' => '', 'note' => '',
            ];

            if ($tag === '' || $l['name'] === '') {
                $report[] = [...$line, 'outcome' => 'skipped', 'note' => 'Missing asset tag or employee name.'];
                continue;
            }

            if (! preg_match(self::TAG_PATTERN, $tag)) {
                $report[] = [...$line, 'outcome' => 'skipped', 'note' => 'The asset tag may only contain letters, digits and - / . _ characters.'];
                continue;
            }

            if (isset($taken[$tag])) {
                $first = $tagRows[$tag] ?? null;
                $report[] = [...$line, 'outcome' => 'skipped', 'note' => $first
                    ? "This asset tag was already used on row {$first['row']} ({$first['name']}). Decide who really has it."
                    : 'This asset tag is already registered in the system.'];
                continue;
            }

            $words = $this->words($l['name']);
            $lapDept = $resolve($l['department'], 'Laptop list');
            $dept = $lapDept['key'];

            // Two or more names in common, in any order, means the same name.
            $candidates = array_filter($pool, fn ($e) => count(array_intersect($words, $e['words'])) >= 2);
            $spelling = false;

            // No exact match: allow names that differ by one letter (Kasule / Kasulo).
            if (! $candidates) {
                $candidates = array_filter($pool, fn ($e) => $this->closeWords($words, $e['words']) >= 2);
                $spelling = (bool) $candidates;
            }

            $available = array_filter($candidates, fn ($e) => ! isset($hasDevice[$e['id']]));
            $sameDept = $dept === '' ? [] : array_filter($available, fn ($e) => $e['dept'] === $dept);

            $targetId = null;
            $outcome = '';
            $note = '';
            $placeholder = false;
            $differWith = null;

            if (count($sameDept) === 1) {
                $targetId = reset($sameDept)['id'];
                $outcome = 'matched';
                $note = 'Name and department match.';
            } elseif (count($sameDept) > 1) {
                $placeholder = true;
                $outcome = 'confused';
                $note = 'Several employees have this name and department: ' . $this->describe($sameDept) . '.';
            } elseif (count($available) === 1) {
                $e = reset($available);

                if ($dept === '' || $e['dept'] === '') {
                    $targetId = $e['id'];
                    $outcome = 'similar';
                    $note = 'The names match, but a department is missing in one of the lists.';
                    $flags[$e['id']][] = "Laptop {$tag} was linked by name only: a department is missing in one of the lists.";
                } else {
                    $placeholder = true;
                    $differWith = $e;
                    $outcome = 'differ';
                    $note = "The name matches {$e['id']} ({$e['name']}), but the departments differ: the laptop list says '{$lapDept['name']}', the employee list says '{$e['department']}'.";
                }
            } elseif (count($available) > 1) {
                $placeholder = true;
                $outcome = 'confused';
                $note = 'Several employees match this name: ' . $this->describe($available) . '.';
            } elseif (count($candidates) > 0) {
                $placeholder = true;
                $outcome = 'confused';
                $owners = array_map(fn ($e) => "{$e['id']} ({$e['name']}) already has " . $hasDevice[$e['id']], $candidates);
                $note = 'Each matching employee already has a UNBS laptop: ' . implode('; ', $owners) . '.';
            } else {
                $placeholder = true;
                $outcome = 'nomatch';
                $note = count($words) < 2
                    ? 'The name has only one word, so it cannot be matched.'
                    : 'No employee in the lists matches this name.' . $this->hints($words, $dept, $pool);
            }

            if ($spelling && $outcome === 'matched') {
                $outcome = 'similar';
                $note = "Name and department match, but the spelling differs slightly: laptop list '{$l['name']}', employee list '{$pool[$targetId]['name']}'.";
                $flags[$targetId][] = "Laptop {$tag} was linked although the name is spelled differently in the laptop list ('{$l['name']}').";
            }

            $employeeName = $targetId !== null ? $pool[$targetId]['name'] : null;

            // Confused, unmatched or different-department laptops are kept as a flagged record for you to sort.
            if ($placeholder) {
                $targetId = sprintf('TEMP-%04d', $next++);
                $employeeName = $l['name'];

                $placeholders[] = [
                    'id' => $targetId, 'row' => $l['row'], 'name' => $l['name'], 'position' => null,
                    'department' => $lapDept['name'], 'action' => 'create',
                ];

                $flags[$targetId][] = "Created from the laptop list (row {$l['row']}): {$note} Correct the ID, merge it with the right person, or delete it.";

                if ($differWith) {
                    $flags[$differWith['id']][] = "The laptop list has the same name under '{$lapDept['name']}' (see {$targetId}).";
                }

                $note .= " Saved as {$targetId}.";
            }

            $devices[] = ['employee' => $targetId, 'asset_tag' => $tag, 'row' => $l['row'], 'outcome' => $outcome];
            $hasDevice[$targetId] = $tag;
            $taken[$tag] = true;
            $tagRows[$tag] = ['row' => $l['row'], 'name' => $l['name']];

            $report[] = [...$line, 'employee_id' => $targetId, 'employee_name' => $employeeName, 'outcome' => $outcome, 'note' => $note];
        }

        $by = array_count_values(array_column($report, 'outcome'));
        $people = array_values($people);

        return [
            'people' => $people,
            'placeholders' => $placeholders,
            'devices' => $devices,
            'flags' => $flags,
            'report' => $report,
            'skipped' => $skipped,
            'unknown_departments' => $this->unknownList($unknown),
            'counts' => [
                'create' => count(array_filter($people, fn ($p) => $p['action'] === 'create')),
                'update' => count(array_filter($people, fn ($p) => $p['action'] === 'update')),
                'devices' => count($devices),
                'matched' => $by['matched'] ?? 0,
                'attention' => ($by['similar'] ?? 0) + ($by['differ'] ?? 0) + ($by['confused'] ?? 0) + ($by['nomatch'] ?? 0),
                'skipped' => count($skipped) + ($by['skipped'] ?? 0),
                'placeholders' => count($placeholders),
                'flagged' => count($flags),
            ],
        ];
    }

    private function poolEntry(string $id, string $name, ?string $department, string $deptKey): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'department' => $department,
            'dept' => $deptKey,
            'words' => $this->words($name),
        ];
    }

    // How many of these words equal, or differ by one letter from, a word in the other list (each counted once).
    private function closeWords(array $words, array $others): int
    {
        $count = 0;

        foreach ($words as $word) {
            foreach ($others as $other) {
                if ($word === $other || (mb_strlen($word) >= 5 && mb_strlen($other) >= 5 && levenshtein($word, $other) <= 1)) {
                    $count++;
                    break;
                }
            }
        }

        return $count;
    }

    // When nobody matches by two names, the people who share one name, same department first.
    private function hints(array $words, string $dept, array $pool): string
    {
        $long = array_filter($words, fn ($w) => mb_strlen($w) >= 4);
        $found = [];

        foreach ($pool as $e) {
            $common = count(array_intersect($long, $e['words']));

            if ($common > 0) {
                $found[] = ['score' => $common * 2 + ($dept !== '' && $e['dept'] === $dept ? 1 : 0), 'e' => $e];
            }
        }

        if (! $found) {
            return '';
        }

        usort($found, fn ($a, $b) => $b['score'] <=> $a['score']);

        return ' Possible people: ' . implode('; ', array_map(
            fn ($f) => "{$f['e']['id']} ({$f['e']['name']}, " . ($f['e']['department'] ?? 'no department') . ')',
            array_slice($found, 0, 3)
        )) . '.';
    }

    private function unknownList(array $unknown): array
    {
        $list = array_map(fn ($u) => [
            'text' => $u['text'], 'count' => $u['count'], 'lists' => implode(' and ', array_keys($u['lists'])),
        ], array_values($unknown));

        usort($list, fn ($a, $b) => $b['count'] <=> $a['count']);

        return $list;
    }

    // Lowercase words without punctuation, each once: "Nshuti, Elve" and "elve nshuti" give the same words.
    private function words(string $text): array
    {
        return array_values(array_unique(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY)));
    }

    // The key a department name is looked up by: its words, ignoring capitals, spaces and punctuation.
    // "F&A", "f a" and "F. A" all give "f a".
    public static function departmentKey(string $text): string
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);

        return implode(' ', $words);
    }

    private function describe(array $employees): string
    {
        return implode(', ', array_map(fn ($e) => "{$e['id']} ({$e['name']})", $employees));
    }

    // ---------------------------------------------------------------- saving

    // Saves a plan: employees first, then their UNBS laptops, then the review flags.
    public function apply(array $plan, int $userId): array
    {
        return DB::transaction(function () use ($plan, $userId) {
            $created = 0;
            $updated = 0;
            $linked = 0;
            $notLinked = 0;

            foreach (array_merge($plan['people'], $plan['placeholders']) as $p) {
                $employee = Employee::where('employee_id_number', $p['id'])->first();
                $position = $p['position'] !== null ? Str::limit($p['position'], 120, '') : null;

                if ($employee) {
                    // A blank cell never wipes out what the system already knows.
                    $employee->update(array_filter([
                        'name' => $p['name'],
                        'position' => $position,
                        'department' => $p['department'],
                    ], fn ($value) => $value !== null));
                    $updated++;
                } else {
                    Employee::create([
                        'employee_id_number' => $p['id'],
                        'name' => $p['name'],
                        'position' => $position ?? 'Unknown',
                        'department' => $p['department'] ?? 'Unknown',
                        'is_active' => true,
                    ]);
                    $created++;
                }
            }

            foreach ($plan['devices'] as $d) {
                $employee = Employee::where('employee_id_number', (string) $d['employee'])->first();

                $hasUnbsDevice = $employee && $employee->devices()
                    ->where('is_active', true)->where('owner_type', 'organization')->exists();

                if (! $employee || $hasUnbsDevice || StaffDevice::where('asset_tag', $d['asset_tag'])->exists()) {
                    $notLinked++;
                    continue;
                }

                $device = $employee->devices()->create([
                    'sticker_code' => 'TMP-' . Str::uuid(),
                    'owner_type' => 'organization',
                    'device_type' => 'Laptop',
                    'asset_tag' => $d['asset_tag'],
                    'is_active' => true,
                    'registered_by' => $userId,
                ]);

                $device->update(['sticker_code' => StaffDevice::makeCode('organization', 'Laptop', $d['asset_tag'], $device->id)]);
                $linked++;
            }

            foreach ($plan['flags'] as $id => $notes) {
                $employee = Employee::where('employee_id_number', (string) $id)->first();

                if (! $employee) {
                    continue;
                }

                $employee->update([
                    'needs_review' => true,
                    'review_note' => Str::limit(trim($employee->review_note . ' ' . implode(' ', $notes)), 500),
                ]);
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'linked' => $linked,
                'not_linked' => $notLinked,
                'flagged' => count($plan['flags']),
            ];
        });
    }

    // What the system already holds, so the plan can avoid clashes and match against existing employees.
    public function existingData(): array
    {
        $owners = [];

        foreach (StaffDevice::with('employee:id,employee_id_number')
            ->where('is_active', true)->where('owner_type', 'organization')->get() as $device) {
            $owners[$device->employee->employee_id_number] = $device->asset_tag ?: $device->sticker_code;
        }

        // Official department names and the other ways they are written (NML, F&A, ICT...).
        $departments = [];

        foreach (Department::all() as $department) {
            $departments[self::departmentKey($department->name)] = $department->name;
        }

        foreach (DepartmentAlias::with('department')->get() as $alias) {
            $departments[$alias->alias_key] = $alias->department?->name;
        }

        return [
            'departments' => $departments,
            'employees' => Employee::get(['employee_id_number', 'name', 'department'])
                ->mapWithKeys(fn ($e) => [$e->employee_id_number => ['name' => $e->name, 'department' => $e->department]])
                ->all(),
            'asset_tags' => StaffDevice::whereNotNull('asset_tag')->pluck('asset_tag')
                ->mapWithKeys(fn ($tag) => [$tag => true])->all(),
            'org_owners' => $owners,
            'temp_max' => (int) Employee::where('employee_id_number', 'like', 'TEMP-%')
                ->pluck('employee_id_number')->map(fn ($id) => (int) substr($id, 5))->max(),
        ];
    }

    // Reads the files that were uploaded (either may be missing) and works out the plan.
    public function buildPlan(?string $employeesPath, ?string $laptopsPath): array
    {
        return $this->plan(
            $employeesPath ? $this->readEmployees($employeesPath) : [],
            $laptopsPath ? $this->readLaptops($laptopsPath) : [],
            $this->existingData(),
        );
    }
}