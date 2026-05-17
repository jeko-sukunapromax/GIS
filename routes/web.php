<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\Admin\BarangayController as AdminBarangayController;
use App\Http\Controllers\Admin\MapFeatureController as AdminMapFeatureController;
use App\Http\Controllers\Admin\MapLayerTypeController as AdminMapLayerTypeController;

Route::get('/', [BarangayController::class, 'index']);
Route::get('/api/barangays', [BarangayController::class, 'getBarangays']);
Route::get('/api/barangays/{barangay}/features', [BarangayController::class, 'getFeatures']);

Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('barangays', AdminBarangayController::class);
    Route::resource('features', AdminMapFeatureController::class);
    Route::resource('layer-types', AdminMapLayerTypeController::class);
});
