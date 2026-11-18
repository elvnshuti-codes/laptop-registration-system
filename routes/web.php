<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LaptopRegistrationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\ReceptionBookingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VisitPurposeController;
use App\Http\Controllers\SampleTypeController;
use App\Http\Controllers\SampleReceptionController;
use App\Http\Controllers\VisitorRecordController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UnservedReasonController;
use App\Http\Controllers\ReportAccessController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\StaffDeviceController;
use App\Http\Controllers\StaffStickerController;
use App\Http\Controllers\StaffImportController;
use App\Http\Controllers\StaffEntryController;


Route::get('/', [AuthController::class, 'home'])->name('home');

// --- Authentication ---
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/change-password', [AuthController::class, 'showChangePasswordForm'])->name('password.change')->middleware('auth');
Route::post('/change-password', [AuthController::class, 'changePassword'])->name('password.change.submit')->middleware('auth');

// --- My profile (any logged-in user) ---
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// --- Administration & laptop registrations ---
Route::resource('users', UserManagementController::class)
    ->middleware(['auth', 'force.password.change', 'permission:manage users']);
Route::resource('registrations', LaptopRegistrationController::class)
    ->middleware(['auth', 'force.password.change', 'permission:view registrations']);

// --- Visitor management: Gate (Checkpoint 1) ---
Route::middleware(['auth', 'force.password.change', 'permission:manage gate visitors'])->group(function () {
    Route::get('/gate', [GateController::class, 'home'])->name('gate.home');
    Route::get('/gate/create', [GateController::class, 'create'])->name('gate.create');
    Route::post('/gate', [GateController::class, 'store'])->name('gate.store');
    Route::get('/gate/{visit}/ticket', [GateController::class, 'ticket'])->name('gate.ticket');
    Route::get('/gate/booking/lookup', [GateController::class, 'bookingLookup'])->name('gate.booking.lookup');

    Route::get('/gate/checkout', [GateController::class, 'checkoutIndex'])->name('gate.checkout.index');
    Route::get('/gate/checkout/lookup', [GateController::class, 'checkoutLookup'])->name('gate.checkout.lookup');
    Route::get('/gate/{visit}/checkout', [GateController::class, 'checkoutShow'])->name('gate.checkout.show');
    Route::post('/gate/{visit}/checkout', [GateController::class, 'checkout'])->name('gate.checkout');
    Route::get('/gate/{visit}/checkout/done', [GateController::class, 'checkoutDone'])->name('gate.checkout.done');
    Route::get('/gate/checkout/{visit}/receipt', [GateController::class, 'receipt'])->name('gate.checkout.receipt');
});

// --- Entry Officer (Checkpoint 2) ---
Route::middleware(['auth', 'force.password.change', 'permission:manage visitor devices'])->group(function () {
    Route::get('/entry', [EntryController::class, 'index'])->name('entry.index');
    Route::get('/entry/lookup', [EntryController::class, 'lookup'])->name('entry.lookup');
    Route::get('/entry/visits/{visit}', [EntryController::class, 'show'])->name('entry.visits.show');
    Route::post('/entry/visits/{visit}/devices', [EntryController::class, 'storeDevice'])->name('entry.devices.store');
    Route::post('/entry/visits/{visit}/no-device', [EntryController::class, 'noDevice'])->name('entry.no-device');

    Route::get('/entry/checkout/lookup', [EntryController::class, 'checkoutLookup'])->name('entry.checkout.lookup');
    Route::get('/entry/visits/{visit}/checkout', [EntryController::class, 'checkoutShow'])->name('entry.visits.checkout');
    Route::post('/entry/visits/{visit}/checkout', [EntryController::class, 'checkoutDevice'])->name('entry.visits.checkout.device');

    Route::post('/entry/visits/{visit}/clear', [EntryController::class, 'clear'])->name('entry.visits.clear');
    Route::post('/entry/visits/{visit}/send-back', [EntryController::class, 'sendBack'])->name('entry.visits.send-back');
    Route::post('/entry/visits/{visit}/unserved', [EntryController::class, 'unserved'])->name('entry.visits.unserved');
});

// --- Staff devices at Entry (check-in) ---
Route::middleware(['auth', 'force.password.change', 'permission:manage staff entry'])->prefix('entry/staff')->name('entry.staff.')->group(function () {
    Route::get('/', [StaffEntryController::class, 'index'])->name('index');
    Route::get('/employees/{employee}', [StaffEntryController::class, 'show'])->name('show');
    Route::post('/employees/{employee}', [StaffEntryController::class, 'store'])->name('store');
    Route::get('/entries/{entry}', [StaffEntryController::class, 'ticket'])->name('ticket');
});   

