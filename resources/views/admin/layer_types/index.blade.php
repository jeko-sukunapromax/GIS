@extends('layouts.admin')

@section('content')
<div style="width: 100%;">
    @if ($errors->any())
        <div style="margin-bottom: 16px; padding: 14px 16px; border-radius: 10px; border: 1px solid rgba(239, 68, 68, 0.28); background: rgba(239, 68, 68, 0.10); color: #fecaca; font-size: 13px;">
            <strong style="display:block; color:#fca5a5; margin-bottom:6px;">Please review the layer type details.</strong>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

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

        <!-- Stats Grid -->
        <div style="display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="padding: 18px; border: 1px solid var(--border-color); background: linear-gradient(135deg, rgba(15, 23, 42, 0.55) 0%, rgba(30, 41, 59, 0.3) 100%); border-radius: 14px; display: flex; justify-content: space-between; align-items: center; border-left: 3px solid var(--accent-blue); backdrop-filter: var(--glass-blur);">
                <div>
                    <div style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Total Layers</div>
                    <div style="margin-top: 6px; font-size: 22px; font-weight: 800; color: var(--text-heading); font-family: 'Outfit', sans-serif;">{{ $layerTypes->count() }}</div>
                </div>
                <div style="background: rgba(0, 153, 255, 0.1); width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--accent-blue);">
                    <i class="fa-solid fa-layer-group" style="font-size: 16px;"></i>
                </div>
            </div>
            <div style="padding: 18px; border: 1px solid var(--border-color); background: linear-gradient(135deg, rgba(15, 23, 42, 0.55) 0%, rgba(30, 41, 59, 0.3) 100%); border-radius: 14px; display: flex; justify-content: space-between; align-items: center; border-left: 3px solid #10b981; backdrop-filter: var(--glass-blur);">
                <div>
                    <div style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Active</div>
                    <div style="margin-top: 6px; font-size: 22px; font-weight: 800; color: #10b981; font-family: 'Outfit', sans-serif;">{{ $layerTypes->where('is_active', true)->count() }}</div>
                </div>
                <div style="background: rgba(16, 185, 129, 0.1); width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #10b981;">
                    <i class="fa-solid fa-circle-check" style="font-size: 16px;"></i>
                </div>
            </div>
            <div style="padding: 18px; border: 1px solid var(--border-color); background: linear-gradient(135deg, rgba(15, 23, 42, 0.55) 0%, rgba(30, 41, 59, 0.3) 100%); border-radius: 14px; display: flex; justify-content: space-between; align-items: center; border-left: 3px solid #06b6d4; backdrop-filter: var(--glass-blur);">
                <div>
                    <div style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Public</div>
                    <div style="margin-top: 6px; font-size: 22px; font-weight: 800; color: #06b6d4; font-family: 'Outfit', sans-serif;">{{ $layerTypes->where('is_public', true)->count() }}</div>
                </div>
                <div style="background: rgba(6, 182, 212, 0.1); width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #06b6d4;">
                    <i class="fa-solid fa-eye" style="font-size: 16px;"></i>
                </div>
            </div>
            <div style="padding: 18px; border: 1px solid var(--border-color); background: linear-gradient(135deg, rgba(15, 23, 42, 0.55) 0%, rgba(30, 41, 59, 0.3) 100%); border-radius: 14px; display: flex; justify-content: space-between; align-items: center; border-left: 3px solid #8b5cf6; backdrop-filter: var(--glass-blur);">
                <div>
                    <div style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Mapped Features</div>
                    <div style="margin-top: 6px; font-size: 22px; font-weight: 800; color: #8b5cf6; font-family: 'Outfit', sans-serif;">{{ $layerTypes->sum('features_count') }}</div>
                </div>
                <div style="background: rgba(139, 92, 246, 0.1); width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #8b5cf6;">
                    <i class="fa-solid fa-map-location-dot" style="font-size: 16px;"></i>
                </div>
            </div>
        </div>

        @if($layerTypes->isEmpty())
            <div style="padding: 32px; text-align: center; border: 1px dashed rgba(148, 163, 184, 0.25); border-radius: 12px; color: var(--text-muted); background: rgba(15, 23, 42, 0.25);">
                <i class="fa-solid fa-layer-group" style="display:block; font-size: 24px; color: var(--accent-blue); margin-bottom: 10px;"></i>
                <div style="font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">No layer types yet</div>
                <div style="font-size: 13px;">Create the first layer type to organize map features, icons, visibility, and geometry rules.</div>
            </div>
        @else
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Visual Preview</th>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Layer Name</th>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Category Group</th>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Geometry</th>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Publishing</th>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Features</th>
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
                            @if($type->description)
                                <div style="margin-top:4px; color:var(--text-muted); font-size:11px; font-weight:400; max-width:260px;">
                                    {{ $type->description }}
                                </div>
                            @endif
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
                        <td style="padding: 14px 16px;">
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                <span style="display:inline-block; padding:4px 9px; border-radius:12px; font-size:10px; font-weight:700; background: {{ $type->is_active ? 'rgba(16,185,129,0.12)' : 'rgba(148,163,184,0.12)' }}; color: {{ $type->is_active ? '#86efac' : '#cbd5e1' }}; border:1px solid {{ $type->is_active ? 'rgba(16,185,129,0.24)' : 'rgba(148,163,184,0.2)' }};">
                                    {{ $type->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span style="display:inline-block; padding:4px 9px; border-radius:12px; font-size:10px; font-weight:700; background: {{ $type->is_public ? 'rgba(56,189,248,0.12)' : 'rgba(148,163,184,0.12)' }}; color: {{ $type->is_public ? '#7dd3fc' : '#cbd5e1' }}; border:1px solid {{ $type->is_public ? 'rgba(56,189,248,0.24)' : 'rgba(148,163,184,0.2)' }};">
                                    {{ $type->is_public ? 'Public' : 'Admin Only' }}
                                </span>
                                <span style="display:inline-block; padding:4px 9px; border-radius:12px; font-size:10px; font-weight:700; background:rgba(255,255,255,0.05); color:var(--text-muted); border:1px solid rgba(255,255,255,0.08);">
                                    #{{ $type->sort_order }}
                                </span>
                            </div>
                        </td>
                        <td style="padding: 14px 16px;">
                            <span style="display:inline-flex; align-items:center; gap:6px; padding:4px 9px; border-radius:12px; font-size:11px; font-weight:700; background:{{ $type->features_count > 0 ? 'rgba(56,189,248,0.12)' : 'rgba(148,163,184,0.10)' }}; color:{{ $type->features_count > 0 ? '#7dd3fc' : 'var(--text-muted)' }}; border:1px solid {{ $type->features_count > 0 ? 'rgba(56,189,248,0.24)' : 'rgba(148,163,184,0.16)' }};">
                                <i class="fa-solid fa-map-location-dot" style="font-size:10px;"></i> {{ $type->features_count }}
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
                                
                                @if($type->features_count > 0)
                                    <button type="button" title="This layer has mapped features. Mark it inactive instead or move/delete the features first." style="background: rgba(148, 163, 184, 0.08); border: 1px solid rgba(148, 163, 184, 0.16); color: var(--text-muted); padding: 6px 12px; font-size: 12px; border-radius: 6px; cursor: not-allowed; display: flex; align-items: center; gap: 4px; font-family: 'Inter', sans-serif;">
                                        <i class="fa-solid fa-lock"></i> In Use
                                    </button>
                                @else
                                    <form action="{{ route('admin.layer-types.destroy', $type) }}" method="POST" onsubmit="return confirm('Delete this unused layer type?')" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; padding: 6px 12px; font-size: 12px; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 4px; font-family: 'Inter', sans-serif;" onmouseover="this.style.background='rgba(239, 68, 68, 0.35)'; this.style.color='white';" onmouseout="this.style.background='rgba(239, 68, 68, 0.15)'; this.style.color='#fca5a5';">
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            </table>
        @endif
    </div>
</div>

<!-- Premium Blur Overlay Modal for Adding/Editing Layer Types -->
<div id="layer-modal" class="layer-modal-overlay">
    <div class="modal-content-card">
        <!-- Modal Header -->
        <div class="modal-header">
            <h3 id="modal-title-text">
                <i class="fa-solid fa-square-plus" style="color: var(--accent-blue);"></i> Add New Layer Type
            </h3>
            <button type="button" onclick="closeLayerModal()" class="modal-close-btn">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        
        <!-- Modal Form (Submits standard POST / PUT) -->
        <form id="modal-layer-form" action="{{ route('admin.layer-types.store') }}" method="POST" style="display: flex; flex-direction: column; flex: 1; overflow: hidden; margin: 0;">
            @csrf
            <input type="hidden" name="_method" id="modal-form-method" value="">
            
            <!-- Scrollable Form Body -->
            <div class="modal-form-body">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="name" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Layer Name *</label>
                    <input type="text" id="name" name="name" class="modal-input" placeholder="e.g. Evacuation Center" required>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="description" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Description</label>
                    <textarea id="description" name="description" rows="2" class="modal-textarea" placeholder="Short purpose or official use of this layer" style="resize: vertical; min-height: 54px;"></textarea>
                </div>

                <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group" style="margin: 0;">
                        <label for="category" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Category Group</label>
                        <select id="category" name="category" class="modal-select">
                            <option value="critical_facilities">Critical Facilities</option>
                            <option value="drrm">DRRM Group</option>
                            <option value="infrastructure">Infrastructure</option>
                            <option value="population">Population Data</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label for="geom_type" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Geometry Type</label>
                        <select id="geom_type" name="geom_type" class="modal-select">
                            <option value="point">Point (Pin Location)</option>
                            <option value="polyline">Line (Road Path)</option>
                            <option value="polygon">Area (Zone Boundary)</option>
                        </select>
                    </div>
                </div>

                <!-- Visual Marker Selector Grid -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Select Marker Icon *</label>
                    <input type="hidden" id="icon" name="icon" value="fa-solid fa-location-dot">
                    
                    <div class="icon-picker-container">
                        <div class="icon-grid" id="icon-picker-grid">
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
                                     title="{{ $label }}">
                                    <i class="{{ $class }}"></i>
                                    <span>{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 16px; display: grid; grid-template-columns: 1.2fr 1fr; gap: 16px; align-items: start; margin-top: 0;">
                    <div style="margin: 0;">
                        <label for="color" style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Theme Color</label>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <input type="color" id="color" name="color" value="#3b82f6" style="width: 50px; height: 38px; padding: 2px; background: rgba(15, 23, 42, 0.4); border: 1px solid rgba(148, 163, 184, 0.15); border-radius: 8px; cursor: pointer; outline: none; flex-shrink: 0;">
                            
                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                <span class="color-preset-dot" data-color="#3b82f6" style="background: #3b82f6;" title="Classic Blue"></span>
                                <span class="color-preset-dot" data-color="#10b981" style="background: #10b981;" title="Emerald Green"></span>
                                <span class="color-preset-dot" data-color="#ef4444" style="background: #ef4444;" title="Red Alert"></span>
                                <span class="color-preset-dot" data-color="#f59e0b" style="background: #f59e0b;" title="Amber Warning"></span>
                                <span class="color-preset-dot" data-color="#8b5cf6" style="background: #8b5cf6;" title="Purple Tech"></span>
                                <span class="color-preset-dot" data-color="#06b6d4" style="background: #06b6d4;" title="Cyan Glow"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin: 0;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Marker Preview</label>
                        <div style="height: 74px; border: 1px solid rgba(56, 189, 248, 0.16); background: radial-gradient(circle, rgba(15, 23, 42, 0.65) 0%, rgba(9, 13, 22, 0.9) 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; box-shadow: inset 0 0 10px rgba(0, 153, 255, 0.05);">
                            <!-- grid backdrop overlay -->
                            <div style="position: absolute; inset: 0; background: linear-gradient(rgba(0, 153, 255, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 153, 255, 0.03) 1px, transparent 1px); background-size: 8px 8px;"></div>
                            <!-- pulsing ring -->
                            <div id="live-marker-pulse" style="position: absolute; width: 44px; height: 44px; border-radius: 50%; background: rgba(59, 130, 246, 0.2); animation: markerPulse 2s infinite ease-out;"></div>
                            <!-- marker icon inside circle -->
                            <div id="live-marker-preview" style="background-color: #3b82f6; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.4); position: relative; z-index: 1;">
                                <i class="fa-solid fa-location-dot" id="live-marker-icon" style="font-size: 12px;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid-2" style="display:grid; grid-template-columns:1fr 1.5fr; gap:16px; margin-bottom:16px;">
                    <div class="form-group" style="margin:0;">
                        <label for="sort_order" style="display:block; margin-bottom:6px; font-weight:600; color:var(--text-main); font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Sort Order</label>
                        <input type="number" id="sort_order" name="sort_order" class="modal-input" value="0" min="0">
                    </div>
                    <div class="form-group" style="margin:0; display:flex; flex-direction:column; justify-content:center; gap:8px;">
                        <div class="switch-container">
                            <span class="switch-label">Active Layer</span>
                            <label class="switch-control">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <span class="switch-slider"></span>
                            </label>
                        </div>
                        <div class="switch-container">
                            <span class="switch-label">Visible on Public Map</span>
                            <label class="switch-control">
                                <input type="hidden" name="is_public" value="0">
                                <input type="checkbox" id="is_public" name="is_public" value="1" checked>
                                <span class="switch-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="metadata-builder" style="margin-bottom: 8px;">
                    <input type="hidden" id="metadata_schema_json" name="metadata_schema_json">
                    <div class="metadata-builder-header">
                        <div>
                            <label style="display:block; margin-bottom:4px; font-weight:600; color:var(--text-main); font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Metadata Fields</label>
                            <div class="metadata-builder-help">Define the fields that appear when users add this layer type to the map.</div>
                        </div>
                        <div class="metadata-builder-actions">
                            <button type="button" class="metadata-action" onclick="addMetadataField()">
                                <i class="fa-solid fa-plus"></i> Add Field
                            </button>
                            <button type="button" class="metadata-action muted" onclick="resetMetadataToDefault()">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Use Defaults
                            </button>
                        </div>
                    </div>
                    <div id="metadata-field-list" class="metadata-field-list" style="margin-bottom: 10px;"></div>
                    <div id="metadata-empty-state" class="metadata-empty-state">
                        <i class="fa-solid fa-folder-open" style="display: block; font-size: 20px; color: rgba(255,255,255,0.15); margin-bottom: 8px;"></i>
                        No custom fields yet. Leave it empty to use the system default fields for this layer.
                    </div>
                    <button type="button" class="metadata-json-toggle" onclick="toggleGeneratedMetadataJson()" style="width: 100%; justify-content: center; margin-top: 10px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); color: var(--text-muted);">
                        <i class="fa-solid fa-code"></i> View generated JSON
                    </button>
                    <textarea id="metadata-json-preview" class="metadata-json-preview" rows="5" readonly></textarea>
                </div>
            </div>
            
            <!-- Sticky Footer -->
            <div class="modal-form-footer">
                <button type="button" onclick="closeLayerModal()" class="btn btn-secondary" style="padding: 10px 18px; font-size: 13px; border-radius: 8px;">Cancel</button>
                <button type="submit" id="modal-submit-btn" class="btn btn-primary" style="padding: 10px 24px; font-size: 13px; border-radius: 8px;">
                    <i class="fa-solid fa-plus"></i> Create Layer Type
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Custom Scrollbar */
    .modal-form-body::-webkit-scrollbar,
    #icon-picker-grid::-webkit-scrollbar,
    .metadata-json-preview::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .modal-form-body::-webkit-scrollbar-track,
    #icon-picker-grid::-webkit-scrollbar-track,
    .metadata-json-preview::-webkit-scrollbar-track {
        background: rgba(15, 23, 42, 0.2);
        border-radius: 4px;
    }
    .modal-form-body::-webkit-scrollbar-thumb,
    #icon-picker-grid::-webkit-scrollbar-thumb,
    .metadata-json-preview::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.25);
        border-radius: 4px;
    }
    .modal-form-body::-webkit-scrollbar-thumb:hover,
    #icon-picker-grid::-webkit-scrollbar-thumb:hover,
    .metadata-json-preview::-webkit-scrollbar-thumb:hover {
        background: rgba(148, 163, 184, 0.4);
    }

    /* Modal Styling */
    .layer-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(5, 8, 16, 0.82);
        backdrop-filter: blur(10px) saturate(140%);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        padding: 20px;
    }

    .modal-content-card {
        background: #0d1322;
        border: 1px solid rgba(56, 189, 248, 0.16);
        border-radius: 20px;
        width: 100%;
        max-width: 680px;
        max-height: 88vh;
        box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.8),
                    0 0 50px rgba(0, 153, 255, 0.08);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transform: scale(0.96);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 24px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.08);
        background: rgba(13, 19, 34, 0.7);
        backdrop-filter: blur(5px);
        flex-shrink: 0;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 0.5px;
        color: var(--text-heading);
        font-family: 'Outfit', sans-serif;
    }

    .modal-close-btn {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.05);
        color: var(--text-muted);
        cursor: pointer;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        outline: none;
    }

    .modal-close-btn:hover {
        background: rgba(239, 68, 68, 0.15);
        border-color: rgba(239, 68, 68, 0.3);
        color: #fca5a5;
        transform: rotate(90deg);
    }

    .modal-form-body {
        flex: 1;
        overflow-y: auto;
        padding: 24px;
    }

    .modal-form-footer {
        padding: 16px 24px;
        border-top: 1px solid rgba(148, 163, 184, 0.08);
        background: rgba(13, 19, 34, 0.85);
        backdrop-filter: blur(10px);
        flex-shrink: 0;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    /* Form inputs customized */
    .modal-input, .modal-textarea, .modal-select {
        width: 100%;
        background: rgba(15, 23, 42, 0.45);
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 10px;
        color: var(--text-heading);
        padding: 11px 14px;
        font-size: 13.5px;
        outline: none;
        transition: all 0.25s ease;
    }

    .modal-input:focus, .modal-textarea:focus, .modal-select:focus {
        border-color: var(--accent-blue);
        background: rgba(15, 23, 42, 0.7);
        box-shadow: 0 0 12px rgba(0, 153, 255, 0.22);
    }

    .modal-input::placeholder, .modal-textarea::placeholder {
        color: var(--text-muted);
        opacity: 0.55;
    }

    /* Icon Presets Grid */
    .icon-picker-container {
        background: rgba(15, 23, 42, 0.3);
        border: 1px solid rgba(148, 163, 184, 0.1);
        border-radius: 12px;
        padding: 10px;
    }

    .icon-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 8px;
        max-height: 124px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .icon-option {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        aspect-ratio: 1.25;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.04);
        background: rgba(255, 255, 255, 0.01);
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        padding: 6px 4px;
    }

    .icon-option i {
        font-size: 14px;
        color: var(--text-muted);
        margin-bottom: 4px;
        transition: all 0.2s ease;
    }

    .icon-option span {
        font-size: 8px;
        font-weight: 500;
        color: var(--text-muted);
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        width: 100%;
        transition: all 0.2s ease;
    }

    .icon-option:hover {
        background: rgba(255, 255, 255, 0.06) !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
    }

    .icon-option:hover i {
        color: var(--text-heading) !important;
        transform: scale(1.1);
    }

    .icon-option.active {
        background: rgba(0, 153, 255, 0.12) !important;
        border-color: var(--accent-blue) !important;
        box-shadow: 0 0 12px rgba(0, 153, 255, 0.18);
    }

    .icon-option.active i {
        color: var(--accent-blue) !important;
    }

    .icon-option.active span {
        color: var(--accent-blue) !important;
        font-weight: 600;
    }

    /* Custom Toggle Switch */
    .switch-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: rgba(15, 23, 42, 0.35);
        border: 1px solid rgba(148, 163, 184, 0.1);
        border-radius: 10px;
        transition: all 0.25s ease;
    }

    .switch-container:hover {
        border-color: rgba(0, 153, 255, 0.2);
        background: rgba(15, 23, 42, 0.55);
    }

    .switch-label {
        font-size: 12.5px;
        font-weight: 500;
        color: var(--text-main);
    }

    .switch-control {
        position: relative;
        display: inline-block;
        width: 38px;
        height: 20px;
        flex-shrink: 0;
    }

    .switch-control input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .switch-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: rgba(255, 255, 255, 0.08);
        transition: .25s ease;
        border-radius: 20px;
        border: 1px solid rgba(148, 163, 184, 0.14);
    }

    .switch-slider:before {
        position: absolute;
        content: "";
        height: 12px;
        width: 12px;
        left: 3px;
        bottom: 3px;
        background-color: var(--text-muted);
        transition: .25s ease;
        border-radius: 50%;
    }

    .switch-control input:checked + .switch-slider {
        background-color: rgba(0, 153, 255, 0.22);
        border-color: var(--accent-blue);
    }

    .switch-control input:checked + .switch-slider:before {
        transform: translateX(18px);
        background-color: var(--accent-blue);
        box-shadow: 0 0 8px var(--accent-blue);
    }

    /* Color Presets */
    .color-presets-row {
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(15, 23, 42, 0.35);
        border: 1px solid rgba(148, 163, 184, 0.1);
        border-radius: 12px;
        padding: 10px 14px;
    }

    .color-preset-dot {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid rgba(13, 19, 34, 0.8);
        box-shadow: 0 0 0 1.5px rgba(255, 255, 255, 0.12);
        display: inline-block;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .color-preset-dot:hover {
        transform: scale(1.18);
        box-shadow: 0 0 0 1.5px white;
    }

    /* Metadata Builder custom tweaks */
    .metadata-builder {
        border: 1px solid rgba(148, 163, 184, 0.1);
        border-radius: 14px;
        background: rgba(15, 23, 42, 0.22);
        padding: 16px;
    }

    .metadata-builder-header {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
        margin-bottom: 14px;
    }

    .metadata-builder-help {
        color: var(--text-muted);
        font-size: 11px;
        line-height: 1.45;
        margin-top: 2px;
    }

    .metadata-action, .metadata-json-toggle {
        border: 1px solid rgba(0, 153, 255, 0.28);
        background: rgba(0, 153, 255, 0.08);
        color: #7dd3fc;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 700;
        padding: 8px 12px;
        transition: all 0.2s ease;
    }

    .metadata-action:hover, .metadata-json-toggle:hover {
        background: rgba(0, 153, 255, 0.2);
        color: white;
        border-color: rgba(0, 153, 255, 0.4);
        box-shadow: 0 0 8px rgba(0, 153, 255, 0.15);
    }

    .metadata-action.muted {
        border-color: rgba(148, 163, 184, 0.18);
        background: rgba(148, 163, 184, 0.06);
        color: var(--text-muted);
    }

    .metadata-action.muted:hover {
        background: rgba(148, 163, 184, 0.14);
        border-color: rgba(148, 163, 184, 0.3);
        color: var(--text-heading);
        box-shadow: none;
    }

    .metadata-field-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .metadata-field-row {
        border: 1px solid rgba(148, 163, 184, 0.1);
        border-radius: 12px;
        background: rgba(15, 23, 42, 0.4);
        padding: 14px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        margin-bottom: 10px;
    }

    .metadata-field-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr) minmax(130px, 0.7fr);
        gap: 10px;
        align-items: end;
    }

    .metadata-field-row label {
        display: block;
        margin-bottom: 5px;
        color: var(--text-muted);
        font-size: 9.5px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .metadata-field-row input, .metadata-field-row select {
        width: 100%;
        height: 38px;
        padding: 9px 12px;
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 8px;
        color: var(--text-heading);
        outline: none;
        font-size: 12.5px;
        transition: all 0.2s ease;
    }

    .metadata-field-row input:focus, .metadata-field-row select:focus {
        border-color: var(--accent-blue);
        background: rgba(15, 23, 42, 0.75);
    }

    .metadata-field-options {
        display: none;
        margin-top: 10px;
    }
    .metadata-field-options.active {
        display: block;
    }
    .metadata-field-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
    }
    .metadata-required {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--text-main);
        font-size: 12px;
        font-weight: 600;
    }
    .metadata-required input {
        width: auto;
        height: auto;
    }

    .metadata-remove {
        border: 1px solid rgba(239, 68, 68, 0.24);
        background: rgba(239, 68, 68, 0.1);
        color: #fca5a5;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 700;
        padding: 8px 12px;
        transition: all 0.2s ease;
    }

    .metadata-remove:hover {
        background: rgba(239, 68, 68, 0.22);
        color: white;
        border-color: rgba(239, 68, 68, 0.4);
    }

    .metadata-empty-state {
        display: none;
        border: 1px dashed rgba(148, 163, 184, 0.16);
        border-radius: 12px;
        background: rgba(15, 23, 42, 0.18);
        padding: 20px;
        text-align: center;
        color: var(--text-muted);
    }

    .metadata-empty-state.active {
        display: block;
    }

    .metadata-json-preview {
        display: none;
        width: 100%;
        margin-top: 10px;
        padding: 12px;
        background: rgba(15, 23, 42, 0.7);
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 10px;
        font-size: 12px;
        color: #38bdf8;
        outline: none;
        font-family: 'Fira Code', monospace;
        resize: vertical;
    }

    .metadata-json-preview.active {
        display: block;
    }

    @keyframes markerPulse {
        0% {
            transform: scale(0.65);
            opacity: 0.8;
        }
        100% {
            transform: scale(1.4);
            opacity: 0;
        }
    }
