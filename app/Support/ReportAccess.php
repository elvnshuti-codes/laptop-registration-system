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

    // Staff laptop pages.
    public const STAFF = [
        'manage staff devices' => ['Manage employees and staff devices', 'The employee list, staff device registration and stickers.'],
        'view registrations' => ['View old Registrations page', 'The old laptop registrations list. It will be retired.'],
    ];

    // Every permission name managed by the User access screen.
    public static function all(): array
    {
        return array_keys(self::REPORTS + self::AREAS + self::STAFF);
    }
}