<?php
/**
 * iskra-core — Logger (Система логирования)
 *
 * Логирование с уровнями, категориями, автоматической ротацией
 * по времени и размеру диска.
 *
 * Версия: 0.1.0
 */

declare(strict_types=1);

namespace Opencart\Extension\IskraCore\System\Library;

use Opencart\System\Engine\Registry as OpenCartRegistry;
use Opencart\System\Library\DB;

final class Logger
{
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_CRITICAL = 'critical';

    /** @var array<string, int> Приоритеты уровней */
    private const LEVELS = [
        self::LEVEL_DEBUG => 0,
        self::LEVEL_INFO => 1,
        self::LEVEL_WARNING => 2,
        self::LEVEL_ERROR => 3,
        self::LEVEL_CRITICAL => 4,
    ];

    private OpenCartRegistry $registry;
    private DB $db;
    private bool $loggingEnabled = true;
    private string $minLevel = self::LEVEL_INFO;
    private int $retentionDays = 30;
    private int $maxSizeMb = 5120;
    private string $limitStrategy = 'table_size';
    private int $maxRows = 500000;
    private int $minDiskMb = 1000;

    public function __construct(OpenCartRegistry $registry)
    {
        $this->registry = $registry;
        $this->db = $registry->get('db');

        // Загрузить настройки
        $this->loadSettings();
    }

    /**
     * Логирование отладочной информации
     */
    public function debug(string $category, string $message, array $context = []): void
    {
        $this->log(self::LEVEL_DEBUG, $category, $message, $context);
    }

    /**
     * Логирование информационного сообщения
     */
    public function info(string $category, string $message, array $context = []): void
    {
        $this->log(self::LEVEL_INFO, $category, $message, $context);
    }

    /**
     * Логирование предупреждения
     */
    public function warning(string $category, string $message, array $context = []): void
    {
        $this->log(self::LEVEL_WARNING, $category, $message, $context);
    }

