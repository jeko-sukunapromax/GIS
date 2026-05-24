<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IhrisAuthenticator
{
    /**
     * Attempt to authenticate against the configured iHRIS API.
     *
     * @return array<string, mixed>|null
     */
    public function attempt(string $email, string $password): ?array
    {
        if ($testUser = $this->attemptTestLogin($email, $password)) {
            return $testUser;
        }

        $response = Http::acceptJson()
            ->asJson()
            ->timeout((int) config('services.ihris.timeout', 10))
            ->post($this->loginUrl(), [
                config('services.ihris.username_field', 'email') => $email,
                'password' => $password,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (! is_array($payload) || $this->explicitlyFailed($payload)) {
            return null;
        }

        $userPayload = $this->userPayload($payload);
        $office = $this->findFirstString($userPayload, [
            'office',
            'office_name',
            'department',
            'department_name',
            'division',
            'division_name',
        ]);
        $payloadEmail = $this->findFirstString($userPayload, ['email', 'email_address', 'username']) ?? $email;

        if (! $this->isAllowedOffice($office) && ! $this->isAdminOverrideEmail($email, $payloadEmail)) {
            return null;
        }

        return [
            'email' => $payloadEmail,
            'name' => $this->findFirstString($userPayload, ['name', 'full_name', 'fullname', 'employee_name']) ?? $email,
            'office' => $office ?? 'iHRIS Admin Override',
            'ihris_id' => $this->findFirstString($userPayload, ['ihris_id', 'employee_id', 'personnel_id', 'user_id', 'id']),
            'token' => $this->findFirstString($payload, ['token', 'access_token', 'bearer_token', 'api_token']),
            'payload' => $payload,
        ];
    }

    public function isAllowedOffice(?string $office): bool
    {
        if (! $office) {
            return false;
        }

        return collect(config('services.ihris.allowed_offices', [config('services.ihris.allowed_office', 'BDRRMC')]))
            ->filter()
            ->contains(fn (string $allowedOffice) => Str::contains(Str::upper($office), Str::upper($allowedOffice)));
    }

    private function isAdminOverrideEmail(string ...$emails): bool
    {
        $overrideEmails = collect(config('services.ihris.admin_override_emails', []))
            ->map(fn (string $email) => Str::lower(trim($email)))
            ->filter();

        return collect($emails)
            ->map(fn (string $email) => Str::lower(trim($email)))
            ->filter()
            ->contains(fn (string $email) => $overrideEmails->contains($email));
    }

    /**
     * Fetch all employees under the configured BDRRMC/MDRRMO office.
     *
     * @return array<int, array<string, mixed>>
     */
    public function officeEmployees(string $token): array
    {
        $response = Http::acceptJson()
            ->withToken($token)
            ->timeout((int) config('services.ihris.timeout', 10))
            ->get($this->officeEmployeesUrl());

        if (! $response->successful()) {
            $response->throw();
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return [];
        }

        $employees = $this->employeeListPayload($payload);

        return array_values(array_filter($employees, fn ($employee) => is_array($employee)));
    }

    private function loginUrl(): string
    {
        return rtrim(config('services.ihris.base_url'), '/').'/'.ltrim(config('services.ihris.login_endpoint', 'login'), '/');
    }

    private function officeEmployeesUrl(): string
    {
        return rtrim(config('services.ihris.base_url'), '/')
            .'/all-employees/office/'
            .config('services.ihris.office_uuid');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function attemptTestLogin(string $email, string $password): ?array
    {
        if (! app()->environment(['local', 'testing']) || ! config('services.ihris.test_login.enabled')) {
            return null;
        }

        if (! hash_equals((string) config('services.ihris.test_login.email'), $email)) {
            return null;
        }

        if (! hash_equals((string) config('services.ihris.test_login.password'), $password)) {
            return null;
        }

        $office = config('services.ihris.allowed_office', 'BDRRMC').' Office';

        return [
            'email' => $email,
            'name' => config('services.ihris.test_login.name', 'BDRRMC Test Admin'),
            'office' => $office,
            'ihris_id' => 'local-test-bdrrmc',
            'token' => null,
            'payload' => [
                'source' => 'local_test_login',
                'office' => $office,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function explicitlyFailed(array $payload): bool
    {
        foreach (['success', 'status', 'ok'] as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] === false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function userPayload(array $payload): array
    {
        foreach (['user', 'employee', 'account', 'data'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $this->userPayload($payload[$key]);
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  payload
     * @return array<int, mixed>
     */
    private function employeeListPayload(array $payload): array
    {
        foreach (['employees', 'users', 'data', 'results'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                if (array_is_list($payload[$key])) {
                    return $payload[$key];
                }

                return $this->employeeListPayload($payload[$key]);
            }
        }

        return array_is_list($payload) ? $payload : [];
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
            return $this->findFirstString($value, ['name', 'description', 'label']);
        }

        return null;
    }
}
