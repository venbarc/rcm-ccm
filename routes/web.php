<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\Auth\SsoCallbackController;
use App\Http\Controllers\Auth\SsoChooseAccountController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\ClaimImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/dashboard')->name('home');
Route::post('/sso/callback', SsoCallbackController::class)->name('sso.callback');

Route::middleware('guest')->group(function (): void {
    Route::get('/sso/choose-account', [SsoChooseAccountController::class, 'create'])->name('sso.choose-account');
    Route::post('/sso/choose-account', [SsoChooseAccountController::class, 'store'])->name('sso.choose-account.store');

    Route::get('/login', fn () => redirect(rtrim((string) config('sso.oneaccess_url'), '/').'/login'))->name('login');
    Route::get('/register', fn () => redirect(rtrim((string) config('sso.oneaccess_url'), '/').'/login'))->name('register');
});

Route::get('/oneaccess', fn () => redirect(rtrim((string) config('sso.oneaccess_url'), '/').'/login'))->name('oneaccess.return');

Route::middleware('auth')->group(function (): void {
    Route::get('/pending-approval', function (Request $request) {
        return $request->user()?->is_approved
            ? redirect()->route('dashboard')
            : Inertia::render('pending-approval');
    })->name('pending-approval');
});

Route::middleware(['auth', 'approved'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
    Route::patch('/claims/{claim}', [ClaimController::class, 'update'])->name('claims.update');

    Route::get('/claims-import', [ClaimImportController::class, 'index'])->name('claims.import.index');
    Route::post('/claims-import', [ClaimImportController::class, 'store'])->middleware('admin')->name('claims.import.store');

    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::post('/assignments/distribute', [AssignmentController::class, 'distribute'])->name('assignments.distribute');

    Route::get('/activity-logs', ActivityLogController::class)->name('activity-logs.index');

    Route::middleware('admin')->group(function (): void {
        Route::get('/user-management', [UserManagementController::class, 'index'])->name('users.index');
        Route::patch('/user-management/{user}', [UserManagementController::class, 'update'])->name('users.update');
    });
});

require __DIR__.'/settings.php';
