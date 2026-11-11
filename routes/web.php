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

Route::get('/', function () {
    return view('welcome');
});

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
    Route::get('/gate/create', [GateController::class, 'create'])->name('gate.create');
    Route::post('/gate', [GateController::class, 'store'])->name('gate.store');
    Route::get('/gate/{visit}/ticket', [GateController::class, 'ticket'])->name('gate.ticket');
    Route::get('/gate/booking/lookup', [GateController::class, 'bookingLookup'])->name('gate.booking.lookup');

    Route::get('/gate/checkout', [GateController::class, 'checkoutIndex'])->name('gate.checkout.index');
    Route::get('/gate/checkout/lookup', [GateController::class, 'checkoutLookup'])->name('gate.checkout.lookup');
    Route::get('/gate/{visit}/checkout', [GateController::class, 'checkoutShow'])->name('gate.checkout.show');
    Route::post('/gate/{visit}/checkout', [GateController::class, 'checkout'])->name('gate.checkout');
    Route::get('/gate/{visit}/checkout/done', [GateController::class, 'checkoutDone'])->name('gate.checkout.done');
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
});
// --- Reports (administrator and receptionists) ---
Route::middleware(['auth', 'force.password.change', 'permission:view reports'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/csv', [ReportController::class, 'csv'])->name('reports.csv');
});

// --- Visitor records (administrator and receptionists) ---
Route::middleware(['auth', 'force.password.change', 'permission:view reports'])->group(function () {
    Route::get('/records', [VisitorRecordController::class, 'index'])->name('records.index');
    Route::get('/records/csv', [VisitorRecordController::class, 'csv'])->name('records.csv');
    Route::get('/records/{visit}', [VisitorRecordController::class, 'show'])->name('records.show');
});
