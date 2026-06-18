<?php
/**
 * iskra-core — установка
 * Документация: https://github.com/iskra-ecommerce/iskra-core
 * Конституция: ../CONSTITUTION.md
 */

declare(strict_types=1);

namespace Opencart\Extension\IskraCore;

use Opencart\System\Engine\Action;
use Opencart\System\Engine\Registry;
use Opencart\System\Library\DB;

/**
 * Класс установки расширения iskra-core
 *
 * Создаёт:
 * - Таблицы: iskra_log, iskra_event_log, iskra_setting_extra
 * - События: базовые события для Event Bus
 * - Права: iskra_core_view, iskra_core_modify
 * - Настройки по умолчанию
 */
final class Installer
{
    private Registry $registry;
    private DB $db;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
        $this->db = $registry->get('db');
    }

    /**
     * Главный метод установки
     */
    public function install(): void
    {
        $this->createMigrationTable();
        $this->autoBackupIfNeeded();
        $this->runMigrations();
        $this->registerPermissions();
        $this->setDefaultSettings();
        $this->logInfo('iskra-core installed');
        $this->checkIntegrity();
    }

    /**
     * Создание таблицы миграций
     */
    private function createMigrationTable(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "iskra_migration` (
                `migration_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `version` VARCHAR(32) NOT NULL,
                `name` VARCHAR(128) NOT NULL,
                `up_sql` LONGTEXT NULL,
                `down_sql` LONGTEXT NULL,
                `checksum` CHAR(64) NULL,
                `status` ENUM('pending','success','failed','rolled_back') DEFAULT 'pending',
                `error` TEXT NULL,
                `applied_at` DATETIME NULL,
                `rolled_back_at` DATETIME NULL,
                PRIMARY KEY (`migration_id`),
                UNIQUE KEY `uk_version_name` (`version`, `name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Авто-бэкап если таблицы уже существуют
     */
    private function autoBackupIfNeeded(): void
    {
        $tables = ['iskra_log', 'iskra_event_log', 'iskra_queue', 'iskra_setting_extra', 'iskra_migration'];
        $anyExists = false;
        foreach ($tables as $table) {
            $q = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . $table . "'");
            if ($q->num_rows > 0) {
                $anyExists = true;
                break;
            }
        }
        if ($anyExists) {
            $this->createBackup();
        }
    }

    /**
     * Запуск миграций
     */
    private function runMigrations(): void
    {
        $migrations = $this->getMigrations();
        foreach ($migrations as $migration) {
            $versionEscaped = $this->db->escape($migration['version']);
            $nameEscaped = $this->db->escape($migration['name']);
            $existing = $this->db->query(
                "SELECT `status`, `checksum` FROM `" . DB_PREFIX . "iskra_migration`
                 WHERE `version` = '" . $versionEscaped . "' AND `name` = '" . $nameEscaped . "'"
            )->row;

            if ($existing && $existing['status'] === 'success') {
                continue;
            }

            $checksum = hash('sha256', $migration['up_sql']);
            if ($existing && $existing['checksum'] === $checksum && $existing['status'] === 'failed') {
                // Retry failed migration with same checksum
            }

            $upSqlEscaped = $this->db->escape($migration['up_sql']);
            $downSqlEscaped = $this->db->escape($migration['down_sql']);

            try {
                $this->db->query($migration['up_sql']);
                $this->db->query(
                    "INSERT INTO `" . DB_PREFIX . "iskra_migration`
                     (`version`, `name`, `up_sql`, `down_sql`, `checksum`, `status`, `applied_at`)
                     VALUES ('" . $versionEscaped . "', '" . $nameEscaped . "', '" . $upSqlEscaped . "', '" . $downSqlEscaped . "', '" . $this->db->escape($checksum) . "', 'success', NOW())
                     ON DUPLICATE KEY UPDATE
                     `status` = 'success', `error` = NULL, `applied_at` = NOW(), `checksum` = VALUES(`checksum`)"
                );
            } catch (\Throwable $e) {
                $errorMsg = $this->db->escape($e->getMessage());
                $this->db->query(
                    "INSERT INTO `" . DB_PREFIX . "iskra_migration`
                     (`version`, `name`, `up_sql`, `down_sql`, `checksum`, `status`, `error`)
                     VALUES ('" . $versionEscaped . "', '" . $nameEscaped . "', '" . $upSqlEscaped . "', '" . $downSqlEscaped . "', '" . $this->db->escape($checksum) . "', 'failed', '" . $errorMsg . "')
                     ON DUPLICATE KEY UPDATE
                     `status` = 'failed', `error` = VALUES(`error`), `checksum` = VALUES(`checksum`)"
                );
                throw new \RuntimeException("Migration {$migration['version']}.{$migration['name']} failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Список миграций
     */
    private function getMigrations(): array
    {
        return [
            [
                'version' => '1.0.0',
                'name' => 'create_log_table',
                'up_sql' => "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "iskra_log` (
                    `log_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `level` ENUM('debug','info','warning','error','critical') NOT NULL DEFAULT 'info',
                    `category` VARCHAR(64) NOT NULL,
                    `user_id` INT(11) UNSIGNED NULL,
                    `ip_address` VARCHAR(45) NULL,
                    `user_agent` VARCHAR(255) NULL,
                    `message` TEXT NOT NULL,
                    `context` JSON NULL,
                    `date_added` DATETIME NOT NULL,
                    PRIMARY KEY (`log_id`),
                    KEY `idx_level` (`level`),
                    KEY `idx_category` (`category`),
                    KEY `idx_user` (`user_id`),
                    KEY `idx_date` (`date_added`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                'down_sql' => "DROP TABLE IF EXISTS `" . DB_PREFIX . "iskra_log`",
            ],
            [
                'version' => '1.0.0',
                'name' => 'create_event_log_table',
                'up_sql' => "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "iskra_event_log` (
                    `event_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `code` VARCHAR(128) NOT NULL,
                    `trigger` VARCHAR(255) NULL,
                    `payload` JSON NULL,
                    `processed` TINYINT(1) DEFAULT 0,
                    `error` TEXT NULL,
                    `date_added` DATETIME NOT NULL,
                    `date_processed` DATETIME NULL,
                    PRIMARY KEY (`event_id`),
                    KEY `idx_code` (`code`),
                    KEY `idx_processed` (`processed`),
                    KEY `idx_date` (`date_added`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                'down_sql' => "DROP TABLE IF EXISTS `" . DB_PREFIX . "iskra_event_log`",
            ],
            [
                'version' => '1.0.0',
                'name' => 'create_queue_table',
                'up_sql' => "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "iskra_queue` (
                    `queue_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `code` VARCHAR(128) NOT NULL,
                    `payload` JSON NOT NULL,
                    `priority` TINYINT(1) DEFAULT 5,
                    `attempts` TINYINT(1) DEFAULT 0,
                    `status` ENUM('pending','processing','done','failed') DEFAULT 'pending',
                    `error` TEXT NULL,
                    `scheduled_at` DATETIME NULL,
                    `date_added` DATETIME NOT NULL,
                    `date_processed` DATETIME NULL,
                    PRIMARY KEY (`queue_id`),
                    KEY `idx_code` (`code`),
                    KEY `idx_status` (`status`),
                    KEY `idx_scheduled` (`scheduled_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                'down_sql' => "DROP TABLE IF EXISTS `" . DB_PREFIX . "iskra_queue`",
            ],
            [
                'version' => '1.0.0',
                'name' => 'create_setting_extra_table',
                'up_sql' => "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "iskra_setting_extra` (
                    `setting_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `code` VARCHAR(64) NOT NULL,
                    `key` VARCHAR(128) NOT NULL,
                    `value` TEXT,
                    `serialized` TINYINT(1) DEFAULT 0,
                    `store_id` INT(11) UNSIGNED DEFAULT 0,
                    `date_added` DATETIME NOT NULL,
                    `date_modified` DATETIME NOT NULL,
                    PRIMARY KEY (`setting_id`),
                    UNIQUE KEY `uk_code_key_store` (`code`, `key`, `store_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                'down_sql' => "DROP TABLE IF EXISTS `" . DB_PREFIX . "iskra_setting_extra`",
            ],
            [
                'version' => '1.0.0',
                'name' => 'create_migration_table',
                'up_sql' => "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "iskra_migration` (
                    `migration_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `version` VARCHAR(32) NOT NULL,
                    `name` VARCHAR(128) NOT NULL,
                    `up_sql` LONGTEXT NULL,
                    `down_sql` LONGTEXT NULL,
                    `checksum` CHAR(64) NULL,
                    `status` ENUM('pending','success','failed','rolled_back') DEFAULT 'pending',
                    `error` TEXT NULL,
                    `applied_at` DATETIME NULL,
                    `rolled_back_at` DATETIME NULL,
                    PRIMARY KEY (`migration_id`),
                    UNIQUE KEY `uk_version_name` (`version`, `name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                'down_sql' => "DROP TABLE IF EXISTS `" . DB_PREFIX . "iskra_migration`",
            ],
        ];
    }

    /**
     * Регистрация прав для ролей пользователей
     */
    private function registerPermissions(): void
    {
        $permissions = [
            'iskra_core' => [
                'access' => ['owner', 'admin', 'manager'],
                'modify' => ['owner', 'admin', 'manager'],
                'delete' => ['owner', 'admin'],
            ],
            'iskra_log' => [
                'access' => ['owner', 'admin'],
                'modify' => ['owner', 'admin'],
                'delete' => ['owner', 'admin'],
            ],
        ];

        // Получить все существующие группы
        $query = $this->db->query("SELECT `user_group_id`, `name`, `permission` FROM `" . DB_PREFIX . "user_group`");

        foreach ($query->rows as $group) {
            $existing = json_decode($group['permission'] ?? '{}', true) ?? [];
            $merged = $this->mergePermissions($existing, $permissions);

            $this->db->query(
                "UPDATE `" . DB_PREFIX . "user_group`
                 SET `permission` = '" . $this->db->escape(json_encode($merged, JSON_UNESCAPED_UNICODE)) . "'
                 WHERE `user_group_id` = " . (int)$group['user_group_id']
            );
        }
    }

    /**
     * Слияние прав с учётом ролей
     */
    private function mergePermissions(array $existing, array $new): array
    {
        $result = $existing;

        foreach ($new as $module => $actions) {
            if (!isset($result[$module])) {
                $result[$module] = [];
            }

            foreach ($actions as $action => $allowedRoles) {
                // Если право уже есть, оставляем как есть
                if (!isset($result[$module][$action])) {
                    // Сопоставляем имена ролей OpenCart
                    $result[$module][$action] = $this->mapRolesToOpenCartNames($allowedRoles);
                }
            }
        }

        return $result;
    }

    /**
     * Преобразование имён ролей в имена OpenCart
     */
    private function mapRolesToOpenCartNames(array $roles): array
    {
        $mapping = [
            'owner' => 'Top Administrator',
            'admin' => 'Administrator',
            'manager' => 'manager',
        ];

        $result = [];
        foreach ($roles as $role) {
            $result[] = $mapping[$role] ?? $role;
        }

        return $result;
    }

    /**
     * Установка настроек по умолчанию
     */
    private function setDefaultSettings(): void
    {
        $defaults = [
            [
                'code' => 'iskra_core',
                'key' => 'iskra_core_log_level',
                'value' => 'info',
            ],
            [
                'code' => 'iskra_core',
                'key' => 'iskra_core_log_retention_days',
                'value' => '30',
            ],
            [
                'code' => 'iskra_core',
                'key' => 'iskra_core_log_max_size_mb',
                'value' => '5120',  // 5 ГБ
            ],
            [
                'code' => 'iskra_core',
                'key' => 'iskra_core_logging_enabled',
                'value' => '1',
            ],
            [
                'code' => 'iskra_core',
                'key' => 'iskra_core_event_bus_enabled',
                'value' => '1',
            ],
            [
                'code' => 'iskra_core',
                'key' => 'iskra_core_queue_cron_enabled',
                'value' => '1',
            ],
            [
                'code' => 'iskra_core',
                'key' => 'iskra_core_log_limit_strategy',
                'value' => 'table_size',
            ],
            [
                'code' => 'iskra_core',
                'key' => 'iskra_core_log_max_rows',
                'value' => '500000',
            ],
            [
                'code' => 'iskra_core',
                'key' => 'iskra_core_log_min_disk_mb',
                'value' => '1000',
            ],
            [
                'code' => 'iskra_core',
                'key' => 'iskra_core_backup_retention_days',
                'value' => '30',
            ],
        ];

        foreach ($defaults as $setting) {
            $existing = $this->db->query(
                "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "setting`
                 WHERE `code` = '" . $this->db->escape($setting['code']) . "' AND `key` = '" . $this->db->escape($setting['key']) . "' AND `store_id` = 0"
            );

            if ((int)$existing->row['total'] === 0) {
                $this->db->query(
                    "INSERT INTO `" . DB_PREFIX . "setting`
                     SET `code` = '" . $this->db->escape($setting['code']) . "', `key` = '" . $this->db->escape($setting['key']) . "', `value` = '" . $this->db->escape($setting['value']) . "', `store_id` = 0"
                );
            }
        }
    }

    /**
     * Проверка целостности после установки
     */
    private function checkIntegrity(): void
    {
        $expectedTables = ['iskra_log', 'iskra_event_log', 'iskra_queue', 'iskra_setting_extra', 'iskra_migration'];
        foreach ($expectedTables as $table) {
            $q = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . $table . "'");
            if ($q->num_rows === 0) {
                $this->logInfo("Integrity check FAILED: table $table missing");
                throw new \RuntimeException("Integrity check failed: table $table was not created");
            }
        }
        $this->logInfo('Integrity check passed');
    }

    /**
     * Создание бэкапа
     */
    private function createBackup(): string
    {
        $dir = DIR_STORAGE . 'backup/iskra_core/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = date('Ymd_His') . '_pre_install_backup.sql';
        $path = $dir . $filename;

        $tables = ['iskra_log', 'iskra_event_log', 'iskra_queue', 'iskra_setting_extra', 'iskra_migration'];
        $sql = "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            $fullTable = DB_PREFIX . $table;
            $q = $this->db->query("SHOW CREATE TABLE `$fullTable`");
            if ($q->num_rows > 0) {
                $create = $q->row['Create Table'];
                $sql .= "DROP TABLE IF EXISTS `$fullTable`;\n$create;\n\n";
                $rows = $this->db->query("SELECT * FROM `$fullTable`");
                if ($rows->num_rows > 0) {
                    $cols = array_keys($rows->row);
                    $sql .= "INSERT INTO `$fullTable` (`" . implode('`, `', $cols) . "`) VALUES\n";
                    $values = [];
                    foreach ($rows->rows as $row) {
                        $vals = [];
                        foreach ($row as $val) {
                            $vals[] = is_null($val) ? 'NULL' : "'" . $this->db->escape($val) . "'";
                        }
                        $values[] = "(" . implode(', ', $vals) . ")";
                    }
                    $sql .= implode(",\n", $values) . ";\n\n";
                }
            }
        }

        $settings = $this->db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE `code` = 'iskra_core'");
        if ($settings->num_rows > 0) {
            $sql .= "-- Settings\n";
            foreach ($settings->rows as $row) {
                $sql .= "DELETE FROM `" . DB_PREFIX . "setting` WHERE `setting_id` = " . (int)$row['setting_id'] . ";\n";
                $vals = [];
                foreach ($row as $k => $v) {
                    $vals[] = "`$k` = " . (is_null($v) ? 'NULL' : "'" . $this->db->escape($v) . "'");
                }
                $sql .= "INSERT INTO `" . DB_PREFIX . "setting` SET " . implode(', ', $vals) . ";\n";
            }
        }

        $sql .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
        file_put_contents($path, $sql);
        return $filename;
    }

    /**
     * Логирование события установки
     */
    private function logInfo(string $message): void
    {
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "iskra_log`
             SET `level` = 'info', `category` = 'system', `message` = '" . $this->db->escape($message) . "', `date_added` = NOW()"
        );
    }
}

// Запуск установки при include
if (defined('DIR_APPLICATION') && defined('DB_PREFIX')) {
    global $registry;
    if (isset($registry)) {
        $installer = new Installer($registry);
        $installer->install();
    }
}
