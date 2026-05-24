@extends('layouts.admin')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; margin-bottom: 24px;">
    <div>
        <h1 class="page-title" style="margin-bottom: 8px;">BDRRMC Users</h1>
        <p style="color: var(--text-muted); font-size: 14px;">Accounts synced after successful iHRIS or local test login.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="padding: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div>
                <div style="color: var(--text-muted); font-size: 11px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase;">Total Users</div>
                <div style="font-family: 'Outfit', sans-serif; color: var(--text-heading); font-size: 34px; font-weight: 800; margin-top: 6px;">{{ $users->count() }}</div>
            </div>
            <div style="width: 48px; height: 48px; border-radius: 16px; display: grid; place-items: center; background: rgba(56,189,248,0.12); color: #7dd3fc; border: 1px solid rgba(56,189,248,0.22);">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
    </div>

    <div class="card" style="padding: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div>
                <div style="color: var(--text-muted); font-size: 11px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase;">BDRRMC Office</div>
                <div style="font-family: 'Outfit', sans-serif; color: var(--text-heading); font-size: 34px; font-weight: 800; margin-top: 6px;">{{ $bdrrmcUsers->count() }}</div>
            </div>
            <div style="width: 48px; height: 48px; border-radius: 16px; display: grid; place-items: center; background: rgba(132,204,22,0.12); color: #bef264; border: 1px solid rgba(132,204,22,0.22);">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
        </div>
    </div>

    <div class="card" style="padding: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div>
                <div style="color: var(--text-muted); font-size: 11px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase;">Local Test Login</div>
                <div style="font-family: 'Outfit', sans-serif; color: {{ config('services.ihris.test_login.enabled') ? '#bef264' : 'var(--text-heading)' }}; font-size: 26px; font-weight: 800; margin-top: 8px;">
                    {{ config('services.ihris.test_login.enabled') ? 'Enabled' : 'Disabled' }}
                </div>
            </div>
            <div style="width: 48px; height: 48px; border-radius: 16px; display: grid; place-items: center; background: rgba(251,191,36,0.12); color: #fde68a; border: 1px solid rgba(251,191,36,0.22);">
                <i class="fa-solid fa-flask"></i>
            </div>
        </div>
    </div>
</div>

@if (app()->environment('local') && config('services.ihris.test_login.enabled'))
    <div class="card" style="padding: 18px 20px; margin-bottom: 24px; border-color: rgba(56,189,248,0.22); background: rgba(14,165,233,0.08);">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div>
                <div style="color: #bae6fd; font-size: 11px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase;">Available Test Account</div>
                <div style="margin-top: 8px; color: var(--text-heading); font-size: 14px;">
                    <strong>{{ config('services.ihris.test_login.name') }}</strong>
                    <span style="color: var(--text-muted);">({{ config('services.ihris.allowed_office') }} Office)</span>
                </div>
            </div>
            <div style="font-family: monospace; color: #e0f2fe; font-size: 13px; padding: 10px 12px; border-radius: 12px; background: rgba(15,23,42,0.55); border: 1px solid rgba(125,211,252,0.18);">
                {{ config('services.ihris.test_login.email') }} / {{ config('services.ihris.test_login.password') }}
            </div>
        </div>
    </div>
@endif

<div class="card" style="padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 18px;">
        <h3>Synced Accounts</h3>
        <div style="position: relative; width: 320px; max-width: 100%;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 12px; color: var(--text-muted);"></i>
            <input type="text" id="userSearchInput" placeholder="Search name, email, office..." onkeyup="filterUsers()" style="width: 100%; padding: 10px 14px 10px 40px; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; color: #f8fafc; outline: none;">
        </div>
    </div>

    <div class="table-responsive">
        <table id="usersTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Office</th>
                    <th>Roles</th>
                    <th>Last Login</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 38px; height: 38px; border-radius: 14px; display: grid; place-items: center; background: rgba(0,153,255,0.12); color: #7dd3fc; border: 1px solid rgba(125,211,252,0.22);">
                                    <i class="fa-solid fa-user-shield"></i>
                                </div>
                                <div>
                                    <strong style="color: var(--text-heading);">{{ $user->name }}</strong>
                                    <div style="color: var(--text-muted); font-size: 12px; margin-top: 3px;">
                                        {{ str_ends_with($user->email, '@no-email.local') ? 'No email in iHRIS' : $user->email }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($user->office)
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: rgba(132, 204, 22, 0.12); border: 1px solid rgba(132, 204, 22, 0.3); color: #bef264; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                    <i class="fa-solid fa-shield-halved"></i> {{ $user->office }}
                                </span>
                            @else
                                <em style="color: var(--text-muted);">Not synced yet</em>
                            @endif
                        </td>
	                        <td>
	                            @if(auth()->user()?->hasRole('super-admin'))
	                                <form method="POST" action="{{ route('admin.users.update-role', $user) }}" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
	                                    @csrf
	                                    @method('PATCH')
	                                    <select name="role" style="min-width: 136px; padding: 8px 10px; background: rgba(15, 23, 42, 0.72); border: 1px solid rgba(148, 163, 184, 0.22); color: #f8fafc; border-radius: 8px; font-size: 12px; font-weight: 700;">
	                                        @foreach($assignableRoles as $role)
	                                            <option value="{{ $role }}" @selected($user->hasRole($role))>{{ ucwords(str_replace('-', ' ', $role)) }}</option>
	                                        @endforeach
	                                    </select>
	                                    <button
	                                        type="submit"
	                                        class="btn btn-secondary"
	                                        style="padding: 8px 10px; font-size: 12px;"
	                                        @disabled(auth()->id() === $user->id && $user->hasRole('super-admin'))
	                                        title="{{ auth()->id() === $user->id && $user->hasRole('super-admin') ? 'You cannot change your own super-admin role here.' : 'Update role' }}"
	                                    >
	                                        <i class="fa-solid fa-floppy-disk"></i> Save
	                                    </button>
	                                </form>
	                            @else
	                                @forelse($user->roles as $role)
	                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; margin: 2px; background: rgba(0,153,255,0.12); border: 1px solid rgba(0,153,255,0.26); color: #7dd3fc; border-radius: 20px; font-size: 12px; font-weight: 700;">
	                                        <i class="fa-solid fa-key"></i> {{ $role->name }}
	                                    </span>
	                                @empty
	                                    <span style="color: var(--text-muted); font-size: 12px;">No role</span>
	                                @endforelse
	                            @endif
	                        </td>
                        <td>{{ $user->last_ihris_login_at ? $user->last_ihris_login_at->format('M d, Y h:i A') : '—' }}</td>
                        <td>{{ $user->created_at?->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 42px; color: var(--text-muted);">
                            No synced users yet. Sign in with the BDRRMC test account or a real BDRRMC iHRIS account.
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
    const rows = document.querySelectorAll('#usersTable tbody tr');

    rows.forEach((row) => {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
    });
}
</script>
@endsection
