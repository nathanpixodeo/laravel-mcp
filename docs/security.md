# Security

## Overview

Laravel MCP is designed with a security-first approach. Every tool is checked against configured policies before execution.

## Authentication

### STDIO transport

Auth is automatically bypassed when using CLI (SSH provides the security layer).

### HTTP transport

Auth uses a Bearer token. Enable in `.env`:

```
MCP_AUTH_ENABLED=true
MCP_AUTH_TOKEN=<your-token>
```

Agent sends token via header:
```
Authorization: Bearer <your-token>
```

## Tool-level Security

### Artisan

- **allowed_commands**: Only allow listed commands (`'*'` = all)
- **blocked_commands**: Block dangerous commands:
  - `db:wipe` — drops entire database
  - `migrate:fresh` — drops all tables
  - `migrate:reset` — rollback migrations
  - `key:generate` — changes APP_KEY, invalidates sessions
  - `env:encrypt` — encrypts .env

### Database

- **readonly mode** (default: `true`): Blocks all non-SELECT SQL
- **max_rows** (default: 200): Limits returned rows
- **Parameterized queries**: All bindings use prepared statements

### Filesystem

- **allowed_paths** (default: `[base_path()]`): Restrict access to project files
- **Path traversal detection**: Auto-blocks `..` in paths
- **readonly mode** (default: `false`): When enabled, blocks all `file_write`

### Config

- **allowed_keys** (default: `app.*, database.*, cache.*, ...`): Only expose whitelisted keys. Cannot read `.env` directly.

## Audit Logging

All tool calls are logged with:

- Tool name
- Parameters (sanitized — passwords, tokens are redacted)
- Status (success / denied / error)
- Error message (if any)
- IP address (or `cli` for stdio)
- Timestamp

Log channel: `stack` (default), configurable via `MCP_LOG_CHANNEL`.

Disable audit logging:
```
MCP_AUDIT_ENABLED=false
```

## Production Security Checklist

- [ ] Use SSH tunnel (stdio) over HTTP when possible
- [ ] If using HTTP: enable auth token, use > 32 chars
- [ ] If using HTTP: always use HTTPS
- [ ] Configure blocked_commands appropriately
- [ ] Enable DB readonly mode
- [ ] Review allowed_paths for filesystem
- [ ] Enable audit logging
- [ ] Rate limit `/mcp` endpoint on nginx
- [ ] Periodically review audit logs
