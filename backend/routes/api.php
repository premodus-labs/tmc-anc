<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CheckpointController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\QueueController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VisitController;
use App\Http\Controllers\Api\V1\VitalsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('auth/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');
        Route::put('auth/password', [AuthController::class, 'changePassword'])
            ->middleware('throttle:5,1')
            ->name('auth.password');

        // No destroy: users are deactivated, not deleted
        Route::apiResource('users', UserController::class)->except('destroy');
        Route::put('users/{user}/roles', [UserController::class, 'syncRoles'])->name('users.roles');
        Route::put('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('staff', [StaffController::class, 'index'])->name('staff.index');

        // No destroy: patient records are kept, not deleted.
        Route::apiResource('patients', PatientController::class)->except('destroy');

        // Visit flow. Visits are closed (completed or cancelled), never edited or deleted.
        Route::get('checkpoints', [CheckpointController::class, 'index'])->name('checkpoints.index');
        Route::get('queue', QueueController::class)->name('queue');
        Route::apiResource('visits', VisitController::class)
            ->only(['index', 'store', 'show'])
            ->parameters(['visits' => 'visitation']);
        Route::post('visits/{visitation}/move', [VisitController::class, 'move'])->name('visits.move');
        Route::post('visits/{visitation}/close', [VisitController::class, 'close'])->name('visits.close');
        Route::get('visits/{visitation}/vitals', [VitalsController::class, 'index'])->name('visits.vitals.index');
        Route::post('visits/{visitation}/vitals', [VitalsController::class, 'store'])->name('visits.vitals.store');
    });
});
