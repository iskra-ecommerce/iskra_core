<?php
/**
 * iskra-core — Event Bus (Шина событий)
 *
 * Асинхронная шина событий для связи между расширениями.
 * Расширения могут бросать события и подписываться на них.
 *
 * Версия: 0.1.0
 */

declare(strict_types=1);

namespace Opencart\Extension\IskraCore\System\Library;

use Opencart\System\Engine\Registry as OpenCartRegistry;
use Opencart\System\Library\DB;

final class EventBus
{
    private OpenCartRegistry $registry;
    private DB $db;
    /** @var array<string, array<int, array{handler: callable|array, priority: int}>> */
    private array $subscribers = [];

    public function __construct(OpenCartRegistry $registry)
    {
        $this->registry = $registry;
        $this->db = $registry->get('db');
    }

    /**
     * Подписаться на событие
     *
     * @param string $eventCode Код события (например, 'iskra_order_paid')
     * @param callable|array $handler Обработчик
     * @param int $priority Приоритет (выше = раньше вызывается)
     */
    public function subscribe(string $eventCode, callable|array $handler, int $priority = 10): void
    {
        if (!isset($this->subscribers[$eventCode])) {
            $this->subscribers[$eventCode] = [];
        }

        $this->subscribers[$eventCode][] = [
            'handler' => $handler,
            'priority' => $priority,
        ];

        // Сортировка по приоритету (по убыванию)
        usort($this->subscribers[$eventCode], fn($a, $b) => $b['priority'] <=> $a['priority']);
    }

    /**
     * Опубликовать событие (синхронно)
     *
     * @param string $eventCode Код события
     * @param array $payload Данные события
     * @return array Результаты всех обработчиков
     */
    public function publish(string $eventCode, array $payload = []): array
    {
        $this->logEvent($eventCode, $payload);

        $results = [];

        if (isset($this->subscribers[$eventCode])) {
            foreach ($this->subscribers[$eventCode] as $subscriber) {
                try {
                    $result = ($subscriber['handler'])($payload, $eventCode);
                    $results[] = $result;
                } catch (\Throwable $e) {
                    $this->logError(
                        'event',
                        "Ошибка в обработчике события '$eventCode': " . $e->getMessage(),
                        ['exception' => $e->getTraceAsString()]
                    );
                }
            }
        }

        return $results;
    }

    /**
     * Поставить событие в очередь (асинхронно)
     *
     * @param string $eventCode Код события
     * @param array $payload Данные события
     * @param int $priority Приоритет (1-10, выше = раньше)
     */
    public function queue(string $eventCode, array $payload = [], int $priority = 5): void
    {
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "iskra_queue`
             SET `code` = ?, `payload` = ?, `priority` = ?, `status` = 'pending', `date_added` = NOW()",
            [
                $eventCode,
                json_encode($payload, JSON_UNESCAPED_UNICODE),
                $priority,
            ]
        );
    }

    /**
     * Обработать очередь (вызывается из cron)
     *
     * @param int $limit Максимум задач за раз
     * @return int Количество обработанных задач
     */
    public function processQueue(int $limit = 50): int
    {
        $query = $this->db->query(
            "SELECT `queue_id`, `code`, `payload`
             FROM `" . DB_PREFIX . "iskra_queue`
             WHERE `status` = 'pending' AND (`scheduled_at` IS NULL OR `scheduled_at` <= NOW())
             ORDER BY `priority` DESC, `date_added` ASC
             LIMIT " . (int)$limit
        );

        $processed = 0;

        foreach ($query->rows as $task) {
            $payload = json_decode($task['payload'], true) ?? [];

            $this->db->query(
                "UPDATE `" . DB_PREFIX . "iskra_queue`
                 SET `status` = 'processing', `attempts` = `attempts` + 1
                 WHERE `queue_id` = ?",
                [(int)$task['queue_id']]
            );

            try {
                $this->publish($task['code'], $payload);

                $this->db->query(
                    "UPDATE `" . DB_PREFIX . "iskra_queue`
                     SET `status` = 'done', `date_processed` = NOW()
                     WHERE `queue_id` = ?",
                    [(int)$task['queue_id']]
                );

                $processed++;
            } catch (\Throwable $e) {
                $attempts = $this->db->query(
                    "SELECT `attempts` FROM `" . DB_PREFIX . "iskra_queue` WHERE `queue_id` = ?",
                    [(int)$task['queue_id']]
                )->row;

                $maxAttempts = 3;
                $newStatus = (int)$attempts['attempts'] >= $maxAttempts ? 'failed' : 'pending';

                $this->db->query(
                    "UPDATE `" . DB_PREFIX . "iskra_queue`
                     SET `status` = ?, `error` = ?
                     WHERE `queue_id` = ?",
                    [$newStatus, $e->getMessage(), (int)$task['queue_id']]
                );
            }
        }

        return $processed;
    }

    /**
     * Логирование события
     */
    private function logEvent(string $code, array $payload): void
    {
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "iskra_event_log`
             SET `code` = ?, `payload` = ?, `date_added` = NOW()",
            [$code, json_encode($payload, JSON_UNESCAPED_UNICODE)]
        );
    }

    /**
     * Логирование ошибки
     */
    private function logError(string $category, string $message, array $context = []): void
    {
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "iskra_log`
             SET `level` = 'error', `category` = ?, `message` = ?, `context` = ?, `date_added` = NOW()",
            [$category, $message, json_encode($context, JSON_UNESCAPED_UNICODE)]
        );
    }
}
