<?php
namespace Opencart\Admin\Model\Extension\IskraCore\Module;

class IskraCore extends \Opencart\System\Engine\Model {

	// === Logs ===

	public function getLogs(array $filter = []): array {
		$sql = "SELECT l.*, u.username AS user_name
				FROM `" . DB_PREFIX . "iskra_log` l
				LEFT JOIN `" . DB_PREFIX . "user` u ON l.user_id = u.user_id";
		$where = [];

		if (!empty($filter['level'])) {
			$where[] = "l.`level` = '" . $this->db->escape($filter['level']) . "'";
		}
		if (!empty($filter['category'])) {
			$where[] = "l.`category` = '" . $this->db->escape($filter['category']) . "'";
		}
		if (!empty($filter['user_id'])) {
			$where[] = "l.`user_id` = " . (int)$filter['user_id'];
		}
		if (!empty($filter['date_from'])) {
			$where[] = "l.`date_added` >= '" . $this->db->escape($filter['date_from']) . "'";
		}
		if (!empty($filter['date_to'])) {
			$where[] = "l.`date_added` <= '" . $this->db->escape($filter['date_to'] . ' 23:59:59') . "'";
		}
		if (!empty($filter['search'])) {
			$where[] = "l.`message` LIKE '%" . $this->db->escape($filter['search']) . "%'";
		}

		if ($where) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}

		$sort = $filter['sort'] ?? 'l.date_added';
		$order = $filter['order'] ?? 'DESC';
		$allowedSort = ['l.date_added', 'l.level', 'l.category', 'l.user_id'];
		if (!in_array($sort, $allowedSort)) {
			$sort = 'l.date_added';
		}
		$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
		$sql .= " ORDER BY " . $sort . " " . $order;

		$start = (int)($filter['start'] ?? 0);
		$limit = (int)($filter['limit'] ?? 20);
		$sql .= " LIMIT " . $start . ", " . $limit;

