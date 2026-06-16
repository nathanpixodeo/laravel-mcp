# Security

## Overview

Laravel MCP được thiết kế với security-first approach. Mọi tool đều được kiểm tra quyền trước khi thực thi.

## Authentication

### STDIO transport

Auth tự động bypass khi dùng CLI (vì đã được bảo vệ bởi SSH).

### HTTP transport

Auth dùng Bearer token. Bật trong `.env`:

```
MCP_AUTH_ENABLED=true
MCP_AUTH_TOKEN=<your-token>
```

Agent gửi token qua header:
```
Authorization: Bearer <your-token>
```

## Tool-level Security

### Artisan

- **allowed_commands**: Chỉ cho phép command trong danh sách (`'*'` = tất cả)
- **blocked_commands**: Chặn command nguy hiểm:
  - `db:wipe` — xoá toàn bộ database
  - `migrate:fresh` — drop tất cả tables
  - `migrate:reset` — rollback migrations
  - `key:generate` — đổi APP_KEY, invalidate sessions
  - `env:encrypt` — encrypt .env

### Database

- **readonly mode** (mặc định: `true`): Chặn mọi câu SQL không phải SELECT
- **max_rows** (mặc định: 200): Giới hạn số dòng trả về
- **Parameterized queries**: Tất cả bindings đều qua prepared statements

### Filesystem

- **allowed_paths** (mặc định: `[base_path()]`): Chỉ cho đọc/ghi file trong project
- **Path traversal detection**: Tự động chặn `..` trong path
- **readonly mode** (mặc định: `false`): Khi bật, chặn mọi `file_write`

### Config

- **allowed_keys** (mặc định: `app.*, database.*, cache.*, ...`): Chỉ cho đọc keys được liệt kê. Không cho đọc `.env` trực tiếp.

## Audit Logging

Tất cả tool calls đều được log với:

- Tool name
- Parameters (sanitized: mật khẩu, token bị redact)
- Status (success / denied / error)
- Error message (nếu có)
- IP address (hoặc `cli` nếu dùng stdio)
- Timestamp

Log channel: `stack` (mặc định), configurable qua `MCP_LOG_CHANNEL`.

Tắt audit log:
```
MCP_AUDIT_ENABLED=false
```

## Security Checklist cho Production

- [ ] Dùng SSH tunnel (stdio) thay vì HTTP nếu có thể
- [ ] Nếu dùng HTTP: bật auth token, dùng token dài (> 32 ký tự)
- [ ] Nếu dùng HTTP: luôn dùng HTTPS
- [ ] Config blocked_commands phù hợp
- [ ] Bật DB readonly mode
- [ ] Review allowed_paths cho filesystem
- [ ] Bật audit logging
- [ ] Rate limit endpoint `/mcp` trên nginx
- [ ]定期 review audit logs