    /**
     * Логирование ошибки
     */
    public function error(string $category, string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ERROR, $category, $message, $context);
    }

    /**
     * Логирование критической ошибки
     */
    public function critical(string $category, string $message, array $context = []): void
    {
        $this->log(self::LEVEL_CRITICAL, $category, $message, $context);
    }

    /**
     * Универсальный метод логирования
     */
    public function log(string $level, string $category, string $message, array $context = []): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        if (!isset(self::LEVELS[$level])) {
            throw new \InvalidArgumentException("Unknown log level: $level");
        }

        if (self::LEVELS[$level] < self::LEVELS[$this->minLevel]) {
            return;
        }

        $userId = $this->getCurrentUserId();
        $ipAddress = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "iskra_log`
             SET `level` = '" . $this->db->escape($level) . "',
                 `category` = '" . $this->db->escape($category) . "',
                 `user_id` = " . ($userId === null ? 'NULL' : (int)$userId) . ",
                 `ip_address` = " . ($ipAddress === null ? 'NULL' : "'" . $this->db->escape($ipAddress) . "'") . ",
                 `user_agent` = " . ($userAgent === null ? 'NULL' : "'" . $this->db->escape(mb_substr((string)$userAgent, 0, 255)) . "'") . ",
                 `message` = '" . $this->db->escape($message) . "',
                 `context` = " . ($context ? "'" . $this->db->escape(json_encode($context, JSON_UNESCAPED_UNICODE)) . "'" : 'NULL') . ",
                 `date_added` = NOW()"
        );

        // При критических событиях — уведомление админу (заглушка)
        if ($level === self::LEVEL_CRITICAL) {
            $this->notifyAdmin($category, $message, $context);
        }

        // Авто-проверка лимита после каждой записи
        $this->checkLimitAfterInsert();
    }

    /**
     * Проверка лимита после вставки лога (авто-ротация)
     */
    private function checkLimitAfterInsert(): void
    {
        switch ($this->limitStrategy) {
            case 'rows':
                $current = $this->getRowCount('iskra_log');
                if ($current > $this->maxRows) {
                    $toDelete = (int)($current - ($this->maxRows * 0.8));
                    $this->db->query("DELETE FROM `" . DB_PREFIX . "iskra_log` ORDER BY `date_added` ASC LIMIT " . max(1, $toDelete));
                }
                break;

            case 'table_size':
                $size = $this->getCurrentLogSize();
                if ($size > $this->maxSizeMb * 1024 * 1024) {
                    $this->rotateBySize($size);
                }
                break;

            case 'disk_space':
                $free = $this->getFreeDiskSpace();
                if ($free !== null && $free < $this->minDiskMb * 1024 * 1024) {
                    $this->rotateBySize($this->getCurrentLogSize());
                }
                break;
        }
    }

    /**
     * Свободное место на диске (bytes)
     */
    private function getFreeDiskSpace(): ?int
    {
        $path = DIR_STORAGE . 'logs/';
        if (!is_dir($path)) {
            $path = DIR_STORAGE;
        }
        $free = @disk_free_space($path);
        return $free !== false ? (int)$free : null;
    }

    /**
     * Получить логи с фильтрами
     *
     * @param array{
     *     level?: string,
     *     category?: string,
     *     user_id?: int,
     *     date_from?: string,
     *     date_to?: string,
     *     search?: string,
     *     start?: int,
     *     limit?: int
     * } $filter
     * @return array
     */
    public function getLogs(array $filter = []): array
    {
        $sql = "SELECT * FROM `" . DB_PREFIX . "iskra_log`";
        $where = [];
        $params = [];

        if (!empty($filter['level'])) {
            $where[] = "`level` = ?";
            $params[] = $filter['level'];
        }

        if (!empty($filter['category'])) {
            $where[] = "`category` = ?";
            $params[] = $filter['category'];
        }

        if (!empty($filter['user_id'])) {
            $where[] = "`user_id` = ?";
            $params[] = (int)$filter['user_id'];
        }

        if (!empty($filter['date_from'])) {
            $where[] = "`date_added` >= ?";
            $params[] = $filter['date_from'];
        }

        if (!empty($filter['date_to'])) {
            $where[] = "`date_added` <= ?";
            $params[] = $filter['date_to'];
        }

        if (!empty($filter['search'])) {
            $where[] = "`message` LIKE ?";
            $params[] = '%' . $filter['search'] . '%';
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY `date_added` DESC";

        if (isset($filter['start']) || isset($filter['limit'])) {
            $sql .= " LIMIT " . (int)($filter['start'] ?? 0) . ", " . (int)($filter['limit'] ?? 50);
        }

        $query = $this->db->query($sql, $params);
        return $query->rows;
    }

    /**
     * Ротация логов (вызывается из cron)
     *
     * Удаляет логи старше retentionDays, если общий размер превышает maxSizeMb.
     */
    public function rotate(): array
    {
        $deleted = 0;

        // 1. Удаление по времени
        $deleted += $this->rotateByTime();

        // 2. Проверка размера
        $currentSize = $this->getCurrentLogSize();
        if ($currentSize > $this->maxSizeMb * 1024 * 1024) {
            $deleted += $this->rotateBySize($currentSize);
        }

        return [
            'deleted' => $deleted,
            'size_mb' => round($this->getCurrentLogSize() / 1024 / 1024, 2),
        ];
    }

    /**
     * Удаление логов по времени
     */
    private function rotateByTime(): int
    {
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "iskra_log` WHERE `date_added` < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$this->retentionDays]
        );

        return $this->db->countAffected();
    }

    /**
     * Удаление логов по размеру (самые старые)
     */
    private function rotateBySize(int $currentSize): int
    {
        // Целевой размер: 80% от максимума
        $targetSize = (int)($this->maxSizeMb * 0.8 * 1024 * 1024);
        $toDelete = $currentSize - $targetSize;

        if ($toDelete <= 0) {
            return 0;
        }

        // Удаляем начиная с самых старых
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "iskra_log`
             ORDER BY `date_added` ASC
             LIMIT " . max(1, (int)($toDelete / 1000))  // Примерная оценка: 1KB на запись
        );

        return $this->db->countAffected();
    }

    /**
     * Получить текущий размер таблицы логов
     */
    private function getCurrentLogSize(): int
    {
        $query = $this->db->query(
            "SELECT SUM(data_length + index_length) AS size
             FROM information_schema.TABLES
             WHERE table_schema = DATABASE() AND table_name = '" . DB_PREFIX . "iskra_log'"
        );

        return (int)($query->row['size'] ?? 0);
    }

    /**
     * Загрузка настроек из БД
     */
    private function loadSettings(): void
    {
        $query = $this->db->query(
            "SELECT `key`, `value` FROM `" . DB_PREFIX . "setting`
             WHERE `code` = 'iskra_core' AND `store_id` = 0"
        );

        foreach ($query->rows as $row) {
            switch ($row['key']) {
                case 'iskra_core_logging_enabled':
                    $this->loggingEnabled = (bool)$row['value'];
                    break;
                case 'iskra_core_log_level':
                    $this->minLevel = $row['value'];
                    break;
                case 'iskra_core_log_retention_days':
                    $this->retentionDays = (int)$row['value'];
                    break;
                case 'iskra_core_log_max_size_mb':
                    $this->maxSizeMb = (int)$row['value'];
                    break;
                case 'iskra_core_log_limit_strategy':
                    $this->limitStrategy = $row['value'] ?: 'table_size';
                    break;
                case 'iskra_core_log_max_rows':
                    $this->maxRows = (int)$row['value'];
                    break;
                case 'iskra_core_log_min_disk_mb':
                    $this->minDiskMb = (int)$row['value'];
                    break;
            }
        }
    }

    /**
     * Получить количество строк в таблице
     */
    private function getRowCount(string $table): int
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . $table . "`");
        return (int)$query->row['total'];
    }

    /**
     * Получить ID текущего пользователя
     */
    private function getCurrentUserId(): ?int
    {
        try {
            $user = $this->registry->get('user');
            if ($user !== null) {
                $id = $user->getId();
                if ($id !== null) {
                    return (int)$id;
                }
            }
        } catch (\Throwable $e) {
            // Реестр может не содержать 'user' в CLI-режиме
        }
        return null;
    }

    /**
     * Получить IP клиента
     */
    private function getClientIp(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Уведомление админа о критическом событии
     */
    private function notifyAdmin(string $category, string $message, array $context): void
    {
        // Заглушка: в Фазе 2 будет отправка email/Telegram через iskra-notify
        // Сейчас — только запись в лог
    }
}
