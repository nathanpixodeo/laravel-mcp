<?php

namespace Nathan\LaravelMcp\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Nathan\LaravelMcp\Security\AuditLogger;
use Nathan\LaravelMcp\Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    private AuditLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mcp.logging.audit_enabled', true);
        $this->logger = new AuditLogger();
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function test_log_writes_to_laravel_log(): void
    {
        Log::shouldReceive('channel')
            ->with('stack')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('MCP Tool Call', \Mockery::on(function ($context) {
                return $context['tool'] === 'test_tool'
                    && $context['status'] === 'success'
                    && isset($context['params'])
                    && isset($context['time']);
            }))
            ->once();

        $this->logger->log('test_tool', ['key' => 'value'], 'success');
    }

    public function test_log_does_not_write_when_audit_disabled(): void
    {
        Config::set('mcp.logging.audit_enabled', false);

        Log::shouldReceive('channel')->never();

        $this->logger->log('test_tool', ['key' => 'value'], 'success');
    }

    public function test_log_redacts_sensitive_params(): void
    {
        Log::shouldReceive('channel')
            ->with('stack')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('MCP Tool Call', \Mockery::on(function ($context) {
                return $context['params']['password'] === '***REDACTED***'
                    && $context['params']['secret'] === '***REDACTED***'
                    && $context['params']['name'] === 'public-value';
            }))
            ->once();

        $this->logger->log('test_tool', [
            'password' => 'super-secret',
            'secret' => 'my-secret-key',
            'name' => 'public-value',
        ]);
    }

    public function test_log_truncates_long_values(): void
    {
        $longValue = str_repeat('a', 2000);

        Log::shouldReceive('channel')
            ->with('stack')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('MCP Tool Call', \Mockery::on(function ($context) use ($longValue) {
                return strlen($context['params']['content']) < strlen($longValue)
                    && str_contains($context['params']['content'], '(truncated)');
            }))
            ->once();

        $this->logger->log('test_tool', ['content' => $longValue]);
    }

    public function test_log_records_error_status(): void
    {
        Log::shouldReceive('channel')
            ->with('stack')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('MCP Tool Call', \Mockery::on(function ($context) {
                return $context['tool'] === 'failing_tool'
                    && $context['status'] === 'error'
                    && $context['error'] === 'Something broke';
            }))
            ->once();

        $this->logger->log('failing_tool', [], 'error', 'Something broke');
    }
}
