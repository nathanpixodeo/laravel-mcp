# Laravel MCP

MCP (Model Context Protocol) server cho Laravel. Cho phép AI agents (Claude CLI, Claude Desktop, Cursor, etc.) kết nối đến Laravel project để debug, kiểm tra, và quản lý từ xa qua SSH tunnel hoặc HTTP.

## Features

- 11 built-in tools: artisan, database, filesystem, logs, routes, config, env
- **2 transports**: stdio (SSH tunnel) và HTTP (route)
- **Security**: auth token, path whitelist, artisan command blocking, read-only modes
- **Audit logging**: tất cả tool calls đều được ghi log
- **Config-driven**: bật/tắt từng tool group, whitelist keys, v.v.

## Installation

```bash
composer require nathanpixodeo/laravel-mcp
```

Nếu dùng HTTP transport, cần thêm:

```bash
composer require nyholm/psr7 nyholm/psr7-server
```

### Publish config (optional)

```bash
php artisan vendor:publish --tag=mcp-config
```

## Usage

### Transport 1: STDIO (qua SSH tunnel)

Trên server:

```bash
php artisan mcp:serve
```

Cấu hình trong Claude Desktop / Claude CLI (`claude_desktop_config.json`):

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

### Transport 2: HTTP (qua route)

Set trong `.env`:

```
MCP_HTTP_ENABLED=true
MCP_AUTH_ENABLED=true
MCP_AUTH_TOKEN=your-secure-token
```

Mặc định route là `/mcp`. Có thể đổi qua env `MCP_HTTP_PATH`.

Agent sẽ connect tới: `https://example.com/mcp` với header `Authorization: Bearer your-secure-token`.

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
        'readonly' => true,   // Chặn INSERT/UPDATE/DELETE/DROP/ALTER
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
| Artisan whitelist | Chỉ cho phép command được liệt kê |
| Artisan blocklist | Chặn command nguy hiểm (db:wipe, migrate:fresh, ...) |
| DB readonly | Tự động chặn câu SQL không phải SELECT |
| Max rows | Giới hạn số dòng trả về từ database |
| Path validation | Không cho đọc/ghi file ngoài allowed_paths |
| FS readonly | Chế độ chỉ đọc cho filesystem |
| Config whitelist | Chỉ cho phép đọc config keys được liệt kê |
| Audit log | Ghi lại tất cả tool calls vào Laravel log |

## Development

```bash
git clone https://github.com/nathanpixodeo/laravel-mcp.git
cd laravel-mcp
composer install
```

## License

MIT
