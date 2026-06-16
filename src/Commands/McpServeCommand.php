<?php

namespace Nathan\LaravelMcp\Commands;

use Illuminate\Console\Command;
use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;
use Nathan\LaravelMcp\Tools\{
    ArtisanTool,
    ConfigTool,
    DatabaseTool,
    EnvTool,
    FileSystemTool,
    LogViewerTool,
    RouteListTool,
};

class McpServeCommand extends Command
{
    protected $signature = 'mcp:serve';

    protected $description = 'Start MCP server for AI agent interaction via stdio.

For HTTP, set MCP_HTTP_ENABLED=true in .env and access the route (default: /mcp).
Requires: composer require nyholm/psr7 nyholm/psr7-server';

    public function handle(): int
    {
        $builder = Server::builder()
            ->setServerInfo(
                config('mcp.server.name', 'Laravel MCP'),
                config('mcp.server.version', '1.0.0'),
            )
            ->setInstructions(config('mcp.server.instructions'))
            ->setContainer(app());

        $this->registerTools($builder);

        $mcpServer = $builder->build();

        $this->components->info('MCP server running on stdio transport.');
        $this->components->twoColumnDetail('Server', config('mcp.server.name'));
        $this->components->twoColumnDetail('Version', config('mcp.server.version'));
        $this->components->twoColumnDetail('DB Mode', config('mcp.tools.database.readonly', true) ? 'read-only' : 'read-write');
        $this->components->twoColumnDetail('FS Mode', config('mcp.tools.filesystem.readonly', false) ? 'read-only' : 'read-write');

        $transport = new StdioTransport();
        $result = $mcpServer->run($transport);

        return self::SUCCESS;
    }

    private function registerTools($builder): void
    {
        $tools = [
            'artisan' => [ArtisanTool::class, 'run'],
            'db_query' => [DatabaseTool::class, 'query'],
            'db_schema' => [DatabaseTool::class, 'schema'],
            'file_read' => [FileSystemTool::class, 'read'],
            'file_write' => [FileSystemTool::class, 'write'],
            'file_list' => [FileSystemTool::class, 'list'],
            'file_search' => [FileSystemTool::class, 'search'],
            'logs' => [LogViewerTool::class, 'logs'],
            'routes' => [RouteListTool::class, 'routes'],
            'config_get' => [ConfigTool::class, 'get'],
            'env_info' => [EnvTool::class, 'info'],
        ];

        foreach ($tools as $name => $handler) {
            $group = match (true) {
                str_starts_with($name, 'db_') => 'database',
                str_starts_with($name, 'file_') => 'filesystem',
                $name === 'artisan' => 'artisan',
                $name === 'logs' => 'logs',
                $name === 'routes' => 'routes',
                $name === 'config_get' => 'config',
                $name === 'env_info' => 'env',
                default => null,
            };

            if ($group === null || config("mcp.tools.{$group}.enabled", true)) {
                $builder->addTool($handler, $name);
            }
        }
    }
}
