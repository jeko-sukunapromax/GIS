@extends('layouts.admin')

@section('content')
<div style="width: 100%;">
    <!-- ACTIVE LAYER TYPES TABLE -->
    <div style="width: 100%; background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; padding: 24px; backdrop-filter: var(--glass-blur); overflow-x: auto;">
        <!-- Header Flex Layout -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 16px;">
            <h2 style="font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: var(--text-heading); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-list-check" style="color: var(--accent-blue);"></i> Active Map Layer Types
            </h2>
            <button type="button" onclick="openLayerModal()" style="background: var(--accent-blue); color: #090d16; border: none; border-radius: 8px; padding: 8px 16px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); transition: all 0.2s ease; font-family: 'Inter', sans-serif; outline: none;">
                <i class="fa-solid fa-square-plus"></i> Add Layer Type
            </button>
        </div>

        @if(session('success'))
            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #a7f3d0; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
            </div>
        @endif

        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Visual Preview</th>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Layer Name</th>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Category Group</th>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Geometry</th>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">System Code</th>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($layerTypes as $type)
                    <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.03); transition: background-color 0.2s ease;" onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.01)'" onmouseout="this.style.backgroundColor='transparent'">
                        <td style="padding: 14px 16px;">
                            <!-- High fidelity real-time marker preview! -->
                            <div style="background-color: {{ $type->color }}; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.4);">
                                <i class="{{ $type->icon }}" style="font-size: 11px;"></i>
                            </div>
                        </td>
                        <td style="padding: 14px 16px; font-weight: 600; color: var(--text-heading); font-size: 13px;">
                            {{ $type->name }}
                        </td>
                        <td style="padding: 14px 16px;">
                            @php
                                $catName = ucwords(str_replace('_', ' ', $type->category));
                                $catBg = 'rgba(56, 189, 248, 0.12)';
                                $catColor = '#38bdf8';
                                
                                if($type->category == 'drrm') { $catBg = 'rgba(6, 182, 212, 0.12)'; $catColor = '#06b6d4'; }
                                elseif($type->category == 'infrastructure') { $catBg = 'rgba(168, 85, 247, 0.12)'; $catColor = '#c084fc'; }
                                elseif($type->category == 'population') { $catBg = 'rgba(20, 184, 166, 0.12)'; $catColor = '#14b8a6'; }
                            @endphp
                            <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; background: {{ $catBg }}; color: {{ $catColor }}; border: 1px solid {{ $catColor }}25;">
                                {{ $catName }}
                            </span>
                        </td>
                        <td style="padding: 14px 16px;">
                            @php
                                $geomLabel = 'Point Marker';
                                $geomBg = 'rgba(56, 189, 248, 0.1)';
                                $geomColor = '#38bdf8';
                                
                                if($type->geom_type == 'polyline') { $geomLabel = 'Road / Line'; $geomBg = 'rgba(168, 85, 247, 0.1)'; $geomColor = '#c084fc'; }
                                elseif($type->geom_type == 'polygon') { $geomLabel = 'Area / Zone'; $geomBg = 'rgba(234, 179, 8, 0.1)'; $geomColor = '#fef08a'; }
                            @endphp
                            <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; background: {{ $geomBg }}; color: {{ $geomColor }}; border: 1px solid {{ $geomColor }}20;">
                                {{ $geomLabel }}
                            </span>
                        </td>
                        <td style="padding: 14px 16px; font-family: monospace; font-size: 12px; color: var(--text-muted);">
                            {{ $type->code }}
                        </td>
                        <td style="padding: 14px 16px; text-align: right;">
                            <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                <button type="button" 
                                        onclick="editLayerType({{ json_encode($type) }})" 
                                        style="background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.3); color: #bae6fd; padding: 6px 12px; font-size: 12px; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 4px; outline: none; font-family: 'Inter', sans-serif;" 
                                        onmouseover="this.style.background='rgba(56, 189, 248, 0.35)'; this.style.color='white';" 
                                        onmouseout="this.style.background='rgba(56, 189, 248, 0.15)'; this.style.color='#bae6fd';">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                
                                <form action="{{ route('admin.layer-types.destroy', $type) }}" method="POST" onsubmit="return confirm('WARNING: Deleting this layer type will disable its visualization for any active map features! Proceed?')" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; padding: 6px 12px; font-size: 12px; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 4px; font-family: 'Inter', sans-serif;" onmouseover="this.style.background='rgba(239, 68, 68, 0.35)'; this.style.color='white';" onmouseout="this.style.background='rgba(239, 68, 68, 0.15)'; this.style.color='#fca5a5';">
                                        <i class="fa-solid fa-trash-can"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Premium Blur Overlay Modal for Adding Layer Types -->
