<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LaptopRegistrationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\ProfileController;

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
});

// --- Reception, Standard House (Checkpoint 3) ---
Route::middleware(['auth', 'force.password.change', 'permission:manage visitor reception'])->group(function () {
    Route::get('/reception', [ReceptionController::class, 'index'])->name('reception.index');
    Route::get('/reception/lookup', [ReceptionController::class, 'lookup'])->name('reception.lookup');
    Route::get('/reception/visits/{visit}', [ReceptionController::class, 'show'])->name('reception.visits.show');
    Route::post('/reception/visits/{visit}', [ReceptionController::class, 'store'])->name('reception.visits.store');
    Route::post('/reception/records/{reception}/served', [ReceptionController::class, 'markServed'])->name('reception.served');
});

// --- Barcode labels: Administrator only ---
Route::middleware(['auth', 'role:Administrator'])->group(function () {
    Route::get('/labels', [LabelController::class, 'index'])->name('labels.index');
    Route::post('/labels/stickers', [LabelController::class, 'generateStickers'])->name('labels.stickers.generate');
    Route::get('/labels/stickers/sheet', [LabelController::class, 'stickerSheet'])->name('labels.stickers.sheet');
});