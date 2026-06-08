<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PostgisAppDataMigrator
{
    /**
     * Tables copied from the current MySQL app database into PostgreSQL.
     * Migrations are intentionally excluded because PostgreSQL owns its own
     * migration history after running the app migrations there.
     */
    private const APP_TABLES = [
        'users',
        'password_reset_tokens',
        'barangays',
        'map_layer_types',
        'map_features',
        'map_uploads',
        'passkeys',
        'personal_access_tokens',
        'permissions',
        'roles',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'boundary_versions',
        'activity_log',
    ];

    private const EPHEMERAL_TABLES = [
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function copy(bool $truncate = false, bool $includeEphemeral = false): array
    {
        $source = DB::connection('mysql');
        $target = DB::connection('postgis');
        $tables = $this->tables($includeEphemeral);

        $this->assertTablesExist($source, $target, $tables);

        if ($truncate) {
            $this->truncateTarget($target, $tables);
        }

        $summary = [];

        foreach ($tables as $table) {
            $columnTypes = $this->columnTypes($target, $table);
            $rows = $source->table($table)->get();
            $inserted = 0;

            foreach ($rows->chunk(250) as $chunk) {
                $records = $chunk
                    ->map(fn (object $row) => $this->normalizeRow((array) $row, $columnTypes))
                    ->all();

                if ($records !== []) {
                    $target->table($table)->insert($records);
                    $inserted += count($records);
                }
            }

            $this->resetIdentitySequence($target, $table);

            $summary[$table] = [
                'source' => $rows->count(),
                'target' => $target->table($table)->count(),
                'inserted' => $inserted,
                'match' => $rows->count() === $target->table($table)->count(),
            ];
        }

        return $summary;
    }

    public function compare(bool $includeEphemeral = false): array
    {
        $source = DB::connection('mysql');
        $target = DB::connection('postgis');
        $tables = $this->tables($includeEphemeral);

        $this->assertTablesExist($source, $target, $tables);

        $summary = [];

        foreach ($tables as $table) {
            $sourceCount = $source->table($table)->count();
            $targetCount = $target->table($table)->count();

            $summary[$table] = [
                'source' => $sourceCount,
                'target' => $targetCount,
                'inserted' => null,
                'match' => $sourceCount === $targetCount,
            ];
        }

        return $summary;
    }

    public function mismatches(array $summary): array
    {
        return collect($summary)
            ->filter(fn (array $row) => ! $row['match'])
            ->keys()
            ->values()
            ->all();
    }

    private function tables(bool $includeEphemeral): array
    {
        return $includeEphemeral
            ? [...self::APP_TABLES, ...self::EPHEMERAL_TABLES]
            : self::APP_TABLES;
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function assertTablesExist(ConnectionInterface $source, ConnectionInterface $target, array $tables): void
    {
        foreach ($tables as $table) {
            if (! $source->getSchemaBuilder()->hasTable($table)) {
                throw new RuntimeException("Missing source MySQL table: {$table}");
            }

            if (! $target->getSchemaBuilder()->hasTable($table)) {
                throw new RuntimeException("Missing target PostgreSQL table: {$table}. Run DB_CONNECTION=postgis ACTIVITY_LOGGER_DB_CONNECTION=postgis php artisan migrate --database=postgis first.");
            }
        }
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function truncateTarget(ConnectionInterface $target, array $tables): void
    {
        if ($tables === []) {
            return;
        }

        $quotedTables = collect($tables)
            ->map(fn (string $table) => $this->quoteIdentifier($table))
            ->implode(', ');

        $target->statement("TRUNCATE TABLE {$quotedTables} RESTART IDENTITY CASCADE");
    }

    /**
     * @return array<string, string>
     */
    private function columnTypes(ConnectionInterface $target, string $table): array
    {
        $columns = $target->select(<<<'SQL'
SELECT column_name, data_type, udt_name
FROM information_schema.columns
WHERE table_schema = 'public'
    AND table_name = ?
SQL, [$table]);

        $types = [];

        foreach ($columns as $column) {
            $types[$column->column_name] = $column->data_type === 'USER-DEFINED'
                ? $column->udt_name
                : $column->data_type;
        }

        return $types;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $columnTypes
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row, array $columnTypes): array
    {
        foreach ($row as $column => $value) {
            $type = $columnTypes[$column] ?? null;

            if ($type === 'boolean' && $value !== null) {
                $row[$column] = (bool) $value;
                continue;
            }

            if (($type === 'json' || $type === 'jsonb') && $value !== null) {
                $row[$column] = $this->normalizeJson($value);
            }
        }

        return $row;
    }

    private function normalizeJson(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        if (! is_string($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        if (trim($value) === '') {
            return 'null';
        }

        json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return $value;
    }

    private function resetIdentitySequence(ConnectionInterface $target, string $table): void
    {
        if (! $target->getSchemaBuilder()->hasColumn($table, 'id')) {
            return;
        }

        $sequence = $target->selectOne("SELECT pg_get_serial_sequence(?, 'id') AS sequence_name", ["public.{$table}"]);

        if (! filled($sequence?->sequence_name)) {
            return;
        }

        $maxId = (int) ($target->table($table)->max('id') ?? 0);
        $target->statement('SELECT setval(?, ?, ?)', [
            $sequence->sequence_name,
            max($maxId, 1),
            $maxId > 0,
        ]);
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (! Str::of($identifier)->isMatch('/^[A-Za-z_][A-Za-z0-9_]*$/')) {
            throw new RuntimeException("Unsafe SQL identifier: {$identifier}");
        }

        return '"'.$identifier.'"';
    }
}
