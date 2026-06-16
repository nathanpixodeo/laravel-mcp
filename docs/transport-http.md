# Transport: HTTP

HTTP transport connects the AI agent to the Laravel MCP server via a **route** in your Laravel application. Suitable for production when agents need remote access without SSH.

## Requirements

```bash
composer require nyholm/psr7 nyholm/psr7-server
```

## Configuration

### .env

```
MCP_HTTP_ENABLED=true
MCP_HTTP_PATH=mcp
MCP_AUTH_ENABLED=true
MCP_AUTH_TOKEN=your-super-secret-token
```

### Defaults

- Route: `https://example.com/mcp`
- Method: POST (MCP Streamable HTTP)
- Auth: Bearer token

## Claude CLI Configuration

```json
{
  "mcpServers": {
    "laravel-mcp-http": {
      "url": "https://example.com/mcp",
      "headers": {
        "Authorization": "Bearer your-super-secret-token"
      }
    }
  }
}
```

## Security

### Auth token

Always enable auth token when using HTTP transport:

```
MCP_AUTH_ENABLED=true
MCP_AUTH_TOKEN=<random-64-char-string>
```

Generate a token:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

### Nginx

Restrict methods and apply rate limiting:

```nginx
location /mcp {
    limit_except POST OPTIONS { deny all; }
    limit_req zone=mcp burst=10 nodelay;
    proxy_pass http://php-fpm;
}
```

### HTTPS

Always use HTTPS. Redirect HTTP → HTTPS.

## Pros

- Agent connects directly via URL
- No SSH required
- Easy integration with cloud IDE, CI/CD

## Cons

- Endpoint exposed to internet
- Requires auth token for security
- Extra PSR-7 packages needed

## Troubleshooting

### 500 Missing PSR-7 implementation

```bash
composer require nyholm/psr7 nyholm/psr7-server
```

### 401 Unauthorized

Check `MCP_AUTH_TOKEN` in `.env` and `Authorization` header in agent config.

### Route not found

Check `MCP_HTTP_ENABLED=true` and `MCP_HTTP_PATH` (default: `mcp`). Run `php artisan config:clear` after changing env.
