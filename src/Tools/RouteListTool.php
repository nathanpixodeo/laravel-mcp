<?php

namespace Nathan\LaravelMcp\Tools;

use Illuminate\Support\Facades\Route;
use Nathan\LaravelMcp\Security\SecurityManager;

class RouteListTool
{
    public function __construct(
        private readonly SecurityManager $security,
    ) {}

    public function routes(?string $method = null): string
    {
        try {
            $routes = Route::getRoutes();
            $output = [];
            $count = 0;

            $output[] = str_pad('METHOD', 8) . ' ' . str_pad('URI', 55) . ' ' . 'NAME';
            $output[] = str_repeat('-', 120);

            foreach ($routes as $route) {
                $methods = implode('|', $route->methods());
                if ($method && !in_array(strtoupper($method), $route->methods(), true)) {
                    continue;
                }
                $uri = $route->uri();
                $name = $route->getName() ?? '-';
                $action = $route->getActionName();

                $output[] = str_pad($methods, 8) . ' ' . str_pad($uri, 55) . ' ' . $name;
                $count++;
            }

            $output[] = str_repeat('-', 120);
            $output[] = "Total: {$count} routes";

            $this->security->log('routes', ['method' => $method ?? 'all', 'count' => $count]);

            return implode("\n", $output);
        } catch (\Throwable $e) {
            $this->security->log('routes', ['method' => $method ?? 'all'], 'error', $e->getMessage());
            return "ERROR: " . $e->getMessage();
        }
    }
}
