# Transport: STDIO

STDIO transport connects the AI agent to the Laravel MCP server via **standard input/output**. It is the simplest transport, typically used with an SSH tunnel.

## How it works

The agent runs `php artisan mcp:serve` on the server and communicates through stdin/stdout using JSON-RPC 2.0.

## Claude CLI Configuration

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

### Local PHP

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

Use SSH key pairs instead of passwords. Create a dedicated deploy key:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/mcp-deploy -C "mcp-agent"
cat ~/.ssh/mcp-deploy.pub >> ~/.ssh/authorized_keys
```

SSH keep-alive config:

```
Host example.com
    HostName example.com
    IdentityFile ~/.ssh/mcp-deploy
    ServerAliveInterval 30
    ServerAliveCountMax 3
```

## Pros

- No HTTP port exposure needed
- No extra PSR-7 packages required
- SSH provides transport encryption
- Simple to set up

## Cons

- Requires SSH access to the server
- Not suitable when agent cannot SSH directly
