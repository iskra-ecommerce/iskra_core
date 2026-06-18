<?php
namespace Opencart\Admin\Controller\Extension\IskraCore\Module;

class IskraCore extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/iskra_core/module/iskra_core');
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/iskra_core/module/iskra_core', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/iskra_core/module/iskra_core.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$data['iskra_core_logging_enabled'] = (bool)$this->config->get('iskra_core_logging_enabled');
		$data['iskra_core_log_level'] = $this->config->get('iskra_core_log_level') ?: 'info';
		$data['iskra_core_log_retention_days'] = (int)$this->config->get('iskra_core_log_retention_days') ?: 30;
		$data['iskra_core_log_max_size_mb'] = (int)$this->config->get('iskra_core_log_max_size_mb') ?: 5120;
		$data['iskra_core_log_limit_strategy'] = $this->config->get('iskra_core_log_limit_strategy') ?: 'table_size';
		$data['iskra_core_log_max_rows'] = (int)$this->config->get('iskra_core_log_max_rows') ?: 500000;
		$data['iskra_core_log_min_disk_mb'] = (int)$this->config->get('iskra_core_log_min_disk_mb') ?: 1000;
		$data['iskra_core_event_bus_enabled'] = (bool)$this->config->get('iskra_core_event_bus_enabled');
		$data['iskra_core_queue_cron_enabled'] = (bool)$this->config->get('iskra_core_queue_cron_enabled');
		$data['iskra_core_backup_retention_days'] = (int)$this->config->get('iskra_core_backup_retention_days') ?: 30;

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/iskra_core/module/iskra_core', $data));
	}

	public function save(): void {
		$this->load->language('extension/iskra_core/module/iskra_core');
		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/iskra_core/module/iskra_core')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (empty($post['iskra_core_log_level']) || !in_array($post['iskra_core_log_level'], ['debug', 'info', 'warning', 'error', 'critical'])) {
			$json['error']['iskra_core_log_level'] = $this->language->get('error_log_level');
		}

		$retention = (int)($post['iskra_core_log_retention_days'] ?? 0);
		if ($retention < 1 || $retention > 365) {
			$json['error']['iskra_core_log_retention_days'] = $this->language->get('error_retention');
		}

		$maxSize = (int)($post['iskra_core_log_max_size_mb'] ?? 0);
		if ($maxSize < 100 || $maxSize > 102400) {
			$json['error']['iskra_core_log_max_size_mb'] = $this->language->get('error_max_size');
		}

		$maxRows = (int)($post['iskra_core_log_max_rows'] ?? 0);
		if ($maxRows < 1000 || $maxRows > 10000000) {
			$json['error']['iskra_core_log_max_rows'] = $this->language->get('error_max_rows');
		}

		$minDisk = (int)($post['iskra_core_log_min_disk_mb'] ?? 0);
		if ($minDisk < 100 || $minDisk > 50000) {
			$json['error']['iskra_core_log_min_disk_mb'] = $this->language->get('error_disk_space');
		}

		if (!$json) {
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('iskra_core', [
				'iskra_core_logging_enabled'       => isset($post['iskra_core_logging_enabled']) ? 1 : 0,
				'iskra_core_log_level'             => $post['iskra_core_log_level'],
				'iskra_core_log_retention_days'    => $retention,
				'iskra_core_log_max_size_mb'       => $maxSize,
				'iskra_core_log_limit_strategy'    => $post['iskra_core_log_limit_strategy'] ?? 'table_size',
				'iskra_core_log_max_rows'          => $maxRows,
				'iskra_core_log_min_disk_mb'       => $minDisk,
				'iskra_core_event_bus_enabled'     => isset($post['iskra_core_event_bus_enabled']) ? 1 : 0,
				'iskra_core_queue_cron_enabled'    => isset($post['iskra_core_queue_cron_enabled']) ? 1 : 0,
				'iskra_core_backup_retention_days' => (int)($post['iskra_core_backup_retention_days'] ?? 30),
			]);
			$json['success'] = $this->language->get('success_settings_saved');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// === AJAX: Backups ===
	public function listBackups(): void {
		$this->load->language('extension/iskra_core/module/iskra_core');
		$json = [];
		if (!$this->user->hasPermission('access', 'extension/iskra_core/module/iskra_core')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/iskra_core/module/iskra_core');
			$json['backups'] = $this->model_extension_iskra_core_module_iskra_core->getBackups();
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function createBackup(): void {
		$this->load->language('extension/iskra_core/module/iskra_core');
		$json = [];
		if (!$this->user->hasPermission('modify', 'extension/iskra_core/module/iskra_core')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/iskra_core/module/iskra_core');
			try {
				$name = $this->model_extension_iskra_core_module_iskra_core->createBackup();
				$json['success'] = $this->language->get('success_backup_created');
				$json['name'] = $name;
			} catch (\Throwable $e) {
				$json['error'] = $this->language->get('error_backup_failed') . ' ' . $e->getMessage();
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function deleteBackup(): void {
		$this->load->language('extension/iskra_core/module/iskra_core');
		$json = [];
		if (!$this->user->hasPermission('modify', 'extension/iskra_core/module/iskra_core')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/iskra_core/module/iskra_core');
			$name = $this->request->post['name'] ?? '';
			if ($this->model_extension_iskra_core_module_iskra_core->deleteBackup($name)) {
				$json['success'] = $this->language->get('success_backup_deleted');
			} else {
				$json['error'] = $this->language->get('error_delete_failed');
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function restoreBackup(): void {
		$this->load->language('extension/iskra_core/module/iskra_core');
		$json = [];
		if (!$this->user->hasPermission('modify', 'extension/iskra_core/module/iskra_core')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/iskra_core/module/iskra_core');
			$name = $this->request->post['name'] ?? '';
			if ($this->model_extension_iskra_core_module_iskra_core->restoreBackup($name)) {
				$json['success'] = $this->language->get('success_backup_restored');
			} else {
				$json['error'] = $this->language->get('error_restore_failed');
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// === AJAX: Migrations ===
	public function listMigrations(): void {
		$this->load->language('extension/iskra_core/module/iskra_core');
		$json = [];
		if (!$this->user->hasPermission('access', 'extension/iskra_core/module/iskra_core')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/iskra_core/module/iskra_core');
			$json['migrations'] = $this->model_extension_iskra_core_module_iskra_core->getMigrations();
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function runMigrations(): void {
		$this->load->language('extension/iskra_core/module/iskra_core');
		$json = [];
		if (!$this->user->hasPermission('modify', 'extension/iskra_core/module/iskra_core')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/iskra_core/module/iskra_core');
			require_once DIR_EXTENSION . 'iskra_core/install.php';
			try {
				$installer = new \Opencart\Extension\IskraCore\Installer($this->registry);
				$installer->install();
				$json['success'] = $this->language->get('success_migrations_run');
			} catch (\Throwable $e) {
				$json['error'] = $e->getMessage();
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function rollbackMigration(): void {
		$this->load->language('extension/iskra_core/module/iskra_core');
		$json = [];
		if (!$this->user->hasPermission('modify', 'extension/iskra_core/module/iskra_core')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/iskra_core/module/iskra_core');
			$last = $this->model_extension_iskra_core_module_iskra_core->getLastSuccessfulMigration();
			if (!$last) {
				$json['error'] = $this->language->get('error_no_migration');
			} else {
				$this->model_extension_iskra_core_module_iskra_core->updateMigrationStatus((int)$last['migration_id'], 'rolled_back');
				$json['success'] = $this->language->get('success_rollback');
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// === AJAX: Integrity ===
	public function checkIntegrity(): void {
		$this->load->language('extension/iskra_core/module/iskra_core');
		$json = [];
		if (!$this->user->hasPermission('access', 'extension/iskra_core/module/iskra_core')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/iskra_core/module/iskra_core');
			$tables = ['iskra_log', 'iskra_event_log', 'iskra_queue', 'iskra_setting_extra', 'iskra_migration'];
			$report = [];
			$allOk = true;
			foreach ($tables as $table) {
				$info = $this->model_extension_iskra_core_module_iskra_core->getTableInfo($table);
				if ($info) {
					$report[] = [
						'table'   => $table,
						'exists'  => true,
						'rows'    => (int)$info['table_rows'],
						'size'    => (int)$info['data_length'] + (int)$info['index_length'],
						'charset' => $info['table_collation'],
						'status'  => 'ok',
					];
				} else {
					$report[] = [
						'table'   => $table,
						'exists'  => false,
						'status'  => 'error',
					];
					$allOk = false;
				}
			}
			$json['report'] = $report;
			$json['all_ok'] = $allOk;
			$json['success'] = $allOk ? $this->language->get('success_integrity_ok') : $this->language->get('error_integrity_failed');
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
