<?php

namespace Nathan\LaravelMcp\Security;

use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public function log(string $tool, array $params, string $status = 'success', ?string $error = null): void
    {
        if (!config('mcp.logging.audit_enabled', true)) {
            return;
        }

        $channel = config('mcp.logging.channel', 'stack');

        Log::channel($channel)->info('MCP Tool Call', [
            'tool' => $tool,
            'params' => $this->sanitizeParams($tool, $params),
            'status' => $status,
            'error' => $error,
            'ip' => app()->runningInConsole() ? 'cli' : (request()->ip() ?? 'cli'),
            'time' => now()->toIso8601String(),
        ]);
    }

    private function sanitizeParams(string $tool, array $params): array
    {
        $sensitive = ['password', 'secret', 'token', 'key', 'auth'];

        return array_map(function ($key, $value) use ($sensitive) {
            foreach ($sensitive as $s) {
                if (str_contains(strtolower($key), $s)) {
                    return '***REDACTED***';
                }
            }
            if (is_string($value) && strlen($value) > 1000) {
                return substr($value, 0, 1000) . '... (truncated)';
            }
            return $value;
        }, array_keys($params), $params);
    }
}