<div id="layer-modal" style="display: none; position: fixed; inset: 0; background: rgba(9, 13, 22, 0.85); backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; padding: 20px;">
    <div class="modal-content-card" style="background: #0f172a; border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); overflow: hidden; transform: scale(0.95); transition: transform 0.3s ease;">
        <!-- Modal Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border-color); background: rgba(15, 23, 42, 0.6);">
            <h3 id="modal-title-text" style="margin: 0; font-size: 16px; color: var(--text-heading); display: flex; align-items: center; gap: 8px; font-family: 'Outfit', sans-serif;">
                <i class="fa-solid fa-square-plus" style="color: var(--accent-blue);"></i> Add New Layer Type
            </h3>
            <button type="button" onclick="closeLayerModal()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 18px; transition: color 0.2s; outline: none;" onmouseover="this.style.color='white'" onmouseout="this.style.color='var(--text-muted)'">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        
        <!-- Modal Form (Submits standard POST / PUT) -->
        <form id="modal-layer-form" action="{{ route('admin.layer-types.store') }}" method="POST" style="padding: 20px; margin: 0;">
            @csrf
            <input type="hidden" name="_method" id="modal-form-method" value="">
            <div class="form-group" style="margin-bottom: 14px;">
                <label for="name" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Layer Name *</label>
                <input type="text" id="name" name="name" placeholder="e.g. Evacuation Center" required style="width: 100%; padding: 10px 12px; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; font-size: 13px; color: var(--text-heading); outline: none;">
            </div>

            <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div class="form-group" style="margin: 0;">
                    <label for="category" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Category Group</label>
                    <select id="category" name="category" style="width: 100%; padding: 10px 12px; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; font-size: 13px; color: var(--text-heading); outline: none; height: 38px;">
                        <option value="critical_facilities">Critical Facilities</option>
                        <option value="drrm">DRRM Group</option>
                        <option value="infrastructure">Infrastructure</option>
                        <option value="population">Population Data</option>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label for="geom_type" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Geometry Type</label>
                    <select id="geom_type" name="geom_type" style="width: 100%; padding: 10px 12px; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; font-size: 13px; color: var(--text-heading); outline: none; height: 38px;">
                        <option value="point">Point (Pin Location)</option>
                        <option value="polyline">Line (Road Path)</option>
                        <option value="polygon">Area (Zone Boundary)</option>
                    </select>
                </div>
            </div>

            <!-- Visual Marker Selector Grid -->
            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Select Marker Icon *</label>
                <input type="hidden" id="icon" name="icon" value="fa-solid fa-location-dot">
                
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px; max-height: 110px; overflow-y: auto;" id="icon-picker-grid">
                    @php
                        $presets = [
                            'fa-solid fa-location-dot' => 'Standard Pin',
                            'fa-solid fa-building-flag' => 'Barangay Hall',
                            'fa-solid fa-house-medical' => 'Health Center',
                            'fa-solid fa-building' => 'Building',
                            'fa-solid fa-basketball' => 'Covered Court',
                            'fa-solid fa-tent' => 'Evac Center',
                            'fa-solid fa-shield-halved' => 'Police Post',
                            'fa-solid fa-users-gear' => 'Responder',
                            'fa-solid fa-people-roof' => 'Household',
                            'fa-solid fa-faucet-drip' => 'Water Utility',
                            'fa-solid fa-bolt' => 'Power Station',
                            'fa-solid fa-fire-extinguisher' => 'Fire Hydrant',
                            'fa-solid fa-school' => 'School',
                            'fa-solid fa-tower-broadcast' => 'Cell Tower',
                            'fa-solid fa-truck-medical' => 'Ambulance'
                        ];
                    @endphp
                    @foreach($presets as $class => $label)
                        <div class="icon-option {{ $class === 'fa-solid fa-location-dot' ? 'active' : '' }}" 
                             data-icon="{{ $class }}" 
                             title="{{ $label }}"
                             style="display: flex; flex-direction: column; align-items: center; justify-content: center; aspect-ratio: 1; border-radius: 6px; border: 1px solid rgba(255,255,255,0.06); background: rgba(255,255,255,0.02); cursor: pointer; transition: all 0.2s ease;">
                            <i class="{{ $class }}" style="font-size: 13px; color: var(--text-muted); margin-bottom: 2px;"></i>
                            <span style="font-size: 7.5px; color: var(--text-muted); text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%; padding: 0 1px;">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 18px; display: grid; grid-template-columns: 2fr 1fr; gap: 12px; align-items: center; margin-top: 0;">
                <div style="margin: 0;">
                    <label for="color" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Theme Color</label>
                    <input type="color" id="color" name="color" value="#3b82f6" style="width: 100%; height: 38px; padding: 2px; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; outline: none;">
                </div>
                
                <div style="margin: 0;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Preview</label>
                    <div style="display: flex; justify-content: center; align-items: center; height: 38px; background: rgba(15, 23, 42, 0.2); border: 1px dashed var(--border-color); border-radius: 8px;">
                        <div id="live-marker-preview" style="background-color: #3b82f6; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.3); transition: all 0.2s ease;">
                            <i class="fa-solid fa-location-dot" id="live-marker-icon" style="font-size: 10px;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" id="modal-submit-btn" class="btn btn-primary" style="width: 100%; padding: 10px; background: var(--accent-blue); color: #090d16; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); transition: all 0.2s ease; outline: none;">
                <i class="fa-solid fa-plus"></i> Create Layer Type
            </button>
        </form>
    </div>
