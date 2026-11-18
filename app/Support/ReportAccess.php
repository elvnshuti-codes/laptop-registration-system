<?php

namespace App\Support;

class ReportAccess
{
    // Reports the administrator can allow, one by one. Add future reports here.
    public const REPORTS = [
        'view reports' => ['Summary reports page', 'Counts, purposes, busy hours and the unserved analysis.'],
        'view visitor records' => ['Visitor records and follow-up list', 'Search any visitor, open their timeline, see the follow-up list.'],
        'export reports' => ['CSV downloads', 'Download reports and records as CSV files.'],
        'view all workload' => ["All receptionists' workload", 'Without this, the person sees only their own figure.'],
    ];

    // Which building's data the person may see in reports and records.
    public const AREAS = [
        'view standard house data' => ['Standard House data', 'Reports and records of Standard House visitors.'],
        'view sample house data' => ['Sample House data', 'Reports and records of Sample House (Testing) visitors.'],
    ];

    // Staff laptop pages. Check-in at Entry is not listed here: it comes with the Entry Officer role.
    public const STAFF = [
        'manage staff devices' => ['Manage employees and staff devices', 'The employee list, staff device registration and stickers.'],
        'view registrations' => ['View old Registrations page', 'The old laptop registrations list. It will be retired.'],
        'approve device requests' => ['Approve staff device requests', 'Decide requests from Entry when a UNBS device was not checked out.'],
    ];

    // Belongings and arms left at the Main Gate.
    public const GATE = [
        'verify gate items' => ['Verify items left at the gate', 'The Items to verify screen: approve belongings and arms left at the gate.'],
        'view gate items report' => ['Gate items accountability report', 'Who left arms and belongings, who collected them, and who verified the records.'],
    ];

    // How a person's screen behaves.
    public const SCREENS = [
        'use kiosk mode' => ['Kiosk mode', 'Shows the Kiosk mode switch: large touch layout and on-screen keyboard.'],
    ];

    // Every permission name managed by the User access screen.
    public static function all(): array
    {
        return array_keys(self::REPORTS + self::AREAS + self::STAFF + self::GATE + self::SCREENS);
    }
}