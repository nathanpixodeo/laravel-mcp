<?php

namespace Nathan\LaravelMcp\Tests;

use Illuminate\Support\Facades\Config;
use Nathan\LaravelMcp\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('mcp', require __DIR__ . '/../config/mcp.php');
        Config::set('mcp.logging.audit_enabled', false);
    }

    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
