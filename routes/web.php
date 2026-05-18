<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\Admin\BarangayController as AdminBarangayController;
use App\Http\Controllers\Admin\MapFeatureController as AdminMapFeatureController;
use App\Http\Controllers\Admin\MapLayerTypeController as AdminMapLayerTypeController;
use App\Http\Controllers\Admin\UploadController as AdminUploadController;

Route::get('/', [BarangayController::class, 'index']);
Route::get('/api/barangays', [BarangayController::class, 'getBarangays']);
Route::get('/api/barangays/{barangay}/features', [BarangayController::class, 'getFeatures']);

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('map', [AdminBarangayController::class, 'map'])->name('map');
    Route::get('uploads', [AdminUploadController::class, 'index'])->name('uploads.index');
    Route::post('uploads', [AdminUploadController::class, 'store'])->name('uploads.store');
    Route::delete('uploads/{upload}', [AdminUploadController::class, 'destroy'])->name('uploads.destroy');
    
    Route::post('barangays/{barangay}/upload-boundary', [AdminBarangayController::class, 'uploadBoundary'])->name('barangays.upload-boundary');
    Route::post('barangays/{barangay}/toggle-visibility', [AdminBarangayController::class, 'toggleVisibility'])->name('barangays.toggle-visibility');
    Route::put('barangays/{barangay}/update-attributes', [AdminBarangayController::class, 'updateAttributes'])->name('barangays.update-attributes');
    Route::get('barangays/{barangay}/manage', [AdminBarangayController::class, 'manage'])->name('barangays.manage');
    
    Route::resource('barangays', AdminBarangayController::class);
    Route::resource('features', AdminMapFeatureController::class);
    Route::resource('layer-types', AdminMapLayerTypeController::class);
});
