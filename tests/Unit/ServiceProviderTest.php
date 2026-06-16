<?php

namespace Nathan\LaravelMcp\Tests\Unit;

use Nathan\LaravelMcp\Tests\TestCase;
use Nathan\LaravelMcp\McpServiceProvider;

class ServiceProviderTest extends TestCase
{
    public function test_provider_is_registered(): void
    {
        $providers = $this->app->getLoadedProviders();
        $this->assertArrayHasKey(McpServiceProvider::class, $providers);
        $this->assertTrue($providers[McpServiceProvider::class]);
    }

    public function test_mcp_config_is_merged(): void
    {
        $this->assertNotNull(config('mcp'));
        $this->assertIsArray(config('mcp'));
    }

    public function test_mcp_config_has_server_info(): void
    {
        $this->assertArrayHasKey('server', config('mcp'));
        $this->assertArrayHasKey('name', config('mcp.server'));
        $this->assertArrayHasKey('version', config('mcp.server'));
    }

    public function test_mcp_config_has_tools_config(): void
    {
        $this->assertArrayHasKey('tools', config('mcp'));
        $this->assertArrayHasKey('artisan', config('mcp.tools'));
        $this->assertArrayHasKey('database', config('mcp.tools'));
        $this->assertArrayHasKey('filesystem', config('mcp.tools'));
    }

    public function test_mcp_config_has_security_defaults(): void
    {
        $this->assertTrue(config('mcp.tools.database.readonly'));
        $this->assertEquals(200, config('mcp.tools.database.max_rows'));
        $this->assertFalse(config('mcp.auth.enabled'));
    }

    public function test_mcp_config_has_blocked_commands(): void
    {
        $blocked = config('mcp.tools.artisan.blocked_commands');
        $this->assertIsArray($blocked);
        $this->assertContains('db:wipe', $blocked);
        $this->assertContains('migrate:fresh', $blocked);
    }

    public function test_commands_are_registered(): void
    {
        $commands = $this->app->make('Illuminate\Contracts\Console\Kernel')->all();
        $this->assertArrayHasKey('mcp:serve', $commands);
    }
}