</div>

<style>
    .icon-option {
        transition: all 0.2s ease;
    }
    .icon-option:hover {
        background: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
    }
    .icon-option:hover i {
        color: var(--text-heading) !important;
    }
    .icon-option.active {
        background: rgba(56, 189, 248, 0.12) !important;
        border-color: var(--accent-blue) !important;
        box-shadow: 0 0 10px rgba(56, 189, 248, 0.15);
    }
    .icon-option.active i {
        color: var(--accent-blue) !important;
    }
    .icon-option.active span {
        color: var(--accent-blue) !important;
    }
</style>

<script>
    // Modal Open/Close Controls
    function openLayerModal(isEdit = false) {
        if (!isEdit) {
            // Reset modal to "Add Mode"
            document.getElementById('modal-title-text').innerHTML = '<i class="fa-solid fa-square-plus" style="color: var(--accent-blue);"></i> Add New Layer Type';
            
            const form = document.getElementById('modal-layer-form');
            form.action = "{{ route('admin.layer-types.store') }}";
            document.getElementById('modal-form-method').value = '';
            
            form.reset();
            
            // Reset preset selector active states
            const iconOptions = document.querySelectorAll('.icon-option');
            iconOptions.forEach(o => o.classList.remove('active'));
            iconOptions[0].classList.add('active');
            document.getElementById('icon').value = 'fa-solid fa-location-dot';
            document.getElementById('live-marker-icon').className = 'fa-solid fa-location-dot';
            document.getElementById('live-marker-preview').style.backgroundColor = '#3b82f6';
            
            document.getElementById('modal-submit-btn').innerHTML = '<i class="fa-solid fa-plus"></i> Create Layer Type';
        }
        
        const modal = document.getElementById('layer-modal');
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.style.opacity = '1';
            modal.querySelector('.modal-content-card').style.transform = 'scale(1)';
        }, 50);
    }

    function closeLayerModal() {
        const modal = document.getElementById('layer-modal');
        modal.style.opacity = '0';
        modal.querySelector('.modal-content-card').style.transform = 'scale(0.95)';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }

    // Populate Modal in "Edit Mode"
    function editLayerType(type) {
        // 1. Change title and action route
        document.getElementById('modal-title-text').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color: var(--accent-blue);"></i> Edit Layer Type';
        
        const form = document.getElementById('modal-layer-form');
        form.action = `/admin/layer-types/${type.id}`;
        document.getElementById('modal-form-method').value = 'PUT';
        
        // 2. Pre-fill form fields
        document.getElementById('name').value = type.name;
        document.getElementById('category').value = type.category;
        document.getElementById('geom_type').value = type.geom_type;
        document.getElementById('icon').value = type.icon;
        document.getElementById('color').value = type.color;
        
        // 3. Mark the active icon option in visual grid
        const iconOptions = document.querySelectorAll('.icon-option');
        iconOptions.forEach(opt => {
            if (opt.getAttribute('data-icon') === type.icon) {
                opt.classList.add('active');
            } else {
                opt.classList.remove('active');
            }
        });
        
        // 4. Update the live visual marker preview
        document.getElementById('live-marker-preview').style.backgroundColor = type.color;
        document.getElementById('live-marker-icon').className = type.icon;
        
        // 5. Change submit button to Save Mode
        document.getElementById('modal-submit-btn').innerHTML = '<i class="fa-solid fa-check"></i> Save Changes';
        
        // 6. Open modal
        openLayerModal(true);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const iconInput = document.getElementById('icon');
        const colorInput = document.getElementById('color');
        const livePreview = document.getElementById('live-marker-preview');
        const liveIcon = document.getElementById('live-marker-icon');
        const iconOptions = document.querySelectorAll('.icon-option');

        // Handle Icon Selection Clicking inside Modal
        iconOptions.forEach(opt => {
            opt.addEventListener('click', function() {
                // Remove active state from all others
                iconOptions.forEach(o => o.classList.remove('active'));
                
                // Add active state to selected
                this.classList.add('active');
                
                // Update hidden input
                const selectedIcon = this.getAttribute('data-icon');
                iconInput.value = selectedIcon;
                
                // Update Live Form Preview Icon
                liveIcon.className = selectedIcon;
            });
        });

        // Handle Live Theme Color Updating inside Modal
        colorInput.addEventListener('input', function() {
            livePreview.style.backgroundColor = this.value;
        });
    });
</script>
@endsection
