# iskra-core

> Ядро проекта **OpenCart 4: Искра** — реестр сервисов, шина событий, логирование, очередь задач.

[![Версия](https://img.shields.io/badge/Версия-0.1.0-blue.svg)]()
[![Лицензия](https://img.shields.io/badge/Лицензия-GPL--3.0-green.svg)]()
[![OpenCart](https://img.shields.io/badge/OpenCart-4.1+-orange.svg)]()

---

## О проекте

**iskra-core** — это базовое расширение, без которого не работает ни одно другое расширение Искра. Оно предоставляет:

- **Service Registry** — реестр сервисов для связи между расширениями
- **Event Bus** — шина событий (синхронная + асинхронная очередь)
- **Logger** — система логирования с ротацией по времени и размеру
- **Queue** — очередь фоновых задач (cron-обработка)

---

## Установка

### Требования

- OpenCart 4.1.0+
- PHP 8.4+
- MySQL 8.0+ / MariaDB 10.5+
- Расширение PDO_MySQL

### Через админку

1. Скачайте `iskra_core.ocmod.zip` из [Releases](https://github.com/iskra-ecommerce/iskra-core/releases)
2. **Система → Расширения → Установщик расширений** → Загрузить файл
3. **Система → Расширения → Модификации** → Обновить
4. **Система → Расширения → Расширения** → Модули → `iskra_core` → Установить

### Вручную (для разработки)

```bash
# Скопировать в папку расширений
cp -r extension/iskra_core/ /path/to/opencart/extension/

# Запустить установку через CLI (если поддерживается)
php cli.php extension=iskra_core action=install
```

---

## Использование

### Service Registry

```php
// Зарегистрировать свой сервис
$registry = $this->registry->get('iskra_service_registry');
$registry->register('iskra_analytic', new AnalyticService($this->registry));

// Получить чужой сервис
$analytic = $this->registry->get('iskra_analytic');
```

### Event Bus

#### Синхронные события

```php
// Подписка
$bus = $this->registry->get('iskra_event_bus');
$bus->subscribe('iskra_order_paid', function(array $payload, string $eventCode) {
    // Обработка
    $orderId = $payload['order_id'];
    $this->log->info('order', "Order $orderId paid");
}, 10);  // Приоритет 10

// Публикация
$bus->publish('iskra_order_paid', [
    'order_id' => 42,
    'amount' => 1500.00,
]);
```

#### Асинхронные события (через очередь)

```php
// Поставить в очередь
$bus->queue('iskra_notify_send_email', [
    'to' => 'customer@example.com',
    'template' => 'order_paid',
    'data' => [...],
], 5);  // Приоритет 5

// Обработать очередь (в cron)
$processed = $bus->processQueue(50);
echo "Processed $processed tasks";
```

### Logger

```php
$logger = $this->registry->get('iskra_logger');

$logger->debug('order', 'Order details', ['order_id' => 42]);
$logger->info('order', 'Order created', ['order_id' => 42]);
$logger->warning('payment', 'Payment retry', ['attempts' => 2]);
$logger->error('integration', 'API timeout', ['service' => 'rozetka']);
$logger->critical('security', 'SQL injection attempt', ['ip' => '1.2.3.4']);
```

### Ротация логов

Включите cron для ежедневной ротации:

```bash
# Каждый день в 03:00
0 3 * * * cd /path/to/opencart && php cli.php iskra:log:rotate
```

---

## Структура расширения

```
extension/iskra_core/
├── install.php                      — Установка
├── uninstall.php                    — Удаление
├── admin/
│   ├── controller/                  — Контроллеры админки
│   ├── model/                       — Модели
│   └── language/
│       ├── en-gb/                   — Английский
│       └── ru-ru/                   — Русский
├── system/
│   └── library/iskra/
│       ├── service_registry.php     — Реестр сервисов
│       ├── event_bus.php            — Шина событий
│       └── logger.php               — Логгер
└── docs/
    ├── README.md                    — Этот файл
    └── CHANGELOG.md                 — История версий
```

---

## События

### Публикуемые iskra-core

- `iskra_core_installed` — расширение установлено
- `iskra_core_uninstalled` — расширение удалено
- `iskra_log_rotated` — ротация логов выполнена
- `iskra_queue_processed` — очередь обработана (через cron)

### Подписки iskra-core

Нет (только публикация).

---

## База данных

### Таблицы

#### `iskra_log` — логи
- `log_id` — ID
- `level` — уровень (debug, info, warning, error, critical)
- `category` — категория (auth, order, payment, system...)
- `user_id` — пользователь
- `ip_address` — IP
- `user_agent` — User-Agent
- `message` — сообщение
- `context` — контекст (JSON)
- `date_added` — дата

#### `iskra_event_log` — лог событий
- `event_id` — ID
- `code` — код события
- `payload` — данные (JSON)
- `processed` — обработано ли

#### `iskra_queue` — очередь задач
- `queue_id` — ID
- `code` — код задачи
- `payload` — данные (JSON)
- `priority` — приоритет
- `status` — статус (pending, processing, done, failed)
- `attempts` — попытки

#### `iskra_setting_extra` — расширенные настройки
- `setting_id` — ID
- `code` — код расширения
- `key` — ключ
- `value` — значение
- `store_id` — магазин

---

## Настройки

Все настройки — в `oc_setting` с `code = 'iskra_core'`:

| Ключ | Тип | По умолчанию | Описание |
|------|-----|--------------|----------|
| `iskra_core_log_level` | string | `info` | Минимальный уровень логирования |
| `iskra_core_log_retention_days` | int | `30` | Хранить логи N дней |
| `iskra_core_log_max_size_mb` | int | `5120` | Макс. размер логов (МБ) |
| `iskra_core_event_bus_enabled` | bool | `1` | Включить Event Bus |
| `iskra_core_queue_cron_enabled` | bool | `1` | Включить cron-обработку очереди |

---

## API для других расширений

### Регистрация в `install.php` расширения

```php
$registry = $this->registry;
$serviceRegistry = $registry->get('iskra_service_registry');

// Регистрация своего сервиса
$serviceRegistry->register('iskra_market_discount', new DiscountService($registry));
```

### Подписка на события

```php
$eventBus = $this->registry->get('iskra_event_bus');
$eventBus->subscribe('iskra_order_paid', [new MyHandler($this->registry), 'handle'], 10);
```

### Логирование

```php
$logger = $this->registry->get('iskra_logger');
$logger->info('crm', 'Контрагент создан', ['contractor_id' => 42]);
```

---

## Тестирование

```bash
# Unit-тесты
composer test

# С покрытием
composer test:coverage
```

---

## Лицензия

GPL-3.0-or-later — полностью совместима с OpenCart.

---

## Связанные проекты

- [iskra-theme-default](https://github.com/iskra-ecommerce/iskra-theme-default) — тема по умолчанию
- [iskra-crm](https://github.com/iskra-ecommerce/iskra-crm) — CRM-модуль
- [iskra-task](https://github.com/iskra-ecommerce/iskra-task) — менеджер задач
- [iskra-market](https://github.com/iskra-ecommerce/iskra-market) — маркетинг

---

## Поддержка

- 🐛 [Issues](https://github.com/iskra-ecommerce/iskra-core/issues)
- 💬 [Discussions](https://github.com/iskra-ecommerce/iskra-core/discussions)
- 📧 security@iskra-ecommerce.com (для сообщений об уязвимостях)

---

**Слава Искре!**
