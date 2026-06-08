<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\BarangayController as AdminBarangayController;
use App\Http\Controllers\Admin\BoundaryDownloadController as AdminBoundaryDownloadController;
use App\Http\Controllers\Admin\BoundaryVersionController as AdminBoundaryVersionController;
use App\Http\Controllers\Admin\DataCompletenessController as AdminDataCompletenessController;
use App\Http\Controllers\Admin\GisConverterController as AdminGisConverterController;
use App\Http\Controllers\Admin\MapExportController as AdminMapExportController;
use App\Http\Controllers\Admin\MapFeatureController as AdminMapFeatureController;
use App\Http\Controllers\Admin\MapLayerTypeController as AdminMapLayerTypeController;
use App\Http\Controllers\Admin\UploadController as AdminUploadController;
use App\Http\Controllers\Admin\UserController as AdminUserController;

Route::get('/', [BarangayController::class, 'index']);
Route::get('/api/barangays', [BarangayController::class, 'getBarangays']);
Route::get('/api/barangays/{barangay}/features', [BarangayController::class, 'getFeatures']);
Route::get('/api/geojson', [BarangayController::class, 'geoJson']);

Route::prefix('admin')->name('admin.')->middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
])->group(function () {
    Route::middleware('role:staff|admin|super-admin')->group(function () {
        Route::get('map', [AdminBarangayController::class, 'map'])->name('map');
        Route::get('barangays/{barangay}/features', [AdminBarangayController::class, 'features'])->name('barangays.features');
        Route::get('barangays/{barangay}/spatial-analysis', [AdminBarangayController::class, 'spatialAnalysis'])->name('barangays.spatial-analysis');
        Route::get('map-export', [AdminMapExportController::class, 'index'])->name('map-export.index');
        Route::get('map-export/geojson', [AdminMapExportController::class, 'geoJson'])->name('map-export.geojson');
        Route::patch('features/{feature}/toggle-public', [AdminMapFeatureController::class, 'togglePublic'])->name('features.toggle-public');
        Route::resource('features', AdminMapFeatureController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::middleware('role:admin|super-admin')->group(function () {
        Route::get('uploads', [AdminUploadController::class, 'index'])->name('uploads.index');
        Route::post('uploads/preview', [AdminUploadController::class, 'preview'])->name('uploads.preview');
        Route::get('uploads/preview/converted', [AdminUploadController::class, 'downloadConverted'])->name('uploads.preview.converted');
        Route::delete('uploads/preview', [AdminUploadController::class, 'cancelPreview'])->name('uploads.cancel-preview');
        Route::post('uploads', [AdminUploadController::class, 'store'])->name('uploads.store');
        Route::delete('uploads/{upload}', [AdminUploadController::class, 'destroy'])->name('uploads.destroy');
        Route::get('gis-converter', [AdminGisConverterController::class, 'index'])->name('gis-converter.index');
        Route::post('gis-converter/preview', [AdminGisConverterController::class, 'preview'])->name('gis-converter.preview');
        Route::get('gis-converter/download', [AdminGisConverterController::class, 'download'])->name('gis-converter.download');
        Route::post('gis-converter/import', [AdminGisConverterController::class, 'import'])->name('gis-converter.import');
        Route::delete('gis-converter/preview', [AdminGisConverterController::class, 'cancel'])->name('gis-converter.cancel');

        Route::post('barangays/{barangay}/upload-boundary', [AdminBarangayController::class, 'uploadBoundary'])->name('barangays.upload-boundary');
        Route::post('barangays/{barangay}/toggle-visibility', [AdminBarangayController::class, 'toggleVisibility'])->name('barangays.toggle-visibility');
        Route::put('barangays/{barangay}/update-attributes', [AdminBarangayController::class, 'updateAttributes'])->name('barangays.update-attributes');
        Route::get('barangays/{barangay}/manage', [AdminBarangayController::class, 'manage'])->name('barangays.manage');
        Route::get('barangays/{barangay}/boundary/download', [AdminBoundaryDownloadController::class, 'current'])->name('barangays.boundary.download');
        Route::get('barangays/{barangay}/boundary-versions/{boundaryVersion}/download', [AdminBoundaryDownloadController::class, 'version'])->name('barangays.boundary-versions.download');
        Route::post('barangays/{barangay}/boundary-versions/{boundaryVersion}/restore', [AdminBoundaryVersionController::class, 'restore'])->name('barangays.boundary-versions.restore');
        Route::delete('barangays/{barangay}/boundary-versions/{boundaryVersion}', [AdminBoundaryVersionController::class, 'destroy'])->name('barangays.boundary-versions.destroy');

        Route::get('municipal-boundary', [AdminBarangayController::class, 'municipalBoundary'])->name('municipal-boundary.index');
        Route::post('municipal-boundary/upload', [AdminBarangayController::class, 'uploadMunicipalBoundary'])->name('municipal-boundary.upload');
        Route::delete('municipal-boundary/reset', [AdminBarangayController::class, 'resetMunicipalBoundary'])->name('municipal-boundary.reset');
        Route::get('data-completeness', [AdminDataCompletenessController::class, 'index'])->name('data-completeness.index');
        Route::get('activity-logs', [AdminActivityLogController::class, 'index'])->name('activity-logs.index');

        Route::resource('barangays', AdminBarangayController::class)->except(['show']);
        Route::resource('layer-types', AdminMapLayerTypeController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::middleware('role:super-admin')->group(function () {
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.update-role');
        Route::patch('users/{user}/remove-admin', [AdminUserController::class, 'removeAdmin'])->name('users.remove-admin');
        Route::patch('users/{user}/deactivate', [AdminUserController::class, 'deactivate'])->name('users.deactivate');
        Route::patch('users/{user}/reactivate', [AdminUserController::class, 'reactivate'])->name('users.reactivate');
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
