<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\Auth\SsoCallbackController;
use App\Http\Controllers\Auth\SsoChooseAccountController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\ClaimExportController;
use App\Http\Controllers\ClaimImportController;
use App\Http\Controllers\CurrentAccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SystemConfigurationController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/account-type/switch', [CurrentAccountController::class, 'update'])->name('account-type.switch');

    Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
    Route::get('/claims/options', [ClaimController::class, 'options'])->name('claims.options');
    Route::get('/claims/{claim}/activities', [ClaimController::class, 'activities'])->name('claims.activities');
    Route::get('/claims/{claim}', [ClaimController::class, 'show'])->name('claims.show');
    Route::patch('/claims/{claim}', [ClaimController::class, 'update'])->name('claims.update');

    Route::post('/claims-export/start', [ClaimExportController::class, 'start'])->name('claims.export.start');
    Route::get('/claims-export/active', [ClaimExportController::class, 'active'])->name('claims.export.active');
    Route::get('/claims-export/history', [ClaimExportController::class, 'history'])->name('claims.export.history');
    Route::get('/claims-export/{claimExport}/progress', [ClaimExportController::class, 'progress'])->name('claims.export.progress');
    Route::get('/claims-export/{claimExport}/download', [ClaimExportController::class, 'download'])->name('claims.export.download');

    Route::get('/claims-import', [ClaimImportController::class, 'index'])->name('claims.import.index');
    Route::post('/claims-import', [ClaimImportController::class, 'store'])->middleware('admin')->name('claims.import.store');
    Route::get('/claims-import/{claimImport}/progress', [ClaimImportController::class, 'progress'])->name('claims.import.progress');

    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/assignments/options', [AssignmentController::class, 'options'])->name('assignments.options');
    Route::get('/assignments/preview', [AssignmentController::class, 'preview'])->name('assignments.preview');
    Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::post('/assignments/distribute', [AssignmentController::class, 'distribute'])->name('assignments.distribute');

    Route::get('/activity-logs', ActivityLogController::class)->name('activity-logs.index');
    Route::get('/activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');
    Route::get('/activity-logs/status-details', [ActivityLogController::class, 'statusDetails'])->name('activity-logs.status-details');
    Route::get('/activity-logs/users/{user}/worked-claim-lines', [ActivityLogController::class, 'workedClaimLines'])->name('activity-logs.worked-claim-lines');

    Route::middleware('admin')->group(function (): void {
        Route::get('/system-configuration', [SystemConfigurationController::class, 'index'])->name('system-configuration.index');
        Route::post('/system-configuration', [SystemConfigurationController::class, 'store'])->name('system-configuration.store');
        Route::post('/system-configuration/{type}/restore-defaults', [SystemConfigurationController::class, 'restoreDefaults'])->name('system-configuration.restore-defaults');
        Route::patch('/system-configuration/{configurationOption}', [SystemConfigurationController::class, 'update'])->name('system-configuration.update');
        Route::delete('/system-configuration/{configurationOption}', [SystemConfigurationController::class, 'destroy'])->name('system-configuration.destroy');

        Route::get('/user-management', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/user-management/available-members', [UserManagementController::class, 'availableMembers'])->name('users.available-members');
        Route::get('/user-management/{user}/members', [UserManagementController::class, 'members'])->name('users.members');
        Route::patch('/user-management/{user}/members', [UserManagementController::class, 'syncMembers'])->name('users.members.sync');
        Route::patch('/user-management/{user}', [UserManagementController::class, 'update'])->name('users.update');
    });
});

require __DIR__.'/settings.php';
