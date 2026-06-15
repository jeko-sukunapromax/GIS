<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\IhrisAuthenticator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SyncBdrrmcUsers extends Command
{
    protected $signature = 'ihris:sync-bdrrmc {token}';
    protected $description = 'Sync BDRRMC users from iHRIS API';

    public function handle(IhrisAuthenticator $ihris): int
    {
        $token = $this->argument('token');

        try {
            $employees = $ihris->officeEmployees($token);
        } catch (\Throwable $e) {
            $this->error('Failed to fetch employees: ' . $e->getMessage());
            return 1;
        }

        $synced = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            $email = $this->findFirstString($employee, ['email', 'email_address', 'username']);
            $ihrisId = $this->findFirstString($employee, ['uuid', 'ihris_id', 'employee_id', 'personnel_id', 'user_id', 'id']);

            if (!$email && $ihrisId) {
                $email = $this->placeholderEmail($ihrisId);
            }

            if (!$email) {
                $skipped++;
                continue;
            }

            $office = $this->findFirstString($employee, ['office', 'office_name', 'department', 'department_name', 'division', 'division_name'])
                ?? 'Municipal Disaster Risk Reduction Management Office';

            $name = $this->findFirstString($employee, ['name', 'full_name', 'fullname', 'employee_name'])
                ?? ($ihrisId ? 'iHRIS User ' . Str::limit($ihrisId, 8, '') : $this->nameFromEmail($email));

            $user = User::query()
                ->when($ihrisId, fn($query) => $query->where('ihris_id', $ihrisId))
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

            $this->info("Synced: {$name} ({$email})");
        }

        $this->info("Done! Synced: {$synced}, Skipped: {$skipped}");
        return 0;
    }

    private function findFirstString(array $payload, array $keys): ?string
    {
        $normalizedKeys = array_map(fn(string $key) => Str::snake($key), $keys);

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
        return Str::of($email)->before('@')->replace(['.', '_', '-'], ' ')->title()->toString();
    }

    private function placeholderEmail(string $ihrisId): string
    {
        $safeId = Str::of($ihrisId)->lower()->replaceMatches('/[^a-z0-9]+/', '-')->trim('-')->toString();
        return "ihris-{$safeId}@no-email.local";
    }
}
