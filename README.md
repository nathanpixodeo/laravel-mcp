# Laravel MCP

MCP (Model Context Protocol) server for Laravel. Lets AI agents (Claude CLI, Claude Desktop, Cursor, etc.) connect to your Laravel projects via SSH tunnel or HTTP to debug, inspect, and manage them remotely.

## Features

- 11 built-in tools: artisan, database, filesystem, logs, routes, config, env
- **2 transports**: stdio (SSH tunnel) and HTTP (route)
- **Security**: auth token, path whitelist, artisan command blocking, read-only modes
- **Audit logging**: all tool calls are logged
- **Config-driven**: enable/disable tool groups, whitelist keys, etc.

## Installation

```bash
composer require nathanpixodeo/laravel-mcp
```

For HTTP transport, also need:

```bash
composer require nyholm/psr7 nyholm/psr7-server
```

### Publish config (optional)

```bash
php artisan vendor:publish --tag=mcp-config
```

## Usage

### Transport 1: STDIO (via SSH tunnel)

On the server:

```bash
php artisan mcp:serve
```

Configure Claude Desktop / Claude CLI (`claude_desktop_config.json`):

```json
{
  "mcpServers": {
    "laravel-mcp": {
      "command": "ssh",
      "args": ["user@server", "-t", "cd /path/to/project && php artisan mcp:serve"]
    }
  }
}
```

### Transport 2: HTTP (via route)

Set in `.env`:

```
MCP_HTTP_ENABLED=true
MCP_AUTH_ENABLED=true
MCP_AUTH_TOKEN=your-secure-token
```

Default route is `/mcp`. Change via `MCP_HTTP_PATH` env.

Agent connects to: `https://example.com/mcp` with header `Authorization: Bearer your-secure-token`.

## Available Tools

| Tool | Description | Parameters |
|------|-------------|------------|
| `artisan` | Run any Artisan command | `command` (string), `parameters` (array) |
| `db_query` | Execute SQL query (readonly by default) | `sql` (string), `bindings` (array) |
| `db_schema` | Show database schema | — |
| `file_read` | Read file content | `path` (string) |
| `file_write` | Write/edit file | `path` (string), `content` (string) |
| `file_list` | List directory contents | `path` (string, default: root) |
| `file_search` | Search files by pattern | `pattern` (string), `path` (string) |
| `logs` | View Laravel logs | `lines` (int, default: 50), `level` (string, optional) |
| `routes` | List all registered routes | `method` (string, optional) |
| `config_get` | Get config value | `key` (string, e.g. `app.name`) |
| `env_info` | Show environment info | — |

## Configuration

### .env

```
MCP_SERVER_NAME=Laravel MCP
MCP_SERVER_VERSION=1.0.0

MCP_HTTP_ENABLED=false
MCP_HTTP_PATH=mcp

MCP_AUTH_ENABLED=false
MCP_AUTH_TOKEN=

MCP_DB_READONLY=true
MCP_DB_MAX_ROWS=200
MCP_FS_READONLY=false
MCP_AUDIT_ENABLED=true
MCP_LOG_CHANNEL=stack
```

### config/mcp.php (after publish)

```php
'tools' => [
    'artisan' => [
        'enabled' => true,
        'allowed_commands' => ['*'],
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
        'readonly' => true,   // Blocks INSERT/UPDATE/DELETE/DROP/ALTER
        'max_rows' => 200,
    ],
    'filesystem' => [
        'enabled' => true,
        'allowed_paths' => [base_path()],
        'readonly' => false,
    ],
    'config' => [
        'enabled' => true,
        'allowed_keys' => ['app.*', 'database.*', 'cache.*', 'session.*', 'mail.*', 'services.*', 'mcp.*'],
    ],
],
```

## Security

| Feature | Description |
|---------|-------------|
| Auth token | Bearer token validation (HTTP transport) |
| Artisan whitelist | Only allow listed commands |
| Artisan blocklist | Block dangerous commands (db:wipe, migrate:fresh, ...) |
| DB readonly | Auto-block non-SELECT SQL |
| Max rows | Limit query result rows |
| Path validation | Restrict file access to allowed directories |
| FS readonly | Read-only filesystem mode |
| Config whitelist | Only expose whitelisted config keys |
| Audit log | Log all tool calls to Laravel log |

## Testing

```bash
composer install
vendor/bin/phpunit
```

## Development

```bash
git clone https://github.com/nathanpixodeo/laravel-mcp.git
cd laravel-mcp
composer install
```

## License

MIT
