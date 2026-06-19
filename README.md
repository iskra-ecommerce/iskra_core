# Iskra Core — OpenCart 4 Extension

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://php.net)
[![OpenCart](https://img.shields.io/badge/OpenCart-4.1%2B-green)](https://opencart.com)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Release](https://img.shields.io/github/v/release/iskra-ecommerce/iskra_core)](https://github.com/iskra-ecommerce/iskra_core/releases)

**Version:** 1.0.0 | **Type:** Module | **Code:** `iskra_core`

> **Русский:** [README.ru.md](README.ru.md)

---

## Description

Iskra Core is the foundational extension for the Iskra ecosystem. It provides essential infrastructure services used by all other Iskra extensions: logging, event bus, task queue, extended settings, database migrations, integrity checks, and automated backups.

**Keywords:** opencart 4 extension, opencart module, opencart logging, opencart backup, opencart migration, opencart cyrillic support, russian opencart, український opencart.

---

## Features

- **Logging System** — Structured logs with 5 levels (debug, info, warning, error, critical), categories, user tracking, and full Cyrillic support.
- **Global Logging Toggle** — Enable or disable log writing without uninstalling the extension.
- **Auto Log Rotation** — Storage-aware cleanup with 3 strategies:
  - `rows` — limit by row count
  - `table_size` — limit by database table size (MB)
  - `disk_space` — limit by available disk space (MB)
- **Event Bus** — Synchronous and asynchronous event communication between extensions.
- **Task Queue** — Background job processing with priority, attempts, and scheduling.
- **Database Migrations** — Versioned schema changes with tracking, checksums, and rollback support.
- **Integrity Checks** — Pre/post-install verification of all Iskra tables.
- **Backup System** — Automatic backup before install/uninstall, manual backup creation, restore, and retention management.
- **Cyrillic Support** — Full UTF-8 MB4 encoding for Russian, Ukrainian, Kazakh, Belarusian, and Romanian.

---

## Installation

### Via OpenCart Admin (recommended)

1. **System → Extensions → Installer**
2. Upload `iskra_core-1.0.0.ocmod.zip`
3. **System → Extensions → Extensions → Modules**
4. Find **Iskra: Core** → click **Install**
5. Click **Edit** to configure settings

### Manual

1. Copy `extension/iskra_core/` to your OpenCart root
2. Go to **System → Extensions → Extensions → Modules**
3. Find **Iskra: Core** → **Install**

---

## Admin UI

After installation, navigate to:
**Extensions → Extensions → Modules → Iskra: Core → Edit**

### Tabs

- **Settings** — Logging toggle, log levels, retention, rotation strategies, Event Bus, Queue, backup retention.
- **Logs** — Filterable, sortable, paginated log viewer with bulk delete and manual rotation.
- **Backups** — Create, restore, download, and delete database backups.
- **Migrations** — View migration status, run pending migrations, rollback last migration.
- **Integrity** — Check table existence, row counts, sizes, and collation.

---

## Database Tables

| Table | Purpose |
|-------|---------|
| `oc_iskra_log` | Application logs |
| `oc_iskra_event_log` | Event Bus events |
| `oc_iskra_queue` | Background task queue |
| `oc_iskra_setting_extra` | Extended settings |
| `oc_iskra_migration` | Migration tracking |

---

## Uninstall

**System → Extensions → Extensions → Modules → Iskra: Core → Uninstall**

⚠️ **Warning:** Uninstalling will drop all Iskra tables and delete settings. An automatic pre-uninstall backup is created in `storage/backup/iskra_core/`.

---

## Development

### Build ZIP

```bash
cd extension/iskra_core/
zip -r ../../iskra_core-1.0.0.ocmod.zip install.json admin/ catalog/ system/ install.php uninstall.php README.md README.ru.md
```

### Dependencies

- OpenCart 4.1+
- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+

---

## License

MIT License. Made by the **Iskra** team.

---

[🇷🇺 Русская версия](README.ru.md)
