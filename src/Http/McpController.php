<?php

namespace Nathan\LaravelMcp\Http;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mcp\Server;
use Mcp\Server\Transport\StreamableHttpTransport;
use Nathan\LaravelMcp\Tools\{
    ArtisanTool,
    ConfigTool,
    DatabaseTool,
    EnvTool,
    FileSystemTool,
    LogViewerTool,
    RouteListTool,
};

class McpController extends Controller
{
    public function __invoke(Request $request)
    {
        if (!interface_exists(\Psr\Http\Message\ServerRequestInterface::class)) {
            return response()->json([
                'error' => 'Missing PSR-7 implementation. Run: composer require nyholm/psr7 nyholm/psr7-server',
            ], 500);
        }

        $authError = $this->checkAuth($request);
        if ($authError) {
            return response()->json(['error' => $authError], 401);
        }

        $psrRequest = $this->toPsr7Request($request);
        $transport = new StreamableHttpTransport($psrRequest);

        $builder = Server::builder()
            ->setServerInfo(
                config('mcp.server.name', 'Laravel MCP'),
                config('mcp.server.version', '1.0.0'),
            )
            ->setInstructions(config('mcp.server.instructions'))
            ->setContainer(app());

        $this->registerTools($builder);
        $mcpServer = $builder->build();

        $psrResponse = $mcpServer->run($transport);

        return response(
            $psrResponse->getBody()->getContents(),
            $psrResponse->getStatusCode(),
            $this->psrHeadersToArray($psrResponse)
        );
    }

    private function checkAuth(Request $request): ?string
    {
        if (!config('mcp.auth.enabled', false)) {
            return null;
        }

        $token = config('mcp.auth.token');
        if (empty($token)) {
            return 'MCP_AUTH_TOKEN is not configured.';
        }

        $provided = $request->bearerToken();
        if (!$provided || $provided !== $token) {
            return 'Invalid or missing Bearer token.';
        }

        return null;
    }

    private function toPsr7Request(Request $request): \Psr\Http\Message\ServerRequestInterface
    {
        if (class_exists(\Nyholm\Psr7Server\ServerRequestCreator::class)) {
            $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
            $creator = new \Nyholm\Psr7Server\ServerRequestCreator(
                $psr17Factory,
                $psr17Factory,
                $psr17Factory,
                $psr17Factory,
            );

            return $creator->fromGlobals();
        }

        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $psrRequest = $psr17Factory->createRequest($request->method(), $request->fullUrl());

        foreach ($request->headers->all() as $name => $values) {
            $psrRequest = $psrRequest->withHeader($name, $values);
        }

        $body = $request->getContent();
        if ($body) {
            $psrRequest = $psrRequest->withBody($psr17Factory->createStream($body));
        }

        return $psrRequest;
    }

    private function psrHeadersToArray(\Psr\Http\Message\ResponseInterface $response): array
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }
        return $headers;
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
