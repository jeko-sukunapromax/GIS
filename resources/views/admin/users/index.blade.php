@extends('layouts.admin')

@section('content')
<style>
    /* Premium Page-Specific Styles */
    .metric-card {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.45) 0%, rgba(15, 23, 42, 0.6) 100%);
        border: 1px solid rgba(148, 163, 184, 0.08);
        border-radius: 18px;
        padding: 24px;
        backdrop-filter: blur(16px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: transparent;
        transition: all 0.3s ease;
    }
    
    .metric-card.blue::before {
        background: linear-gradient(90deg, #0099ff, transparent);
    }
    .metric-card.green::before {
        background: linear-gradient(90deg, #84cc16, transparent);
    }
    .metric-card.amber::before {
        background: linear-gradient(90deg, #f59e0b, transparent);
    }

    .metric-card:hover {
        transform: translateY(-4px);
        border-color: rgba(148, 163, 184, 0.2);
    }
    
    .metric-card.blue:hover {
        box-shadow: 0 12px 24px -10px rgba(0, 153, 255, 0.15), 0 0 1px 1px rgba(0, 153, 255, 0.2);
    }
    .metric-card.green:hover {
        box-shadow: 0 12px 24px -10px rgba(132, 204, 22, 0.15), 0 0 1px 1px rgba(132, 204, 22, 0.2);
    }
    .metric-card.amber:hover {
        box-shadow: 0 12px 24px -10px rgba(245, 158, 11, 0.15), 0 0 1px 1px rgba(245, 158, 11, 0.2);
    }

    .metric-icon {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        transition: all 0.3s ease;
    }
    
    .metric-card:hover .metric-icon {
        transform: scale(1.1) rotate(3deg);
    }

    /* Table styles */
    .premium-table-container {
        background: linear-gradient(180deg, rgba(30, 41, 59, 0.3) 0%, rgba(15, 23, 42, 0.5) 100%);
        border: 1px solid rgba(148, 163, 184, 0.08);
        border-radius: 18px;
        padding: 24px;
        backdrop-filter: blur(16px);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.4);
    }

    .premium-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .premium-table th {
        background: transparent;
        padding: 10px 20px;
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1.5px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.08);
    }

    .premium-table tr.table-row td {
        padding: 16px 20px;
        background: rgba(30, 41, 59, 0.25);
        border-top: 1px solid rgba(255, 255, 255, 0.03);
        border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        font-size: 14px;
        transition: all 0.25s ease;
    }

    .premium-table tr.table-row td:first-child {
        border-left: 1px solid rgba(255, 255, 255, 0.03);
        border-top-left-radius: 16px;
        border-bottom-left-radius: 16px;
    }

    .premium-table tr.table-row td:last-child {
        border-right: 1px solid rgba(255, 255, 255, 0.03);
        border-top-right-radius: 16px;
        border-bottom-right-radius: 16px;
    }

    .premium-table tr.table-row:hover td {
        background: rgba(30, 41, 59, 0.45);
        border-top-color: rgba(56, 189, 248, 0.2);
        border-bottom-color: rgba(56, 189, 248, 0.2);
    }

    .premium-table tr.table-row:hover td:first-child {
        border-left-color: rgba(56, 189, 248, 0.2);
    }

    .premium-table tr.table-row:hover td:last-child {
        border-right-color: rgba(56, 189, 248, 0.2);
    }
    
    /* Input Search */
    .premium-search-input {
        width: 100%;
        padding: 11px 16px 11px 42px;
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid rgba(148, 163, 184, 0.15);
        border-radius: 10px;
        font-size: 14px;
        color: #f8fafc;
        outline: none;
        transition: all 0.3s ease;
    }

    .premium-search-input:focus {
        border-color: var(--accent-blue);
        background: rgba(15, 23, 42, 0.7);
        box-shadow: 0 0 12px rgba(0, 153, 255, 0.2);
    }

    /* Badges */
    .office-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background: rgba(132, 204, 22, 0.08);
        border: 1px solid rgba(132, 204, 22, 0.25);
        color: #bef264;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 700;
        text-shadow: 0 0 4px rgba(132, 204, 22, 0.1);
        transition: all 0.2s ease;
    }
    
    .office-badge.default-office {
        background: rgba(14, 165, 233, 0.08);
        border: 1px solid rgba(14, 165, 233, 0.25);
        color: #38bdf8;
        text-shadow: 0 0 4px rgba(14, 165, 233, 0.1);
    }
    
    .office-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        background: rgba(0, 153, 255, 0.08);
        border: 1px solid rgba(0, 153, 255, 0.25);
        color: #7dd3fc;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 700;
    }

    /* Role selector UI */
    .role-select {
        min-width: 120px;
        padding: 8px 12px;
        background: rgba(15, 23, 42, 0.8);
        border: 1px solid rgba(148, 163, 184, 0.15);
        color: #f8fafc;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        outline: none;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .role-select:focus, .role-select:hover {
        border-color: var(--accent-blue);
        background: rgba(15, 23, 42, 0.95);
    }

    .save-role-btn {
        padding: 8px 12px;
        background: rgba(0, 153, 255, 0.1);
        border: 1px solid rgba(0, 153, 255, 0.25);
        color: #7dd3fc;
        font-size: 12px;
        font-weight: 700;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .save-role-btn:not(:disabled):hover {
        background: rgba(0, 153, 255, 0.2);
        border-color: var(--accent-blue);
        color: #ffffff;
        box-shadow: 0 0 10px rgba(0, 153, 255, 0.25);
        transform: translateY(-1px);
    }
    
    .save-role-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* User Avatar styling */
    .user-avatar-circle {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.1), 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    
    .user-avatar-circle.super-admin {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(139, 92, 246, 0.15));
        border: 1px solid rgba(239, 68, 68, 0.25);
        color: #fca5a5;
        box-shadow: 0 0 15px rgba(239, 68, 68, 0.1);
    }
    
    .user-avatar-circle.admin {
        background: linear-gradient(135deg, rgba(0, 153, 255, 0.15), rgba(56, 189, 248, 0.15));
        border: 1px solid rgba(0, 153, 255, 0.25);
        color: #7dd3fc;
        box-shadow: 0 0 15px rgba(0, 153, 255, 0.1);
    }

    .user-avatar-circle.staff {
        background: linear-gradient(135deg, rgba(148, 163, 184, 0.15), rgba(71, 85, 105, 0.15));
        border: 1px solid rgba(148, 163, 184, 0.25);
        color: #cbd5e1;
    }

    .user-avatar-circle.inactive {
        filter: grayscale(0.65);
        opacity: 0.6;
    }

    .status-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 700;
        background: rgba(16, 185, 129, 0.08);
        border: 1px solid rgba(16, 185, 129, 0.22);
        color: #34d399;
        text-shadow: 0 0 8px rgba(52, 211, 153, 0.15);
        transition: all 0.2s ease;
    }

    .status-chip::before {
        content: '';
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 8px #10b981;
    }

    .status-chip.inactive {
        background: rgba(239, 68, 68, 0.08);
        border-color: rgba(239, 68, 68, 0.22);
        color: #f87171;
        text-shadow: 0 0 8px rgba(248, 113, 113, 0.15);
    }

    .status-chip.inactive::before {
        background: #ef4444;
        box-shadow: 0 0 8px #ef4444;
    }

    .filter-select {
        padding: 11px 16px;
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid rgba(148, 163, 184, 0.15);
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        color: #cbd5e1;
        outline: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .filter-select:hover, .filter-select:focus {
        border-color: var(--accent-blue);
        background: rgba(15, 23, 42, 0.7);
        color: #ffffff;
    }

    .user-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: nowrap;
        white-space: nowrap;
    }

    .mini-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.15);
        background: rgba(15, 23, 42, 0.4);
        color: #cbd5e1;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .mini-action-btn:hover {
        transform: translateY(-1px);
        border-color: rgba(56, 189, 248, 0.4);
        background: rgba(56, 189, 248, 0.08);
        color: #e0f2fe;
        box-shadow: 0 4px 12px rgba(56, 189, 248, 0.1);
    }

    .mini-action-btn.danger {
        border-color: rgba(239, 68, 68, 0.25);
        color: #fca5a5;
        background: rgba(239, 68, 68, 0.05);
    }

    .mini-action-btn.danger:hover {
        background: rgba(239, 68, 68, 0.15);
        border-color: rgba(239, 68, 68, 0.5);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
    }

    .mini-action-btn.success {
        border-color: rgba(16, 185, 129, 0.25);
        color: #a7f3d0;
        background: rgba(16, 185, 129, 0.05);
    }

    .mini-action-btn.success:hover {
        background: rgba(16, 185, 129, 0.15);
        border-color: rgba(16, 185, 129, 0.5);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
    }

    .table-row:hover .user-avatar-circle {
        transform: scale(1.06) rotate(-2deg);
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; margin-bottom: 28px;">
    <div>
        <h1 class="page-title" style="margin-bottom: 6px; font-size: 28px;">BDRRMC Users</h1>
        <p style="color: var(--text-muted); font-size: 14px; letter-spacing: 0.2px;">Accounts synced after successful iHRIS or local test login.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; margin-bottom: 28px;">
    <div class="metric-card blue">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div>
                <div style="color: var(--text-muted); font-size: 11px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase;">Total Synced Users</div>
                <div style="font-family: 'Outfit', sans-serif; color: var(--text-heading); font-size: 36px; font-weight: 800; margin-top: 6px; line-height: 1;">
                    {{ $users->count() }}
                </div>
            </div>
            <div class="metric-icon" style="background: rgba(0, 153, 255, 0.1); color: #7dd3fc; border: 1px solid rgba(0, 153, 255, 0.2);">
                <i class="fa-solid fa-users" style="font-size: 20px;"></i>
            </div>
        </div>
    </div>

    <div class="metric-card green">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div>
                <div style="color: var(--text-muted); font-size: 11px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase;">BDRRMC Office</div>
                <div style="font-family: 'Outfit', sans-serif; color: var(--text-heading); font-size: 36px; font-weight: 800; margin-top: 6px; line-height: 1;">
                    {{ $bdrrmcUsers->count() }}
                </div>
            </div>
            <div class="metric-icon" style="background: rgba(132, 204, 22, 0.1); color: #bef264; border: 1px solid rgba(132, 204, 22, 0.2);">
                <i class="fa-solid fa-shield-halved" style="font-size: 20px;"></i>
            </div>
        </div>
    </div>

    <div class="metric-card amber">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div>
                <div style="color: var(--text-muted); font-size: 11px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase;">Inactive Accounts</div>
                <div style="font-family: 'Outfit', sans-serif; color: {{ $deactivatedUsers->count() > 0 ? '#f59e0b' : 'var(--text-heading)' }}; font-size: 36px; font-weight: 800; margin-top: 6px; line-height: 1;">
                    {{ $deactivatedUsers->count() }}
                </div>
            </div>
            <div class="metric-icon" style="background: rgba(245, 158, 11, 0.1); color: #fde68a; border: 1px solid rgba(245, 158, 11, 0.2);">
                <i class="fa-solid fa-user-slash" style="font-size: 20px;"></i>
            </div>
        </div>
    </div>
</div>

<div class="premium-table-container">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
        <h3 style="font-size: 15px; letter-spacing: 1.2px;">Synced Accounts</h3>
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <select id="roleFilter" class="filter-select" onchange="filterUsers()">
                <option value="">All roles</option>
                @foreach($assignableRoles as $role)
                    <option value="{{ $role }}">{{ ucwords(str_replace('-', ' ', $role)) }}</option>
                @endforeach
            </select>
            <select id="statusFilter" class="filter-select" onchange="filterUsers()">
                <option value="">All status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <div style="position: relative; width: 340px; max-width: 100%;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 15px; color: var(--text-muted);"></i>
                <input type="text" id="userSearchInput" placeholder="Search name, email, office..." onkeyup="filterUsers()" class="premium-search-input">
            </div>
        </div>
    </div>

    <div style="overflow-x: auto; width: 100%;">
        <table id="usersTable" class="premium-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Office</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php
                        $words = explode(' ', $user->name);
                        $initials = '';
                        foreach ($words as $word) {
                            $initials .= strtoupper(substr($word, 0, 1));
                            if (strlen($initials) >= 2) break;
                        }
                        if (empty($initials)) {
                            $initials = 'U';
                        }
                        
                        $userRole = 'staff';
                        if ($user->hasRole('super-admin')) {
                            $userRole = 'super-admin';
                        } elseif ($user->hasRole('admin')) {
                            $userRole = 'admin';
                        }
                        $userStatus = $user->deactivated_at ? 'inactive' : 'active';
                    @endphp
                    <tr class="table-row" data-role="{{ $userRole }}" data-status="{{ $userStatus }}">
                        <td>
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div class="user-avatar-circle {{ $userRole }} {{ $userStatus === 'inactive' ? 'inactive' : '' }}">
                                    {{ $initials }}
                                </div>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="color: var(--text-heading); font-weight: 700; font-size: 14.5px;">{{ $user->name }}</span>
                                    <span style="color: var(--text-muted); font-size: 12px; margin-top: 2px;">
                                        {{ str_ends_with($user->email, '@no-email.local') ? 'No email in iHRIS' : $user->email }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($user->office)
                                <span class="office-badge {{ $user->office === 'BDRRMC Office' ? '' : 'default-office' }}">
                                    <i class="fa-solid fa-shield-halved" style="font-size: 10px;"></i> {{ $user->office }}
                                </span>
                            @else
                                <em style="color: var(--text-muted); font-size: 13px;">Not synced yet</em>
                            @endif
                        </td>
                        <td>
                            @if(auth()->user()?->hasRole('super-admin'))
                                <form method="POST" action="{{ route('admin.users.update-role', $user) }}" style="display: flex; align-items: center; gap: 8px;">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" class="role-select">
                                        @foreach($assignableRoles as $role)
                                            <option value="{{ $role }}" @selected($user->hasRole($role))>{{ ucwords(str_replace('-', ' ', $role)) }}</option>
                                        @endforeach
                                    </select>
                                    <button
                                        type="submit"
                                        class="save-role-btn"
                                        @disabled(auth()->id() === $user->id && $user->hasRole('super-admin'))
                                        title="{{ auth()->id() === $user->id && $user->hasRole('super-admin') ? 'You cannot change your own super-admin role here.' : 'Update role' }}"
                                    >
                                        <i class="fa-solid fa-floppy-disk"></i> Save
                                    </button>
                                </form>
                            @else
                                @forelse($user->roles as $role)
                                    <span class="role-badge">
                                        <i class="fa-solid fa-key" style="font-size: 9px;"></i> {{ $role->name }}
                                    </span>
                                @empty
                                    <span style="color: var(--text-muted); font-size: 12px;">No role</span>
                                @endforelse
                            @endif
                        </td>
                        <td>
                            <span class="status-chip {{ $userStatus === 'inactive' ? 'inactive' : '' }}">
                                {{ $userStatus === 'inactive' ? 'Inactive' : 'Active' }}
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-flex; align-items: center; gap: 6px; color: var(--text-main); font-size: 13px; font-weight: 500;">
                                <i class="fa-regular fa-clock" style="color: rgba(148, 163, 184, 0.4); font-size: 12px;"></i>
                                {{ $user->last_ihris_login_at ? $user->last_ihris_login_at->format('M d, Y h:i A') : '—' }}
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted); font-size: 13px;">
                                <i class="fa-regular fa-calendar" style="color: rgba(148, 163, 184, 0.3); font-size: 12px;"></i>
                                {{ $user->created_at?->format('M d, Y') }}
                            </span>
                        </td>
                        <td>
                            @if(auth()->user()?->hasRole('super-admin'))
                                <div class="user-actions">
                                    @if($user->hasRole('admin') && !auth()->user()->is($user))
                                        <form method="POST" action="{{ route('admin.users.remove-admin', $user) }}" onsubmit="return confirm('Remove admin access from this user?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="mini-action-btn">
                                                <i class="fa-solid fa-user-minus"></i> Remove Admin
                                            </button>
                                        </form>
                                    @endif

                                    @if($user->deactivated_at)
                                        <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="mini-action-btn success">
                                                <i class="fa-solid fa-user-check"></i> Reactivate
                                            </button>
                                        </form>
                                    @elseif(!auth()->user()->is($user))
                                        <form method="POST" action="{{ route('admin.users.deactivate', $user) }}" onsubmit="return confirm('Deactivate this user account?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="mini-action-btn danger">
                                                <i class="fa-solid fa-user-slash"></i> Deactivate
                                            </button>
                                        </form>
                                    @else
                                        <span style="color: var(--text-muted); font-weight: 700; font-size: 12px; letter-spacing: 0.5px; opacity: 0.8;">Current user</span>
                                    @endif
                                </div>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 48px; color: var(--text-muted);">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                <i class="fa-solid fa-users-slash" style="font-size: 32px; color: rgba(148,163,184,0.2);"></i>
                                <span>No synced users yet. Sign in with the BDRRMC test account or a real BDRRMC iHRIS account.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function filterUsers() {
    const filter = document.getElementById('userSearchInput').value.toLowerCase().trim();
    const role = document.getElementById('roleFilter').value;
    const status = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#usersTable tbody tr.table-row');

    rows.forEach((row) => {
        const matchesSearch = row.textContent.toLowerCase().includes(filter);
        const matchesRole = role === '' || row.dataset.role === role;
        const matchesStatus = status === '' || row.dataset.status === status;

        row.style.display = matchesSearch && matchesRole && matchesStatus ? '' : 'none';
    });
}
</script>
@endsection
