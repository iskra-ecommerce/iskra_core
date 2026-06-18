<?php
/**
 * iskra-core — удаление
 * Документация: https://github.com/iskra-ecommerce/iskra-core
 *
 * Зеркально отражает install.php.
 * ВАЖНО: при удалении теряются все логи. Сделайте бэкап заранее.
 */

declare(strict_types=1);

namespace Opencart\Extension\IskraCore;

use Opencart\System\Engine\Registry;
use Opencart\System\Library\DB;

final class Uninstaller
{
    private Registry $registry;
    private DB $db;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
        $this->db = $registry->get('db');
    }

    /**
     * Главный метод удаления
     */
    public function uninstall(): void
    {
        $this->autoBackupBeforeUninstall();
        $this->removeSettings();
        $this->removePermissions();
        $this->dropTables();
    }

    /**
     * Авто-бэкап перед удалением
     */
    private function autoBackupBeforeUninstall(): void
    {
        $dir = DIR_STORAGE . 'backup/iskra_core/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = date('Ymd_His') . '_pre_uninstall_backup.sql';
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
    }

    /**
     * Удаление настроек iskra_core
     */
    private function removeSettings(): void
    {
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'iskra_core'"
        );

        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "iskra_setting_extra`"
        );
    }

    /**
     * Удаление прав iskra_core из групп пользователей
     */
    private function removePermissions(): void
    {
        $query = $this->db->query("SELECT `user_group_id`, `permission` FROM `" . DB_PREFIX . "user_group`");

        foreach ($query->rows as $group) {
            $permissions = json_decode($group['permission'] ?? '{}', true) ?? [];

            unset($permissions['iskra_core']);
            unset($permissions['iskra_log']);

            $this->db->query(
                "UPDATE `" . DB_PREFIX . "user_group`
                 SET `permission` = '" . $this->db->escape(json_encode($permissions, JSON_UNESCAPED_UNICODE)) . "'
                 WHERE `user_group_id` = " . (int)$group['user_group_id']
            );
        }
    }

    /**
     * Удаление таблиц iskra_*
     *
     * ВАЖНО: убедитесь, что другие расширения Искра не используют эти таблицы.
     * Сначала удалите зависимые расширения, потом iskra-core.
     */
    private function dropTables(): void
    {
        $tables = [
            DB_PREFIX . 'iskra_log',
            DB_PREFIX . 'iskra_event_log',
            DB_PREFIX . 'iskra_queue',
            DB_PREFIX . 'iskra_setting_extra',
            DB_PREFIX . 'iskra_migration',
        ];

        foreach ($tables as $table) {
            $this->db->query("DROP TABLE IF EXISTS `$table`");
        }
    }
}

// Запуск удаления при include
if (defined('DIR_APPLICATION') && defined('DB_PREFIX')) {
    global $registry;
    if (isset($registry)) {
        $uninstaller = new Uninstaller($registry);
        $uninstaller->uninstall();
    }
}
