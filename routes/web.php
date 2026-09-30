<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MyWorkController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkController;
use App\Http\Controllers\WorkerController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/my-work', [MyWorkController::class, 'index'])->name('my-work');

    Route::middleware('role:super_admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    Route::middleware('role:engineer,site_engineer')->group(function () {
        Route::resource('projects', ProjectController::class);
        Route::resource('locations', LocationController::class)->except(['show']);
        Route::prefix('works/{type}')->where(['type' => 'pillars|walls|bridges'])->group(function () {
            Route::get('/', [WorkController::class, 'index'])->name('works.index');
            Route::get('/create', [WorkController::class, 'create'])->name('works.create');
            Route::post('/', [WorkController::class, 'store'])->name('works.store');
            Route::get('/{id}/edit', [WorkController::class, 'edit'])->name('works.edit');
            Route::put('/{id}', [WorkController::class, 'update'])->name('works.update');
            Route::delete('/{id}', [WorkController::class, 'destroy'])->name('works.destroy');
        });

        Route::resource('materials', MaterialController::class);
        Route::post('/materials/{material}/transactions', [MaterialController::class, 'transact'])->name('materials.transact');

        Route::resource('equipment', EquipmentController::class);
        Route::post('/equipment/{equipment}/usage', [EquipmentController::class, 'usage'])->name('equipment.usage');

        Route::get('/workers/attendance', [WorkerController::class, 'attendance'])->name('workers.attendance');
        Route::post('/workers/attendance', [WorkerController::class, 'storeAttendance'])->name('workers.attendance.store');
        Route::resource('workers', WorkerController::class);
        Route::post('/workers/{worker}/assignments', [WorkerController::class, 'assign'])->name('workers.assign');

        Route::resource('daily-reports', DailyReportController::class)->parameters([
            'daily-reports' => 'dailyReport',
        ]);
        Route::post('/daily-reports/{dailyReport}/review', [DailyReportController::class, 'review'])->name('daily-reports.review');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    });
});
