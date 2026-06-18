# История версий iskra-core

## 0.1.0 (2026-06-05) — Фаза 1: Скелет

### Добавлено
- Service Registry (реестр сервисов)
- Event Bus (шина событий, синхронная)
- Logger (система логирования с ротацией)
- Queue (асинхронная очередь задач)
- install.php (создание таблиц, прав, настроек)
- uninstall.php (полный откат)
- README.md
- CHANGELOG.md
- Языковые файлы (en-gb, ru-ru)

### Таблицы БД
- `iskra_log` — логи системы
- `iskra_event_log` — лог событий
- `iskra_queue` — очередь задач
- `iskra_setting_extra` — расширенные настройки

### Настройки по умолчанию
- `iskra_core_log_level` = info
- `iskra_core_log_retention_days` = 30
- `iskra_core_log_max_size_mb` = 5120
- `iskra_core_event_bus_enabled` = 1
- `iskra_core_queue_cron_enabled` = 1

---

## Будущие версии

### 0.2.0 (план)
- HTTP Client (для интеграций с маркетплейсами)
- Mailer (SMTP)
- Cache Manager
- CLI-команды (artisan-style)

### 0.3.0 (план)
- Notifier (Telegram, SMS)
- Backup Manager
- Seeder (тестовые данные)

### 1.0.0 (план, Фаза 2)
- Полная стабилизация API
- 100% покрытие тестами критических сервисов
- Документация для разработчиков расширений
