# Tools Reference

## artisan

Run Artisan commands.

**Parameters:**
- `command` (string, required): Artisan command name
- `parameters` (array, optional): Command arguments

**Examples:**
```
artisan("cache:clear")
artisan("route:list")
artisan("make:model", {"name": "Product"})
```

**Security:**
- Blocked commands: `db:wipe`, `migrate:fresh`, `migrate:reset`, `key:generate`, `env:encrypt`
- Customize via `config/mcp.php` → `tools.artisan.blocked_commands`
- Whitelist via `config/mcp.php` → `tools.artisan.allowed_commands`

---

## db_query

Execute SQL query. Mặc định chỉ cho phép SELECT.

**Parameters:**
- `sql` (string, required): SQL query
- `bindings` (array, optional): Parameter bindings

**Examples:**
```
db_query("SELECT * FROM users WHERE id = ?", [1])
db_query("SELECT name, email FROM users ORDER BY created_at DESC LIMIT 10")
```

**Security:**
- Readonly mode chặn: `INSERT`, `UPDATE`, `DELETE`, `DROP`, `ALTER`, `CREATE`, `TRUNCATE`, `REPLACE`
- Max rows: 200 (configurable via `MCP_DB_MAX_ROWS`)
- Dùng parameterized bindings, không concatenate SQL

---

## db_schema

Show database schema: tables, columns, types.

**Examples:**
```
db_schema()
```

Output:
```
Table: users
  id | INTEGER | NOT NULL
  name | VARCHAR(255) | NOT NULL
  email | VARCHAR(255) | NOT NULL

Table: posts
  id | INTEGER | NOT NULL
  title | VARCHAR(255) | NOT NULL
```

---

## file_read

Đọc nội dung file trong project.

**Parameters:**
- `path` (string, required): Relative từ base_path() hoặc absolute path

**Examples:**
```
file_read(".env")
file_read("app/Models/User.php")
```

**Security:**
- Chỉ đọc được file trong `allowed_paths` (mặc định: `base_path()`)
- Không cho path traversal với `..`

---

## file_write

Ghi file trong project.

**Parameters:**
- `path` (string, required): Relative hoặc absolute path
- `content` (string, required): Nội dung file

**Examples:**
```
file_write("config/custom.php", "<?php return [];")
```

**Security:**
- Có thể tắt bằng `MCP_FS_READONLY=true`
- Chỉ ghi trong `allowed_paths`

---

## file_list

Liệt kê nội dung thư mục.

**Parameters:**
- `path` (string, optional, default: root): Đường dẫn thư mục

**Examples:**
```
file_list()
file_list("app/Http/Controllers")
```

---

## file_search

Tìm kiếm file theo pattern.

**Parameters:**
- `pattern` (string, required): Glob pattern
- `path` (string, optional, default: root): Thư mục tìm kiếm

**Examples:**
```
file_search("*.php")
file_search("User*.php", "app/Models")
```

**Note:** Giới hạn 100 kết quả.

---

## logs

Xem Laravel logs.

**Parameters:**
- `lines` (int, optional, default: 50): Số dòng gần nhất
- `level` (string, optional): Filter theo level (EMERGENCY, ERROR, WARNING, INFO, DEBUG)

**Examples:**
```
logs()
logs(100, "ERROR")
```

---

## routes

List tất cả routes.

**Parameters:**
- `method` (string, optional): Filter theo HTTP method (GET, POST, etc.)

**Examples:**
```
routes()
routes("GET")
```

---

## config_get

Đọc config value.

**Parameters:**
- `key` (string, required): Config key (dot notation)

**Examples:**
```
config_get("app.name")
config_get("database.connections.mysql")
```

**Security:**
- Chỉ đọc keys trong `allowed_keys`:
  - `app.*`, `database.*`, `cache.*`, `session.*`, `mail.*`, `services.*`, `mcp.*`

---

## env_info

Thông tin môi trường hiện tại.

**Examples:**
```json
{
  "app": {
    "name": "My App",
    "env": "production",
    "debug": false
  },
  "laravel": { "version": "13.0.0" },
  "php": { "version": "8.3.0", "os": "Linux" },
  "database": { "connection": "mysql", "driver": "mysql" },
  "mcp": {
    "transport": "stdio",
    "auth_enabled": true,
    "db_readonly": true,
    "fs_readonly": false
  }
}
```
