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
        $result = [];

        foreach ($params as $key => $value) {
            foreach ($sensitive as $s) {
                if (str_contains(strtolower($key), $s)) {
                    $value = '***REDACTED***';
                    break;
                }
            }
            if (is_string($value) && strlen($value) > 1000) {
                $value = substr($value, 0, 1000) . '... (truncated)';
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
