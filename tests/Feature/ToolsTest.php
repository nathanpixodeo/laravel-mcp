<?php

namespace Nathan\LaravelMcp\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Nathan\LaravelMcp\Security\AuditLogger;
use Nathan\LaravelMcp\Security\SecurityManager;
use Nathan\LaravelMcp\Tests\TestCase;
use Nathan\LaravelMcp\Tools\ArtisanTool;
use Nathan\LaravelMcp\Tools\ConfigTool;
use Nathan\LaravelMcp\Tools\EnvTool;
use Nathan\LaravelMcp\Tools\FileSystemTool;

class ToolsTest extends TestCase
{
    private SecurityManager $security;

    protected function setUp(): void
    {
        parent::setUp();
        $logger = $this->createMock(AuditLogger::class);
        $this->security = new SecurityManager($logger);

        Config::set('mcp.tools.config.allowed_keys', ['*']);
        Config::set('mcp.tools.filesystem.allowed_paths', [base_path()]);
    }

    public function test_artisan_tool_routes_command(): void
    {
        $tool = new ArtisanTool($this->security);
        $result = $tool->run('route:list');

        $this->assertStringContainsString('Exit code: 0', $result);
    }

    public function test_artisan_tool_help_command(): void
    {
        $tool = new ArtisanTool($this->security);
        $result = $tool->run('list');

        $this->assertStringContainsString('Exit code: 0', $result);
    }

    public function test_config_tool_gets_value(): void
    {
        Config::set('app.test_value', 'hello_mcp');
        $tool = new ConfigTool($this->security);
        $result = $tool->get('app.test_value');

        $this->assertStringContainsString('hello_mcp', $result);
    }

    public function test_config_tool_returns_error_for_missing_key(): void
    {
        $tool = new ConfigTool($this->security);
        $result = $tool->get('nonexistent.key.here');

        $this->assertStringContainsString('not found', $result);
    }

    public function test_config_tool_returns_json_for_array(): void
    {
        Config::set('app.test_array', ['foo' => 'bar', 'baz' => 123]);
        $tool = new ConfigTool($this->security);
        $result = $tool->get('app.test_array');

        $this->assertStringContainsString('foo', $result);
        $this->assertStringContainsString('bar', $result);
    }

    public function test_env_tool_returns_info(): void
    {
        $tool = new EnvTool($this->security);
        $result = $tool->info();

        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('app', $decoded);
        $this->assertArrayHasKey('laravel', $decoded);
        $this->assertArrayHasKey('php', $decoded);
        $this->assertArrayHasKey('database', $decoded);
        $this->assertArrayHasKey('mcp', $decoded);
    }

    public function test_env_tool_has_correct_structure(): void
    {
        $tool = new EnvTool($this->security);
        $result = json_decode($tool->info(), true);

        $this->assertEquals('testing', $result['app']['env']);
        $this->assertArrayHasKey('version', $result['laravel']);
        $this->assertArrayHasKey('version', $result['php']);
        $this->assertArrayHasKey('driver', $result['database']);
    }

    public function test_file_list_returns_contents(): void
    {
        $tool = new FileSystemTool($this->security);
        $result = $tool->list('');

        $this->assertStringContainsString('config', $result);
    }

    public function test_file_read_returns_content(): void
    {
        $tool = new FileSystemTool($this->security);
        $result = $tool->read('config/app.php');

        $this->assertStringContainsString("'name'", $result);
        $this->assertStringContainsString("'env'", $result);
    }

    public function test_file_read_missing_returns_error(): void
    {
        $tool = new FileSystemTool($this->security);
        $result = $tool->read('nonexistent_file.txt');

        $this->assertStringContainsString('not found', strtolower($result));
    }

    public function test_file_search_finds_php_files(): void
    {
        $tool = new FileSystemTool($this->security);
        $result = $tool->search('*.php', 'config');

        $this->assertStringContainsString('.php', $result);
    }
}
