@extends('layouts.admin')

@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('admin.barangays.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
    <h1 class="page-title" style="margin-top: 12px; margin-bottom: 0;">Add New Barangay</h1>
</div>

<div class="card">
    <form action="{{ route('admin.barangays.store') }}" method="POST">
        @csrf
        
        <h3 style="margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Basic Information</h3>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Barangay Name <span style="color: red;">*</span></label>
                <input type="text" name="name" class="form-control" required placeholder="e.g. Tococ East">
            </div>
            <div class="form-group">
                <label class="form-label">Municipality</label>
                <input type="text" name="municipality" class="form-control" value="Bayambang">
            </div>
            <div class="form-group">
                <label class="form-label">Province</label>
                <input type="text" name="province" class="form-control" value="Pangasinan">
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
        </div>

        <h3 style="margin-top: 24px; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Geographical Data</h3>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Total Land Area (Hectares)</label>
                <input type="number" step="0.00001" name="total_area" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Population</label>
                <input type="text" name="population" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Primary Land Use</label>
                <input type="text" name="land_use" class="form-control" placeholder="e.g. Agricultural">
            </div>
            <div class="form-group">
                <label class="form-label">Hazard Level</label>
                <input type="text" name="hazard_level" class="form-control" placeholder="e.g. Moderate">
            </div>
        </div>

        <h3 style="margin-top: 24px; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Land Cover Distribution (Hectares)</h3>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Agriculture</label>
                <input type="number" step="0.00001" name="agri_area" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Residential</label>
                <input type="number" step="0.00001" name="residential_area" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Commercial</label>
                <input type="number" step="0.00001" name="commercial_area" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Not Identified</label>
                <input type="number" step="0.00001" name="unidentified_area" class="form-control">
            </div>
        </div>
        
        <h3 style="margin-top: 24px; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Map Details</h3>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Latitude</label>
                <input type="number" step="0.0000001" name="latitude" class="form-control" value="15.8287">
            </div>
            <div class="form-group">
                <label class="form-label">Longitude</label>
                <input type="number" step="0.0000001" name="longitude" class="form-control" value="120.4173">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Boundary Data (GeoJSON Array)</label>
            <textarea name="boundary" class="form-control" rows="3" placeholder="[[lat, lng], [lat, lng], ...]"></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>

        <div style="margin-top: 32px; display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Barangay</button>
            <a href="{{ route('admin.barangays.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
