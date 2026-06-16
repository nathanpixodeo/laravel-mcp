<?php

use Illuminate\Support\Facades\Route;
use Nathan\LaravelMcp\Http\McpController;

$path = config('mcp.http.path', 'mcp');

if (config('mcp.http.enabled', false)) {
    Route::match(['get', 'post', 'options', 'delete'], $path, McpController::class);
}
