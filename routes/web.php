<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LaptopRegistrationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\EntryController;

Route::get('/', function () {
    return view('welcome');
});

// --- Authentication ---
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/change-password', [AuthController::class, 'showChangePasswordForm'])->name('password.change')->middleware('auth');
Route::post('/change-password', [AuthController::class, 'changePassword'])->name('password.change.submit')->middleware('auth');

// --- Administration & laptop registrations ---
Route::resource('users', UserManagementController::class)->middleware(['auth', 'force.password.change']);
Route::resource('registrations', LaptopRegistrationController::class)->middleware(['auth', 'force.password.change']);

// --- Visitor management: Gate (Checkpoint 1) ---
Route::middleware(['auth', 'permission:manage gate visitors'])->group(function () {
    Route::get('/gate/create', [GateController::class, 'create'])->name('gate.create');
    Route::post('/gate', [GateController::class, 'store'])->name('gate.store');
    Route::get('/gate/{visit}/ticket', [GateController::class, 'ticket'])->name('gate.ticket');

    Route::get('/gate/checkout', [GateController::class, 'checkoutIndex'])->name('gate.checkout.index');
    Route::post('/gate/{visit}/checkout', [GateController::class, 'checkout'])->name('gate.checkout');
});

// --- Entry Officer (Checkpoint 2) and barcode labels ---
Route::middleware(['auth', 'permission:manage visitor devices'])->group(function () {
    Route::get('/labels', [LabelController::class, 'index'])->name('labels.index');
    Route::post('/labels/stickers', [LabelController::class, 'generateStickers'])->name('labels.stickers.generate');
    Route::get('/labels/stickers/sheet', [LabelController::class, 'stickerSheet'])->name('labels.stickers.sheet');

    Route::get('/entry', [EntryController::class, 'index'])->name('entry.index');
    Route::get('/entry/lookup', [EntryController::class, 'lookup'])->name('entry.lookup');
    Route::get('/entry/visits/{visit}', [EntryController::class, 'show'])->name('entry.visits.show');
    Route::post('/entry/visits/{visit}/devices', [EntryController::class, 'storeDevice'])->name('entry.devices.store');
    Route::post('/entry/visits/{visit}/no-device', [EntryController::class, 'noDevice'])->name('entry.no-device');

    Route::get('/entry/checkout/lookup', [EntryController::class, 'checkoutLookup'])->name('entry.checkout.lookup');
    Route::get('/entry/visits/{visit}/checkout', [EntryController::class, 'checkoutShow'])->name('entry.visits.checkout');
    Route::post('/entry/visits/{visit}/checkout', [EntryController::class, 'checkoutDevice'])->name('entry.visits.checkout.device');
});