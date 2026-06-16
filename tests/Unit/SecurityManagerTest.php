<?php

namespace Nathan\LaravelMcp\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Nathan\LaravelMcp\Security\AuditLogger;
use Nathan\LaravelMcp\Security\SecurityManager;
use Nathan\LaravelMcp\Tests\TestCase;

class SecurityManagerTest extends TestCase
{
    private SecurityManager $security;

    protected function setUp(): void
    {
        parent::setUp();

        $logger = $this->createMock(AuditLogger::class);
        $this->security = new SecurityManager($logger);
    }

    public function test_auth_is_skipped_when_disabled(): void
    {
        Config::set('mcp.auth.enabled', false);
        $this->assertNull($this->security->checkAuth());
    }

    public function test_auth_requires_token_when_enabled(): void
    {
        Config::set('mcp.auth.enabled', true);
        Config::set('mcp.auth.token', '');
        $this->assertNotNull($this->security->checkAuth());
    }

    public function test_tool_enabled_returns_null_when_enabled(): void
    {
        Config::set('mcp.tools.database.enabled', true);
        $this->assertNull($this->security->checkToolEnabled('database'));
    }

    public function test_tool_enabled_returns_error_when_disabled(): void
    {
        Config::set('mcp.tools.database.enabled', false);
        $this->assertNotNull($this->security->checkToolEnabled('database'));
    }

    public function test_artisan_command_allowed_with_wildcard(): void
    {
        Config::set('mcp.tools.artisan.allowed_commands', ['*']);
        Config::set('mcp.tools.artisan.blocked_commands', []);
        $this->assertNull($this->security->checkArtisanCommand('cache:clear'));
    }

    public function test_artisan_command_blocked_when_not_in_whitelist(): void
    {
        Config::set('mcp.tools.artisan.allowed_commands', ['cache:clear', 'route:list']);
        Config::set('mcp.tools.artisan.blocked_commands', []);
        $this->assertNotNull($this->security->checkArtisanCommand('migrate'));
    }

    public function test_artisan_command_blocked_by_blocklist(): void
    {
        Config::set('mcp.tools.artisan.allowed_commands', ['*']);
        Config::set('mcp.tools.artisan.blocked_commands', ['db:wipe', 'migrate:fresh']);
        $this->assertNotNull($this->security->checkArtisanCommand('db:wipe'));
    }

    public function test_artisan_command_allowed_when_not_blocked(): void
    {
        Config::set('mcp.tools.artisan.allowed_commands', ['*']);
        Config::set('mcp.tools.artisan.blocked_commands', ['db:wipe']);
        $this->assertNull($this->security->checkArtisanCommand('cache:clear'));
    }

    public function test_database_readonly_returns_error_when_enabled(): void
    {
        Config::set('mcp.tools.database.readonly', true);
        $this->assertNotNull($this->security->checkDatabaseReadonly());
    }

    public function test_database_readonly_returns_null_when_disabled(): void
    {
        Config::set('mcp.tools.database.readonly', false);
        $this->assertNull($this->security->checkDatabaseReadonly());
    }

    public function test_query_blocks_insert_when_readonly(): void
    {
        Config::set('mcp.tools.database.readonly', true);
        $this->assertNotNull($this->security->validateDatabaseQuery('INSERT INTO users (name) VALUES ("test")'));
    }

    public function test_query_blocks_update_when_readonly(): void
    {
        Config::set('mcp.tools.database.readonly', true);
        $this->assertNotNull($this->security->validateDatabaseQuery('UPDATE users SET name = "test" WHERE id = 1'));
    }

    public function test_query_blocks_delete_when_readonly(): void
    {
        Config::set('mcp.tools.database.readonly', true);
        $this->assertNotNull($this->security->validateDatabaseQuery('DELETE FROM users WHERE id = 1'));
    }

    public function test_query_allows_select_when_readonly(): void
    {
        Config::set('mcp.tools.database.readonly', true);
        $this->assertNull($this->security->validateDatabaseQuery('SELECT * FROM users'));
    }

    public function test_query_allows_select_with_complex_syntax(): void
    {
        Config::set('mcp.tools.database.readonly', true);
        $this->assertNull($this->security->validateDatabaseQuery('SELECT u.id, u.name, p.title FROM users u JOIN posts p ON p.user_id = u.id'));
    }

    public function test_query_allows_non_select_when_readonly_disabled(): void
    {
        Config::set('mcp.tools.database.readonly', false);
        $this->assertNull($this->security->validateDatabaseQuery('INSERT INTO users (name) VALUES ("test")'));
    }

    public function test_file_path_allowed_within_base_path(): void
    {
        Config::set('mcp.tools.filesystem.allowed_paths', [base_path()]);
        $this->assertNull($this->security->validateFilePath(base_path('app/Models/User.php')));
    }

    public function test_file_path_blocked_outside_allowed(): void
    {
        Config::set('mcp.tools.filesystem.allowed_paths', [base_path()]);
        $this->assertNotNull($this->security->validateFilePath('/etc/passwd'));
    }

    public function test_file_path_traversal_detected(): void
    {
        Config::set('mcp.tools.filesystem.allowed_paths', [base_path()]);
        $result = $this->security->validateFilePath(base_path('../../etc/passwd'));
        $this->assertNotNull($result);
        $this->assertStringContainsString('traversal', strtolower($result));
    }

    public function test_filesystem_readonly_returns_error_when_enabled(): void
    {
        Config::set('mcp.tools.filesystem.readonly', true);
        $this->assertNotNull($this->security->checkFilesystemReadonly());
    }

    public function test_filesystem_readonly_returns_null_when_disabled(): void
    {
        Config::set('mcp.tools.filesystem.readonly', false);
        $this->assertNull($this->security->checkFilesystemReadonly());
    }

    public function test_config_key_allowed_with_wildcard(): void
    {
        Config::set('mcp.tools.config.allowed_keys', ['*']);
        $this->assertNull($this->security->validateConfigKey('app.name'));
    }

    public function test_config_key_allowed_when_matches(): void
    {
        Config::set('mcp.tools.config.allowed_keys', ['app.*', 'database.*']);
        $this->assertNull($this->security->validateConfigKey('app.name'));
    }

    public function test_config_key_blocked_when_not_in_list(): void
    {
        Config::set('mcp.tools.config.allowed_keys', ['app.*']);
        $this->assertNotNull($this->security->validateConfigKey('services.stripe'));
    }
}
