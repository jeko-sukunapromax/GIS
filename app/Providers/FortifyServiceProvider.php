<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\IhrisAuthenticator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use Spatie\Permission\Models\Role;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        Fortify::authenticateUsing(function (Request $request) {
            $ihrisUser = app(IhrisAuthenticator::class)->attempt(
                (string) $request->input(Fortify::username()),
                (string) $request->input('password')
            );

            if (! $ihrisUser) {
                return null;
            }

            $user = User::query()
                ->when($ihrisUser['ihris_id'], fn ($query) => $query->where('ihris_id', $ihrisUser['ihris_id']))
                ->orWhere('email', Str::lower($ihrisUser['email']))
                ->first() ?? new User;

            if ($user->exists && $user->deactivated_at) {
                return null;
            }

            $user->fill([
                'ihris_id' => $ihrisUser['ihris_id'],
                'name' => $ihrisUser['name'],
                'email' => Str::lower($ihrisUser['email']),
                'office' => $ihrisUser['office'],
                'ihris_payload' => $ihrisUser['payload'],
                'last_ihris_login_at' => now(),
                'password' => Hash::make(Str::random(48)),
                'email_verified_at' => now(),
            ])->save();

            collect(['super-admin', 'admin', 'staff'])
                ->each(fn (string $role) => Role::findOrCreate($role, 'web'));

            $assignedRole = $this->roleForIhrisUser($user, $ihrisUser['email']);
            $user->syncRoles([$assignedRole]);

            if ($ihrisUser['token']) {
                $request->session()->put('ihris_access_token', $ihrisUser['token']);
            }

            app(ActivityLogger::class)->log('user.logged_in', "{$user->name} logged in through iHRIS.", $user, [
                'email' => $user->email,
                'office' => $user->office,
                'role' => $assignedRole,
            ], $request);

            return $user;
        });

        Fortify::confirmPasswordsUsing(function (User $user, ?string $password = null) {
            if (! $password) {
                return false;
            }

            return (bool) app(IhrisAuthenticator::class)->attempt($user->email, $password);
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });
    }

    private function roleForIhrisUser(User $user, string $email): string
    {
        $normalizedEmail = Str::lower(trim($email));

        $superAdminEmails = collect(config('services.ihris.super_admin_emails', []))
            ->map(fn (string $email) => Str::lower(trim($email)))
            ->filter();

        if ($superAdminEmails->contains($normalizedEmail)) {
            return 'super-admin';
        }

        return $user->roles
            ->pluck('name')
            ->first(fn (string $role) => in_array($role, ['admin', 'staff'], true)) ?? 'admin';
    }
}
