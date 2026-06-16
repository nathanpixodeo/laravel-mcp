<?php

namespace Nathan\LaravelMcp\Security;

class SecurityManager
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function checkAuth(): ?string
    {
        if (!config('mcp.auth.enabled', false)) {
            return null;
        }

        $token = config('mcp.auth.token');

        if (empty($token)) {
            return 'MCP_AUTH_TOKEN is not configured but authentication is enabled.';
        }

        if (app()->runningInConsole()) {
            return null;
        }

        if (request()->bearerToken() !== $token) {
            return 'Invalid or missing authentication token.';
        }

        return null;
    }

    public function checkToolEnabled(string $key): ?string
    {
        $enabled = config("mcp.tools.{$key}.enabled", true);

        if (!$enabled) {
            return "Tool '{$key}' is disabled by configuration.";
        }

        return null;
    }

    public function checkArtisanCommand(string $command): ?string
    {
        $allowed = config('mcp.tools.artisan.allowed_commands', ['*']);

        if (!in_array('*', $allowed, true) && !in_array($command, $allowed, true)) {
            return "Artisan command '{$command}' is not in the allowed list.";
        }

        $blocked = config('mcp.tools.artisan.blocked_commands', []);

        foreach ($blocked as $pattern) {
            if (fnmatch($pattern, $command)) {
                return "Artisan command '{$command}' is blocked by configuration.";
            }
        }

        return null;
    }

    public function checkDatabaseReadonly(): ?string
    {
        if (config('mcp.tools.database.readonly', true)) {
            return 'Database is in read-only mode. Only SELECT queries are allowed.';
        }

        return null;
    }

    public function validateDatabaseQuery(string $sql): ?string
    {
        if (!config('mcp.tools.database.readonly', true)) {
            return null;
        }

        $sql = trim($sql);
        $forbidden = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE', 'TRUNCATE', 'REPLACE', 'RENAME'];

        foreach ($forbidden as $keyword) {
            if (preg_match('/^\s*' . $keyword . '\b/i', $sql)) {
                return "Query rejected: '{$keyword}' statements are not allowed in read-only mode.";
            }
        }

        return null;
    }

    public function validateFilePath(string $path): ?string
    {
        $realPath = realpath($path);

        if ($realPath === false) {
            if (str_contains($path, '..')) {
                return 'Path traversal detected: ".." is not allowed.';
            }
            $realPath = $path;
        }

        $allowedPaths = config('mcp.tools.filesystem.allowed_paths', [base_path()]);

        $allowed = false;
        foreach ($allowedPaths as $allowedPath) {
            $normalizedAllowed = rtrim(realpath($allowedPath) ?: $allowedPath, '/') . '/';
            $normalizedReal = rtrim($realPath, '/') . '/';

            if (str_starts_with($normalizedReal, $normalizedAllowed)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            return "Path '{$path}' is outside the allowed directories.";
        }

        return null;
    }

    public function checkFilesystemReadonly(): ?string
    {
        if (config('mcp.tools.filesystem.readonly', false)) {
            return 'Filesystem is in read-only mode. Write operations are disabled.';
        }

        return null;
    }

    public function validateConfigKey(string $key): ?string
    {
        $allowed = config('mcp.tools.config.allowed_keys', ['*']);

        if (in_array('*', $allowed, true)) {
            return null;
        }

        foreach ($allowed as $pattern) {
            if (fnmatch($pattern, $key)) {
                return null;
            }
        }

        return "Config key '{$key}' is not in the allowed list.";
    }

    public function log(string $tool, array $params, string $status = 'success', ?string $error = null): void
    {
        $this->auditLogger->log($tool, $params, $status, $error);
    }
}