		$query = $this->db->query($sql);
		return $query->rows;
	}

	public function getTotalLogs(array $filter = []): int {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "iskra_log`";
		$where = [];

		if (!empty($filter['level'])) {
			$where[] = "`level` = '" . $this->db->escape($filter['level']) . "'";
		}
		if (!empty($filter['category'])) {
			$where[] = "`category` = '" . $this->db->escape($filter['category']) . "'";
		}
		if (!empty($filter['user_id'])) {
			$where[] = "`user_id` = " . (int)$filter['user_id'];
		}
		if (!empty($filter['date_from'])) {
			$where[] = "`date_added` >= '" . $this->db->escape($filter['date_from']) . "'";
		}
		if (!empty($filter['date_to'])) {
			$where[] = "`date_added` <= '" . $this->db->escape($filter['date_to'] . ' 23:59:59') . "'";
		}
		if (!empty($filter['search'])) {
			$where[] = "`message` LIKE '%" . $this->db->escape($filter['search']) . "%'";
		}

		if ($where) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}

		$query = $this->db->query($sql);
		return (int)$query->row['total'];
	}

	public function deleteLogs(array $logIds): void {
		if (!$logIds) {
			return;
		}
		$ids = implode(',', array_map('intval', $logIds));
		$this->db->query("DELETE FROM `" . DB_PREFIX . "iskra_log` WHERE `log_id` IN (" . $ids . ")");
	}

	public function getCategories(): array {
		$query = $this->db->query("SELECT DISTINCT `category` FROM `" . DB_PREFIX . "iskra_log` ORDER BY `category`");
		return array_column($query->rows, 'category');
	}

	public function getLogStats(): array {
		$query = $this->db->query("SELECT COUNT(*) AS total, `level` FROM `" . DB_PREFIX . "iskra_log` GROUP BY `level`");
		$stats = ['total' => 0, 'by_level' => []];
		foreach ($query->rows as $row) {
			$stats['total'] += (int)$row['total'];
			$stats['by_level'][$row['level']] = (int)$row['total'];
		}
		return $stats;
	}

	public function getTableSize(string $table): int {
		$query = $this->db->query(
			"SELECT (data_length + index_length) AS size
			 FROM information_schema.TABLES
			 WHERE table_schema = DATABASE() AND table_name = '" . $this->db->escape(DB_PREFIX . $table) . "'"
		);
		return (int)($query->row['size'] ?? 0);
	}

	public function getRowCount(string $table): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . $table . "`");
		return (int)$query->row['total'];
	}

	// === Migrations ===

	public function createMigrationTable(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "iskra_migration` (
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
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	}

	public function getMigrations(): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "iskra_migration` ORDER BY `version`, `name`");
		return $query->rows;
	}

	public function getMigration(string $version, string $name): ?array {
		$query = $this->db->query(
			"SELECT * FROM `" . DB_PREFIX . "iskra_migration`
			 WHERE `version` = '" . $this->db->escape($version) . "' AND `name` = '" . $this->db->escape($name) . "'"
		);
		return $query->row ?: null;
	}

	public function addMigration(array $data): void {
		$this->db->query(
			"INSERT INTO `" . DB_PREFIX . "iskra_migration`
			 (`version`, `name`, `up_sql`, `down_sql`, `checksum`, `status`, `applied_at`)
			 VALUES ('" . $this->db->escape($data['version']) . "', '" . $this->db->escape($data['name']) . "', '" . $this->db->escape($data['up_sql']) . "', '" . $this->db->escape($data['down_sql']) . "', '" . $this->db->escape($data['checksum'] ?? '') . "', '" . $this->db->escape($data['status'] ?? 'pending') . "', " . ($data['applied_at'] ? "'" . $this->db->escape($data['applied_at']) . "'" : 'NULL') . ")
			 ON DUPLICATE KEY UPDATE
			 `up_sql` = VALUES(`up_sql`), `down_sql` = VALUES(`down_sql`),
			 `checksum` = VALUES(`checksum`)"
		);
	}

	public function updateMigrationStatus(int $migrationId, string $status, ?string $error = null): void {
		$this->db->query(
			"UPDATE `" . DB_PREFIX . "iskra_migration`
			 SET `status` = '" . $this->db->escape($status) . "', `error` = " . ($error !== null ? "'" . $this->db->escape($error) . "'" : 'NULL') . ", `applied_at` = " . ($status === 'success' ? 'NOW()' : '`applied_at`') . ", `rolled_back_at` = " . ($status === 'rolled_back' ? 'NOW()' : '`rolled_back_at`') . "
			 WHERE `migration_id` = " . (int)$migrationId
		);
	}

	public function getLastSuccessfulMigration(): ?array {
		$query = $this->db->query(
			"SELECT * FROM `" . DB_PREFIX . "iskra_migration`
			 WHERE `status` = 'success' ORDER BY `applied_at` DESC LIMIT 1"
		);
		return $query->row ?: null;
	}

	// === Integrity ===

	public function getTableInfo(string $table): array {
		$query = $this->db->query(
			"SELECT table_name, table_rows, data_length, index_length, table_collation
			 FROM information_schema.TABLES
			 WHERE table_schema = DATABASE() AND table_name = '" . $this->db->escape(DB_PREFIX . $table) . "'"
		);
		return $query->row ?: [];
	}

	public function tableExists(string $table): bool {
		$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . $table) . "'");
		return $query->num_rows > 0;
	}

	public function checkTableColumns(string $table, array $expectedColumns): array {
		$query = $this->db->query("DESCRIBE `" . DB_PREFIX . $table . "`");
		$existing = array_column($query->rows, 'Field');
		$missing = array_diff($expectedColumns, $existing);
		return [
			'exists' => empty($missing),
			'missing' => $missing,
			'existing' => $existing,
		];
	}

	// === Backups ===

	public function getBackupDir(): string {
		return DIR_STORAGE . 'backup/iskra_core/';
	}

	public function ensureBackupDir(): void {
		$dir = $this->getBackupDir();
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
	}

	public function createBackup(): string {
		$this->ensureBackupDir();
		$filename = date('Ymd_His') . '_backup.sql';
		$path = $this->getBackupDir() . $filename;

		$tables = ['iskra_log', 'iskra_event_log', 'iskra_queue', 'iskra_setting_extra', 'iskra_migration'];
		$sql = "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

		foreach ($tables as $table) {
			if (!$this->tableExists($table)) {
				continue;
			}
			$fullTable = DB_PREFIX . $table;
			$sql .= "-- Table: $fullTable\n";
			$query = $this->db->query("SHOW CREATE TABLE `$fullTable`");
			$create = $query->row['Create Table'] ?? '';
			if ($create) {
				$sql .= "DROP TABLE IF EXISTS `$fullTable`;\n$create;\n\n";
			}

			$rows = $this->db->query("SELECT * FROM `$fullTable`");
			if ($rows->num_rows > 0) {
				$columns = array_keys($rows->row);
				$sql .= "INSERT INTO `$fullTable` (`" . implode('`, `', $columns) . "`) VALUES\n";
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

		// Backup settings
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

	public function getBackups(): array {
		$dir = $this->getBackupDir();
		$backups = [];
		if (!is_dir($dir)) {
			return $backups;
		}
		$files = glob($dir . '*.sql');
		foreach ($files as $file) {
			$backups[] = [
				'name' => basename($file),
				'size' => filesize($file),
				'date' => filemtime($file),
				'path' => $file,
			];
		}
		usort($backups, fn($a, $b) => $b['date'] <=> $a['date']);
		return $backups;
	}

	public function deleteBackup(string $filename): bool {
		$path = $this->getBackupDir() . basename($filename);
		if (file_exists($path) && str_ends_with($path, '.sql')) {
			return unlink($path);
		}
		return false;
	}

	public function restoreBackup(string $filename): bool {
		$path = $this->getBackupDir() . basename($filename);
		if (!file_exists($path)) {
			return false;
		}
		$sql = file_get_contents($path);
		if (!$sql) {
			return false;
		}
		// Execute in transaction-like manner
		$this->db->query("SET FOREIGN_KEY_CHECKS = 0");
		$statements = array_filter(array_map('trim', explode(';', $sql)));
		foreach ($statements as $stmt) {
			if ($stmt && !str_starts_with($stmt, '--') && !str_starts_with($stmt, 'SET')) {
				try {
					$this->db->query($stmt);
				} catch (\Exception $e) {
					$this->db->query("SET FOREIGN_KEY_CHECKS = 1");
					return false;
				}
			}
		}
		$this->db->query("SET FOREIGN_KEY_CHECKS = 1");
		return true;
	}

	public function cleanupOldBackups(int $retentionDays): int {
		$dir = $this->getBackupDir();
		if (!is_dir($dir)) {
			return 0;
		}
		$cutoff = time() - ($retentionDays * 86400);
		$deleted = 0;
		$files = glob($dir . '*.sql');
		foreach ($files as $file) {
			if (filemtime($file) < $cutoff) {
				unlink($file);
				$deleted++;
			}
		}
		return $deleted;
	}
}