// --- Reception, Standard House (Checkpoint 3) ---
Route::middleware(['auth', 'force.password.change', 'permission:manage visitor reception'])->group(function () {
    Route::get('/reception', [ReceptionController::class, 'index'])->name('reception.index');
    Route::get('/reception/lookup', [ReceptionController::class, 'lookup'])->name('reception.lookup');
    Route::get('/reception/visits/{visit}', [ReceptionController::class, 'show'])->name('reception.visits.show');
    Route::post('/reception/visits/{visit}', [ReceptionController::class, 'store'])->name('reception.visits.store');
    Route::post('/reception/records/{reception}/served', [ReceptionController::class, 'markServed'])->name('reception.served');

    // NEW: acknowledge a "left without being served" record
    Route::post('/reception/exceptions/{exception}/acknowledge', [ReceptionController::class, 'acknowledge'])->name('reception.exceptions.acknowledge');

    // Pre-booking (create must come before {booking})
    Route::get('/reception/bookings', [ReceptionBookingController::class, 'index'])->name('reception.bookings.index');
    Route::get('/reception/bookings/create', [ReceptionBookingController::class, 'create'])->name('reception.bookings.create');
    Route::post('/reception/bookings', [ReceptionBookingController::class, 'store'])->name('reception.bookings.store');
    Route::get('/reception/bookings/{booking}', [ReceptionBookingController::class, 'show'])->name('reception.bookings.show');
    Route::post('/reception/bookings/{booking}/cancel', [ReceptionBookingController::class, 'cancel'])->name('reception.bookings.cancel');
});

// --- Sample Reception ---
Route::middleware(['auth', 'force.password.change', 'permission:manage sample reception'])->group(function () {
    Route::get('/sample-reception', [SampleReceptionController::class, 'index'])->name('sample.index');
    Route::post('/sample-reception/lookup', [SampleReceptionController::class, 'lookup'])->name('sample.lookup');
    Route::get('/sample-reception/visits/{visit}', [SampleReceptionController::class, 'show'])->name('sample.visits.show');
    Route::post('/sample-reception/visits/{visit}/served', [SampleReceptionController::class, 'serve'])->name('sample.visits.serve');

    // NEW: acknowledge a "left without being served" record
    Route::post('/sample-reception/exceptions/{exception}/acknowledge', [SampleReceptionController::class, 'acknowledge'])->name('sample.exceptions.acknowledge');
});

// --- Administrator only: barcode labels and the admin console ---
Route::middleware(['auth', 'role:Administrator'])->group(function () {
    Route::get('/labels', [LabelController::class, 'index'])->name('labels.index');
    Route::post('/labels/stickers', [LabelController::class, 'generateStickers'])->name('labels.stickers.generate');
    Route::get('/labels/stickers/sheet', [LabelController::class, 'stickerSheet'])->name('labels.stickers.sheet');

    Route::get('/admin/purposes', [VisitPurposeController::class, 'index'])->name('admin.purposes.index');
    Route::post('/admin/purposes', [VisitPurposeController::class, 'store'])->name('admin.purposes.store');
    Route::put('/admin/purposes/{purpose}', [VisitPurposeController::class, 'update'])->name('admin.purposes.update');
    Route::delete('/admin/purposes/{purpose}', [VisitPurposeController::class, 'destroy'])->name('admin.purposes.destroy');
    Route::get('/admin/sample-types', [SampleTypeController::class, 'index'])->name('admin.sample-types.index');
    Route::post('/admin/sample-types', [SampleTypeController::class, 'store'])->name('admin.sample-types.store');
    Route::put('/admin/sample-types/{sampleType}', [SampleTypeController::class, 'update'])->name('admin.sample-types.update');
    Route::delete('/admin/sample-types/{sampleType}', [SampleTypeController::class, 'destroy'])->name('admin.sample-types.destroy');

    Route::get('/admin/reasons', [UnservedReasonController::class, 'index'])->name('admin.reasons.index');
    Route::post('/admin/reasons', [UnservedReasonController::class, 'store'])->name('admin.reasons.store');
    Route::put('/admin/reasons/{reason}', [UnservedReasonController::class, 'update'])->name('admin.reasons.update');
    Route::delete('/admin/reasons/{reason}', [UnservedReasonController::class, 'destroy'])->name('admin.reasons.destroy');

    Route::get('/admin/report-access', [ReportAccessController::class, 'index'])->name('admin.report-access.index');
    Route::get('/admin/report-access/{user}', [ReportAccessController::class, 'edit'])->name('admin.report-access.edit');
    Route::put('/admin/report-access/{user}', [ReportAccessController::class, 'update'])->name('admin.report-access.update');
});

    // --- Reports and visitor records (the administrator sets access per person) ---
     Route::middleware(['auth', 'force.password.change'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:view reports')->name('reports.index');
    Route::get('/reports/csv', [ReportController::class, 'csv'])
        ->middleware(['permission:view reports', 'permission:export reports'])->name('reports.csv');

    // csv must come before {visit}
    Route::get('/records/csv', [VisitorRecordController::class, 'csv'])
        ->middleware(['permission:view visitor records', 'permission:export reports'])->name('records.csv');
    Route::get('/records', [VisitorRecordController::class, 'index'])
        ->middleware('permission:view visitor records')->name('records.index');
    Route::get('/records/{visit}', [VisitorRecordController::class, 'show'])
        ->middleware('permission:view visitor records')->name('records.show');
});

