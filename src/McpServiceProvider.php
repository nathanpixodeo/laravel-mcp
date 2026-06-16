<?php

namespace Nathan\LaravelMcp;

use Illuminate\Support\ServiceProvider;
use Nathan\LaravelMcp\Commands\McpServeCommand;
use Nathan\LaravelMcp\Security\AuditLogger;
use Nathan\LaravelMcp\Security\SecurityManager;

class McpServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/mcp.php' => config_path('mcp.php'),
            ], 'mcp-config');

            $this->commands([
                McpServeCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/mcp.php',
            'mcp'
        );

        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(SecurityManager::class);
    }
}
