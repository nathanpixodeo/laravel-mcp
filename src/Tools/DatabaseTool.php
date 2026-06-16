<?php

namespace Nathan\LaravelMcp\Tools;

use Illuminate\Support\Facades\DB;
use Nathan\LaravelMcp\Security\SecurityManager;

class DatabaseTool
{
    public function __construct(
        private readonly SecurityManager $security,
    ) {}

    public function query(string $sql, array $bindings = []): string
    {
        try {
            $error = $this->security->validateDatabaseQuery($sql);
            if ($error) {
                $this->security->log('db_query', ['sql' => $sql, 'bindings' => $bindings], 'denied', $error);
                return "ERROR: {$error}";
            }

            $maxRows = config('mcp.tools.database.max_rows', 200);
            $results = DB::select($sql, $bindings);

            if (!is_array($results)) {
                $results = [];
            }

            $results = array_slice($results, 0, $maxRows);

            $this->security->log('db_query', ['sql' => $sql, 'bindings' => $bindings, 'rows' => count($results)]);

            if (empty($results)) {
                return "Query executed successfully. 0 rows returned.";
            }

            $formatted = $this->formatTable($results);

            if (count($results) >= $maxRows) {
                $formatted .= sprintf("\n(limited to %d rows)", $maxRows);
            }

            return $formatted;
        } catch (\Throwable $e) {
            $this->security->log('db_query', ['sql' => $sql, 'bindings' => $bindings], 'error', $e->getMessage());
            return "ERROR: " . $e->getMessage();
        }
    }

    public function schema(): string
    {
        try {
            $driver = DB::connection()->getDriverName();
            $tables = $this->getTables($driver);

            if (empty($tables)) {
                return "No tables found.";
            }

            $this->security->log('db_schema', ['tables' => count($tables)]);

            $output = [];
            foreach ($tables as $table) {
                $output[] = "Table: {$table}";
                $columns = $this->getColumns($driver, $table);
                foreach ($columns as $col) {
                    $output[] = "  {$col}";
                }
                $output[] = '';
            }

            return implode("\n", $output);
        } catch (\Throwable $e) {
            $this->security->log('db_schema', [], 'error', $e->getMessage());
            return "ERROR: " . $e->getMessage();
        }
    }

    private function getTables(string $driver): array
    {
        return match ($driver) {
            'sqlite' => DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"),
            'pgsql' => DB::select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname='public' ORDER BY tablename"),
            'sqlsrv' => DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME"),
            default => DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME"),
        };
    }

    private function getColumns(string $driver, string $table): array
    {
        $columns = match ($driver) {
            'sqlite' => DB::select("PRAGMA table_info('{$table}')"),
            default => DB::select("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$table}' ORDER BY ORDINAL_POSITION"),
        };

        return array_map(function ($col) {
            $col = (array) $col;
            return implode(' | ', array_map(fn($v) => (string) ($v ?? 'NULL'), $col));
        }, $columns);
    }

    private function formatTable(array $results): string
    {
        $rows = array_map(fn($row) => (array) $row, $results);
        $headers = array_keys($rows[0]);

        $widths = [];
        foreach ($headers as $header) {
            $widths[$header] = min(mb_strlen($header), 30);
        }
        foreach ($rows as $row) {
            foreach ($headers as $header) {
                $val = mb_substr((string) ($row[$header] ?? 'NULL'), 0, 30);
                $widths[$header] = max($widths[$header], mb_strlen($val));
            }
        }

        $pad = fn($val, int $w) => str_pad(mb_substr((string) ($val ?? 'NULL'), 0, 30), $w);

        $headerLine = implode(' | ', array_map(fn($h) => $pad($h, $widths[$h]), $headers));
        $sep = str_repeat('-', min(strlen($headerLine), 120));
        $lines = [$headerLine, $sep];

        foreach ($rows as $row) {
            $lines[] = implode(' | ', array_map(fn($h) => $pad($row[$h] ?? 'NULL', $widths[$h]), $headers));
        }

        $lines[] = sprintf("\nTotal rows: %d", count($results));

        return implode("\n", $lines);
    }
}