// --- Visitor records (administrator and receptionists) ---
Route::middleware(['auth', 'force.password.change', 'permission:view reports'])->group(function () {
    Route::get('/records', [VisitorRecordController::class, 'index'])->name('records.index');
    Route::get('/records/csv', [VisitorRecordController::class, 'csv'])->name('records.csv');
    Route::get('/records/{visit}', [VisitorRecordController::class, 'show'])->name('records.show');
});

// --- Staff laptops: employee list ---
Route::middleware(['auth', 'force.password.change', 'permission:manage staff devices'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::post('/employees/{employee}/toggle', [EmployeeController::class, 'toggle'])->name('employees.toggle');
    Route::get('/employees/{employee}/devices', [StaffDeviceController::class, 'index'])->name('devices.index');
    Route::post('/employees/{employee}/devices', [StaffDeviceController::class, 'store'])->name('devices.store');
    Route::post('/devices/{device}/toggle', [StaffDeviceController::class, 'toggle'])->name('devices.toggle');
    Route::get('/employees/{employee}/stickers', [StaffStickerController::class, 'employee'])->name('stickers.employee');
    Route::get('/stickers/pending', [StaffStickerController::class, 'pending'])->name('stickers.pending');
    Route::post('/stickers/printed', [StaffStickerController::class, 'markPrinted'])->name('stickers.printed');
    Route::post('/employees/{employee}/reviewed', [EmployeeController::class, 'reviewed'])->name('employees.reviewed');
    Route::get('/employees/{employee}/merge', [EmployeeController::class, 'mergeForm'])->name('employees.merge.form');
    Route::post('/employees/{employee}/merge', [EmployeeController::class, 'merge'])->name('employees.merge');
    Route::get('/import', [StaffImportController::class, 'create'])->name('import.create');
    Route::post('/import', [StaffImportController::class, 'store'])->name('import.store');
    Route::get('/import/{token}', [StaffImportController::class, 'show'])->whereUuid('token')->name('import.show');
    Route::post('/import/{token}/departments', [StaffImportController::class, 'departments'])->whereUuid('token')->name('import.departments');
    Route::post('/import/{token}/apply', [StaffImportController::class, 'apply'])->whereUuid('token')->name('import.apply'); 
});

    Route::middleware(['auth', 'force.password.change'])->group(function () {
    Route::get('/gate-items', [\App\Http\Controllers\GateItemController::class, 'index'])
        ->middleware('permission:verify gate items')->name('gate.items.index');
    Route::post('/gate-items/{visit}/approve', [\App\Http\Controllers\GateItemController::class, 'approve'])
        ->middleware('permission:verify gate items')->name('gate.items.approve');
    Route::get('/gate-items/report', [\App\Http\Controllers\GateItemController::class, 'report'])
        ->middleware('permission:view gate items report')->name('gate.items.report');

});
   
Route::middleware(['auth', 'force.password.change', 'permission:manage staff entry'])->group(function () {
    Route::get('/entry/staff-checkout', [\App\Http\Controllers\StaffCheckoutController::class, 'start'])->name('entry.staff-out.start');
    Route::get('/entry/staff-checkout/slip', [\App\Http\Controllers\StaffCheckoutController::class, 'slip'])->name('entry.staff-out.slip');
    Route::get('/entry/staff-checkout/{employee}', [\App\Http\Controllers\StaffCheckoutController::class, 'show'])->name('entry.staff-out.show');
    Route::post('/entry/staff-checkout/{employee}', [\App\Http\Controllers\StaffCheckoutController::class, 'store'])->name('entry.staff-out.store');
    Route::post('/entry/staff-requests/{employee}', [\App\Http\Controllers\StaffDeviceRequestController::class, 'store'])->name('entry.staff-requests.store');
});

Route::middleware(['auth', 'force.password.change', 'permission:manage staff devices'])->group(function () {
    Route::get('/issued-devices', [\App\Http\Controllers\StaffIssueController::class, 'index'])->name('issued.index');
    Route::post('/issued-devices/{device}/issue', [\App\Http\Controllers\StaffIssueController::class, 'issue'])->name('issued.issue');
    Route::post('/issued-devices/{device}/collect', [\App\Http\Controllers\StaffIssueController::class, 'collect'])->name('issued.collect');
});

Route::middleware(['auth', 'force.password.change', 'permission:approve device requests'])->prefix('admin/device-requests')->name('admin.device-requests.')->group(function () {
    Route::get('/', [\App\Http\Controllers\StaffDeviceRequestController::class, 'index'])->name('index');
    Route::post('/{deviceRequest}/approve', [\App\Http\Controllers\StaffDeviceRequestController::class, 'approve'])->name('approve');
    Route::post('/{deviceRequest}/decline', [\App\Http\Controllers\StaffDeviceRequestController::class, 'decline'])->name('decline');
    Route::post('/{deviceRequest}/release', [\App\Http\Controllers\StaffDeviceRequestController::class, 'release'])->name('release');
});