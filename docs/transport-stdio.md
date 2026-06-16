# Transport: STDIO

STDIO transport dùng để kết nối AI agent với Laravel MCP server qua **standard input/output**. Đây là transport đơn giản nhất, thường được dùng với SSH tunnel.

## Cách hoạt động

Agent chạy lệnh `php artisan mcp:serve` trên server, giao tiếp qua stdin/stdout bằng JSON-RPC 2.0.

## Cấu hình Claude CLI

### Linux / macOS

```json
{
  "mcpServers": {
    "laravel-mcp": {
      "command": "ssh",
      "args": [
        "user@example.com",
        "-t",
        "cd /var/www/laravel && php artisan mcp:serve"
      ]
    }
  }
}
```

### Nếu có sẵn PHP local

```json
{
  "mcpServers": {
    "laravel-mcp": {
      "command": "php",
      "args": ["artisan", "mcp:serve"]
    }
  }
}
```

## SSH Key

Nên dùng SSH key pair thay vì password. Tạo dedicated deploy key với quyền tối thiểu:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/mcp-deploy -C "mcp-agent"
cat ~/.ssh/mcp-deploy.pub >> ~/.ssh/authorized_keys
```

Cấu hình SSH để keep alive:

```
Host example.com
    HostName example.com
    IdentityFile ~/.ssh/mcp-deploy
    ServerAliveInterval 30
    ServerAliveCountMax 3
```

## Ưu điểm

- Không cần expose port HTTP
- Không cần cài thêm package PSR-7
- Bảo mật qua SSH
- Đơn giản, dễ setup

## Nhược điểm

- Cần SSH access
- Không dùng được nếu agent ở network khác không SSH được
