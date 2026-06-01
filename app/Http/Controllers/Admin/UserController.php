<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\IhrisAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Throwable;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->with('roles')
            ->latest('last_ihris_login_at')
            ->latest()
            ->get();

        $bdrrmcUsers = $users->filter(fn (User $user) => str_contains(
            strtoupper((string) $user->office),
            strtoupper(config('services.ihris.allowed_office', 'BDRRMC'))
        ));

        return view('admin.users.index', [
            'users' => $users,
            'bdrrmcUsers' => $bdrrmcUsers,
            'activeUsers' => $users->whereNull('deactivated_at'),
            'deactivatedUsers' => $users->whereNotNull('deactivated_at'),
            'assignableRoles' => $this->assignableRoles(),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super-admin'), 403);

        $validated = $request->validate([
            'role' => ['required', Rule::in($this->assignableRoles())],
        ]);

        if ($request->user()->is($user) && $validated['role'] !== 'super-admin') {
            return back()->with('error', 'You cannot remove your own super-admin role.');
        }

        collect($this->assignableRoles())
            ->each(fn (string $role) => Role::findOrCreate($role, 'web'));

        $user->syncRoles([$validated['role']]);

        app(ActivityLogger::class)->log('user.role_updated', "Updated {$user->name}'s role to {$validated['role']}.", $user, [
            'role' => $validated['role'],
        ], $request);

        return back()->with('success', "{$user->name}'s role was updated to {$validated['role']}.");
    }

    public function removeAdmin(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super-admin'), 403);

        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot remove your own admin access.');
        }

        if ($user->hasRole('super-admin')) {
            return back()->with('error', 'Use the role selector to change a super-admin account.');
        }

        Role::findOrCreate('staff', 'web');
        $user->syncRoles(['staff']);

        app(ActivityLogger::class)->log('user.admin_removed', "Removed admin access from {$user->name}.", $user, [], $request);

        return back()->with('success', "{$user->name}'s admin access was removed. The account is now staff.");
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super-admin'), 403);

        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->forceFill([
            'deactivated_at' => now(),
        ])->save();

        app(ActivityLogger::class)->log('user.deactivated', "Deactivated {$user->name}.", $user, [], $request);

        return back()->with('success', "{$user->name} was deactivated.");
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super-admin'), 403);

        $user->forceFill([
            'deactivated_at' => null,
        ])->save();

        app(ActivityLogger::class)->log('user.reactivated', "Reactivated {$user->name}.", $user, [], $request);

        return back()->with('success', "{$user->name} was reactivated.");
    }

    public function sync(IhrisAuthenticator $ihris): RedirectResponse
    {
        $token = session('ihris_access_token');

        if (! $token) {
            return back()->with('error', 'No iHRIS bearer token found. Please login using a real iHRIS BDRRMC account before syncing users.');
        }

        try {
            $employees = $ihris->officeEmployees($token);
        } catch (Throwable $e) {
            return back()->with('error', 'Unable to sync iHRIS users: '.$e->getMessage());
        }

        $synced = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            $email = $this->findFirstString($employee, ['email', 'email_address', 'username']);
            $ihrisId = $this->findFirstString($employee, ['uuid', 'ihris_id', 'employee_id', 'personnel_id', 'user_id', 'id']);

            if (! $email && $ihrisId) {
                $email = $this->placeholderEmail($ihrisId);
            }

            if (! $email) {
                $skipped++;
                continue;
            }

            $office = $this->findFirstString($employee, ['office', 'office_name', 'department', 'department_name', 'division', 'division_name'])
                ?? 'Municipal Disaster Risk Reduction Management Office';

            $name = $this->findFirstString($employee, ['name', 'full_name', 'fullname', 'employee_name'])
                ?? ($ihrisId ? 'iHRIS User '.Str::limit($ihrisId, 8, '') : $this->nameFromEmail($email));

            $user = User::query()
                ->when($ihrisId, fn ($query) => $query->where('ihris_id', $ihrisId))
                ->orWhere('email', Str::lower($email))
                ->first() ?? new User;

            $user->fill([
                'ihris_id' => $ihrisId,
                'name' => $name,
                'email' => Str::lower($email),
                'office' => $office,
                'ihris_payload' => $employee,
                'password' => $user->exists ? $user->password : Hash::make(Str::random(48)),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            $user->assignRole('admin');
            $synced++;
        }

        app(ActivityLogger::class)->log('users.synced', 'Synced BDRRMC iHRIS users.', null, [
            'synced' => $synced,
            'skipped' => $skipped,
        ], request());

        return back()->with('success', "BDRRMC users synced successfully. Synced: {$synced}. Skipped missing email/UUID: {$skipped}.");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    private function findFirstString(array $payload, array $keys): ?string
    {
        $normalizedKeys = array_map(fn (string $key) => Str::snake($key), $keys);

        foreach ($payload as $key => $value) {
            if (in_array(Str::snake((string) $key), $normalizedKeys, true)) {
                $string = $this->toString($value);

                if ($string !== null) {
                    return $string;
                }
            }

            if (is_array($value)) {
                $nested = $this->findFirstString($value, $keys);

                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }

    private function toString(mixed $value): ?string
    {
        if (is_scalar($value)) {
            $string = trim((string) $value);

            return $string !== '' ? $string : null;
        }

        if (is_array($value)) {
            return $this->findFirstString($value, ['name', 'description', 'label', 'acronym']);
        }

        return null;
    }

    private function nameFromEmail(string $email): string
    {
        return Str::of($email)
            ->before('@')
            ->replace(['.', '_', '-'], ' ')
            ->title()
            ->toString();
    }

    private function placeholderEmail(string $ihrisId): string
    {
        $safeId = Str::of($ihrisId)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->toString();

        return "ihris-{$safeId}@no-email.local";
    }

    /**
     * @return array<int, string>
     */
    private function assignableRoles(): array
    {
        return ['super-admin', 'admin', 'staff'];
    }
}
