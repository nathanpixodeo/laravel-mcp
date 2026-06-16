# Transport: HTTP

HTTP transport dùng để kết nối AI agent với Laravel MCP server qua một **route HTTP** trong Laravel application. Thích hợp cho production khi agent cần connect từ xa mà không cần SSH.

## Yêu cầu

```bash
composer require nyholm/psr7 nyholm/psr7-server
```

## Cấu hình

### .env

```
MCP_HTTP_ENABLED=true
MCP_HTTP_PATH=mcp
MCP_AUTH_ENABLED=true
MCP_AUTH_TOKEN=your-super-secret-token
```

### Mặc định

- Route: `https://example.com/mcp`
- Method: POST (MCP Streamable HTTP)
- Auth: Bearer token

## Cấu hình Claude CLI

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

## Bảo mật

### Auth token

Luôn bật auth token khi dùng HTTP transport:

```
MCP_AUTH_ENABLED=true
MCP_AUTH_TOKEN=<random-64-char-string>
```

Tạo token:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

### Nginx

Nên restrict method và rate limit:

```nginx
location /mcp {
    limit_except POST OPTIONS { deny all; }
    limit_req zone=mcp burst=10 nodelay;
    proxy_pass http://php-fpm;
}
```

### HTTPS

Luôn dùng HTTPS. Redirect HTTP → HTTPS.

## Ưu điểm

- Agent connect trực tiếp qua URL
- Không cần SSH
- Dễ tích hợp với cloud IDE, CI/CD

## Nhược điểm

- Cần expose endpoint ra internet
- Cần auth token để bảo mật
- Cần cài thêm PSR-7 packages

## Troubleshooting

### 500 Missing PSR-7 implementation

Chạy:
```bash
composer require nyholm/psr7 nyholm/psr7-server
```

### 401 Unauthorized

Kiểm tra `MCP_AUTH_TOKEN` trong `.env` và `Authorization` header trong config agent.

### Route not found

Kiểm tra `MCP_HTTP_ENABLED=true` và `MCP_HTTP_PATH` (mặc định `mcp`). Nhớ `php artisan config:clear` nếu dùng env.