</style>

<script>
    let metadataFields = [];
    const metadataTypes = ['text', 'number', 'select', 'textarea', 'boolean', 'date'];

    function metadataFieldTemplate() {
        return {
            key: '',
            label: '',
            type: 'text',
            required: false,
            placeholder: '',
            options: []
        };
    }

    function slugMetadataKey(value) {
        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function setMetadataFields(fields = []) {
        metadataFields = Array.isArray(fields)
            ? fields.map(field => ({
                key: field.key || '',
                label: field.label || '',
                type: metadataTypes.includes(field.type) ? field.type : 'text',
                required: Boolean(field.required),
                placeholder: field.placeholder || '',
                options: Array.isArray(field.options) ? field.options : []
            }))
            : [];

        renderMetadataFields();
    }

    function addMetadataField(field = null) {
        metadataFields.push(field || metadataFieldTemplate());
        renderMetadataFields();
    }

    function removeMetadataField(index) {
        metadataFields.splice(index, 1);
        renderMetadataFields();
    }

    function updateMetadataField(index, property, value) {
        if (!metadataFields[index]) return;

        if (property === 'label') {
            metadataFields[index].label = value;
            if (!metadataFields[index].key) {
                metadataFields[index].key = slugMetadataKey(value);
            }
        } else if (property === 'required') {
            metadataFields[index].required = Boolean(value);
        } else if (property === 'options') {
            metadataFields[index].options = String(value || '')
                .split(/\n|,/)
                .map(option => option.trim())
                .filter(Boolean);
        } else {
            metadataFields[index][property] = value;
        }

        if (property === 'type' && value !== 'select') {
            metadataFields[index].options = [];
        }

        syncMetadataJson();

        if (property === 'type') {
            renderMetadataFields(false);
        }
    }

    function normalizedMetadataFields() {
        return metadataFields
            .map(field => {
                const key = slugMetadataKey(field.key || field.label);
                const type = metadataTypes.includes(field.type) ? field.type : 'text';
                const normalized = {
                    key,
                    label: String(field.label || key).trim(),
                    type,
                    required: Boolean(field.required)
                };

                if (field.placeholder && type !== 'boolean' && type !== 'select') {
                    normalized.placeholder = String(field.placeholder).trim();
                }

                if (type === 'select') {
                    normalized.options = Array.isArray(field.options)
                        ? field.options.map(option => String(option).trim()).filter(Boolean)
                        : [];
                }

                return normalized;
            })
            .filter(field => field.key && field.label);
    }

    function syncMetadataJson() {
        const normalized = normalizedMetadataFields();
        const json = normalized.length ? JSON.stringify(normalized, null, 2) : '';
        const hidden = document.getElementById('metadata_schema_json');
        const preview = document.getElementById('metadata-json-preview');

        if (hidden) hidden.value = json;
        if (preview) preview.value = json || 'System default fields will be used.';
    }

    function renderMetadataFields(shouldSync = true) {
        const list = document.getElementById('metadata-field-list');
        const emptyState = document.getElementById('metadata-empty-state');

        if (!list || !emptyState) return;

        emptyState.classList.toggle('active', metadataFields.length === 0);

        list.innerHTML = metadataFields.map((field, index) => {
            const optionsText = Array.isArray(field.options) ? field.options.join(', ') : '';
            const type = metadataTypes.includes(field.type) ? field.type : 'text';

            return `
                <div class="metadata-field-row">
                    <div class="metadata-field-grid">
                        <div>
                            <label>Field Label</label>
                            <input value="${escapeAttribute(field.label || '')}" placeholder="e.g. Capacity" oninput="updateMetadataField(${index}, 'label', this.value)">
                        </div>
                        <div>
                            <label>Field Key</label>
                            <input value="${escapeAttribute(field.key || '')}" placeholder="e.g. capacity" oninput="updateMetadataField(${index}, 'key', this.value)">
                        </div>
                        <div>
                            <label>Field Type</label>
                            <select onchange="updateMetadataField(${index}, 'type', this.value)">
                                ${metadataTypes.map(option => `<option value="${option}" ${type === option ? 'selected' : ''}>${option}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="metadata-field-options ${type === 'select' ? 'active' : ''}">
                        <label>Dropdown Options</label>
                        <input value="${escapeAttribute(optionsText)}" placeholder="Operational, Needs Maintenance, Inactive" oninput="updateMetadataField(${index}, 'options', this.value)">
                    </div>
                    <div class="metadata-field-options ${['text', 'number', 'textarea', 'date'].includes(type) ? 'active' : ''}">
                        <label>Placeholder</label>
                        <input value="${escapeAttribute(field.placeholder || '')}" placeholder="Optional helper text" oninput="updateMetadataField(${index}, 'placeholder', this.value)">
                    </div>
                    <div class="metadata-field-footer">
                        <label class="metadata-required">
                            <input type="checkbox" ${field.required ? 'checked' : ''} onchange="updateMetadataField(${index}, 'required', this.checked)">
                            Required field
                        </label>
                        <button type="button" class="metadata-remove" onclick="removeMetadataField(${index})">
                            <i class="fa-solid fa-trash-can"></i> Remove
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        if (shouldSync) {
            syncMetadataJson();
        }
    }

    function resetMetadataToDefault() {
        setMetadataFields([]);
    }

    function toggleGeneratedMetadataJson() {
        const preview = document.getElementById('metadata-json-preview');
        if (preview) preview.classList.toggle('active');
    }

    function escapeAttribute(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

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
            const pulse = document.getElementById('live-marker-pulse');
            if (pulse) pulse.style.backgroundColor = '#3b82f6';
            document.getElementById('description').value = '';
            setMetadataFields([]);
            document.getElementById('sort_order').value = 0;
            document.getElementById('is_active').checked = true;
            document.getElementById('is_public').checked = true;
            
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
        document.getElementById('description').value = type.description || '';
        document.getElementById('category').value = type.category;
        document.getElementById('geom_type').value = type.geom_type;
        document.getElementById('icon').value = type.icon;
        document.getElementById('color').value = type.color;
        setMetadataFields(type.metadata_schema || []);
        document.getElementById('sort_order').value = type.sort_order || 0;
        document.getElementById('is_active').checked = Boolean(type.is_active);
        document.getElementById('is_public').checked = Boolean(type.is_public);
        
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
        const pulse = document.getElementById('live-marker-pulse');
        if (pulse) pulse.style.backgroundColor = type.color;
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
        const modalForm = document.getElementById('modal-layer-form');

        if (modalForm) {
            modalForm.addEventListener('submit', function() {
                syncMetadataJson();
            });
        }

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
            const pulse = document.getElementById('live-marker-pulse');
            if (pulse) {
                pulse.style.backgroundColor = this.value;
            }
        });

        // Color preset selection
        document.querySelectorAll('.color-preset-dot').forEach(dot => {
            dot.addEventListener('click', function() {
                const selectedColor = this.getAttribute('data-color');
                colorInput.value = selectedColor;
                livePreview.style.backgroundColor = selectedColor;
                const pulse = document.getElementById('live-marker-pulse');
                if (pulse) {
                    pulse.style.backgroundColor = selectedColor;
                }
            });
        });
    });
</script>
@endsection
