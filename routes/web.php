<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\Admin\BarangayController as AdminBarangayController;
use App\Http\Controllers\Admin\MapFeatureController as AdminMapFeatureController;
use App\Http\Controllers\Admin\MapLayerTypeController as AdminMapLayerTypeController;
use App\Http\Controllers\Admin\UploadController as AdminUploadController;
use App\Http\Controllers\Admin\UserController as AdminUserController;

Route::get('/', [BarangayController::class, 'index']);
Route::get('/api/barangays', [BarangayController::class, 'getBarangays']);
Route::get('/api/barangays/{barangay}/features', [BarangayController::class, 'getFeatures']);

Route::prefix('admin')->name('admin.')->middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
])->group(function () {
    Route::middleware('role:staff|admin|super-admin')->group(function () {
        Route::get('map', [AdminBarangayController::class, 'map'])->name('map');
        Route::resource('features', AdminMapFeatureController::class)->only(['index', 'store', 'destroy']);
    });

    Route::middleware('role:admin|super-admin')->group(function () {
        Route::get('uploads', [AdminUploadController::class, 'index'])->name('uploads.index');
        Route::post('uploads/preview', [AdminUploadController::class, 'preview'])->name('uploads.preview');
        Route::post('uploads', [AdminUploadController::class, 'store'])->name('uploads.store');
        Route::delete('uploads/{upload}', [AdminUploadController::class, 'destroy'])->name('uploads.destroy');

        Route::post('barangays/{barangay}/upload-boundary', [AdminBarangayController::class, 'uploadBoundary'])->name('barangays.upload-boundary');
        Route::post('barangays/{barangay}/toggle-visibility', [AdminBarangayController::class, 'toggleVisibility'])->name('barangays.toggle-visibility');
        Route::put('barangays/{barangay}/update-attributes', [AdminBarangayController::class, 'updateAttributes'])->name('barangays.update-attributes');
        Route::get('barangays/{barangay}/manage', [AdminBarangayController::class, 'manage'])->name('barangays.manage');

        Route::resource('barangays', AdminBarangayController::class)->except(['show']);
        Route::resource('layer-types', AdminMapLayerTypeController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::middleware('role:super-admin')->group(function () {
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.update-role');
    });
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user?->hasAnyRole(['super-admin', 'admin'])) {
            return redirect()->route('admin.barangays.index');
        }

        if ($user?->hasRole('staff')) {
            return redirect()->route('admin.features.index');
        }

        return redirect('/');
    })->name('dashboard');
});
