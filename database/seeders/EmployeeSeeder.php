<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

// 50 fictional employees for testing. Safe to run more than once.
class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $first = ['Grace', 'David', 'Sarah', 'Moses', 'Esther', 'Peter', 'Agnes', 'Samuel', 'Joan', 'Daniel', 'Ruth', 'Isaac', 'Patience',
            'Joseph', 'Mercy', 'Brian', 'Faith', 'Andrew', 'Winnie', 'Emmanuel', 'Juliet', 'Henry', 'Scovia', 'Paul', 'Annet'];
        $last = ['Okello', 'Nakato', 'Mugisha', 'Namukasa', 'Ssemwogerere', 'Atim', 'Byaruhanga', 'Achieng', 'Kato', 'Nabirye',
            'Tumusiime', 'Akello', 'Mutebi', 'Namatovu', 'Opio', 'Kyomuhendo', 'Wasswa', 'Nalubega', 'Ochieng', 'Birungi'];

        $departments = ['Standards Development', 'Testing Services', 'Metrology', 'Certification', 'Market Surveillance',
            'ICT', 'Finance and Administration', 'Human Resources', 'Legal', 'Planning and Research'];

        // [ID prefix, position, how many]
        $grades = [
            ['ED', 'Executive Director', 1],
            ['DR', 'Director', 4],
            ['PO', 'Principal Officer', 8],
            ['SO', 'Senior Officer', 12],
            ['OF', 'Officer', 10],
            ['GT', 'Graduate Trainee', 15],
        ];

        $i = 0;

        foreach ($grades as [$prefix, $position, $count]) {
            for ($n = 1; $n <= $count; $n++) {
                Employee::updateOrCreate(
                    ['employee_id_number' => sprintf('UNBS%s%03d', $prefix, $n)],
                    [
                        'name' => $first[$i % 25] . ' ' . $last[($i * 7) % 20],
                        'department' => $prefix === 'ED' ? 'Executive Office' : $departments[$i % 10],
                        'position' => $position,
                        'is_active' => true,
                    ]
                );

                $i++;
            }
        }
    }
}