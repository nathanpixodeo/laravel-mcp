<?php

namespace Nathan\LaravelMcp\Tools;

use Nathan\LaravelMcp\Security\SecurityManager;

class EnvTool
{
    public function __construct(
        private readonly SecurityManager $security,
    ) {}

    public function info(): string
    {
        try {
            $info = [
                'app' => [
                    'name' => config('app.name'),
                    'env' => app()->environment(),
                    'debug' => config('app.debug'),
                    'url' => config('app.url'),
                ],
                'laravel' => [
                    'version' => app()->version(),
                ],
                'php' => [
                    'version' => PHP_VERSION,
                    'os' => PHP_OS,
                    'sapi' => PHP_SAPI,
                ],
                'database' => [
                    'connection' => config('database.default'),
                    'driver' => config('database.connections.' . config('database.default') . '.driver'),
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                ],
                'session' => [
                    'driver' => config('session.driver'),
                ],
                'queue' => [
                    'connection' => config('queue.default'),
                ],
                'mcp' => [
                    'transport' => config('mcp.transport.default'),
                    'auth_enabled' => config('mcp.auth.enabled', false),
                    'db_readonly' => config('mcp.tools.database.readonly', true),
                    'fs_readonly' => config('mcp.tools.filesystem.readonly', false),
                ],
                'time' => [
                    'timezone' => config('app.timezone'),
                    'now' => now()->toIso8601String(),
                ],
            ];

            $this->security->log('env_info', []);

            return json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            $this->security->log('env_info', [], 'error', $e->getMessage());
            return "ERROR: " . $e->getMessage();
        }
    }
}
