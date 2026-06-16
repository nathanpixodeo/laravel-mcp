<?php

return [
    'server' => [
        'name' => env('MCP_SERVER_NAME', 'Laravel MCP'),
        'version' => env('MCP_SERVER_VERSION', '1.0.0'),
        'instructions' => env('MCP_SERVER_INSTRUCTIONS', 'Laravel MCP Server – debug and manage your Laravel application via AI agents.'),
    ],

    'http' => [
        'enabled' => env('MCP_HTTP_ENABLED', false),
        'path' => env('MCP_HTTP_PATH', 'mcp'),
    ],

    'transport' => [
        'default' => env('MCP_TRANSPORT', 'stdio'),
    ],

    'auth' => [
        'token' => env('MCP_AUTH_TOKEN'),
        'enabled' => env('MCP_AUTH_ENABLED', false),
    ],

    'tools' => [
        'artisan' => [
            'enabled' => true,
            'allowed_commands' => [
                '*',
            ],
            'blocked_commands' => [
                'db:wipe',
                'migrate:fresh',
                'migrate:reset',
                'key:generate',
                'env:encrypt',
            ],
        ],
        'database' => [
            'enabled' => true,
            'readonly' => env('MCP_DB_READONLY', true),
            'max_rows' => env('MCP_DB_MAX_ROWS', 200),
        ],
        'filesystem' => [
            'enabled' => true,
            'allowed_paths' => [base_path()],
            'readonly' => env('MCP_FS_READONLY', false),
        ],
        'logs' => [
            'enabled' => true,
        ],
        'routes' => [
            'enabled' => true,
        ],
        'config' => [
            'enabled' => true,
            'allowed_keys' => ['app.*', 'database.*', 'cache.*', 'session.*', 'mail.*', 'services.*', 'mcp.*'],
        ],
        'env' => [
            'enabled' => true,
        ],
    ],

    'logging' => [
        'audit_enabled' => env('MCP_AUDIT_ENABLED', true),
        'channel' => env('MCP_LOG_CHANNEL', 'stack'),
    ],
];
