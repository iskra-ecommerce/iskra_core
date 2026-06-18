<?php
namespace Opencart\Admin\Controller\Extension\IskraCore\Module;

class IskraCoreLog extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/iskra_core/module/iskra_core');
		$this->document->setTitle($this->language->get('heading_title') . ' — ' . $this->language->get('tab_logs'));

		$this->load->model('extension/iskra_core/module/iskra_core');

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
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('tab_logs'),
			'href' => $this->url->link('extension/iskra_core/module/iskra_core_log', 'user_token=' . $this->session->data['user_token'])
		];

		// Filters
		$filter = [
			'level'     => $this->request->get['level'] ?? '',
			'category'  => $this->request->get['category'] ?? '',
			'user_id'   => $this->request->get['user_id'] ?? '',
			'date_from' => $this->request->get['date_from'] ?? '',
			'date_to'   => $this->request->get['date_to'] ?? '',
			'search'    => $this->request->get['search'] ?? '',
			'sort'      => $this->request->get['sort'] ?? 'l.date_added',
			'order'     => $this->request->get['order'] ?? 'DESC',
		];

		$page = (int)($this->request->get['page'] ?? 1);
		$limit = 20;
		$start = ($page - 1) * $limit;

		$filter['start'] = $start;
		$filter['limit'] = $limit;

		$data['logs'] = $this->model_extension_iskra_core_module_iskra_core->getLogs($filter);
		$total = $this->model_extension_iskra_core_module_iskra_core->getTotalLogs($filter);

		$data['categories'] = $this->model_extension_iskra_core_module_iskra_core->getCategories();
		$data['stats'] = $this->model_extension_iskra_core_module_iskra_core->getLogStats();

		$data['filter'] = $filter;
		$data['page'] = $page;
		$data['limit'] = $limit;
		$data['total'] = $total;
		$data['pages'] = (int)ceil($total / $limit);
		$data['pagination'] = $this->getPagination($page, $data['pages'], $filter);

		$data['delete'] = $this->url->link('extension/iskra_core/module/iskra_core_log.delete', 'user_token=' . $this->session->data['user_token']);
		$data['rotate'] = $this->url->link('extension/iskra_core/module/iskra_core_log.rotate', 'user_token=' . $this->session->data['user_token']);
		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/iskra_core/module/iskra_core_log', $data));
	}

	public function delete(): void {
		$this->load->language('extension/iskra_core/module/iskra_core');
		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/iskra_core/module/iskra_core_log')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$selected = $this->request->post['selected'] ?? [];
		if (!$selected) {
			$json['error'] = $this->language->get('error_delete_failed');
		}

		if (!$json) {
			$this->load->model('extension/iskra_core/module/iskra_core');
			$this->model_extension_iskra_core_module_iskra_core->deleteLogs(array_map('intval', $selected));
			$json['success'] = $this->language->get('success_logs_deleted');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function rotate(): void {
		$this->load->language('extension/iskra_core/module/iskra_core');
		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/iskra_core/module/iskra_core_log')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			require_once DIR_EXTENSION . 'iskra_core/system/library/iskra/logger.php';
			$logger = new \Opencart\Extension\IskraCore\System\Library\Logger($this->registry);
			$result = $logger->rotate();
			$json['success'] = sprintf($this->language->get('success_logs_rotated'), $result['deleted']);
			$json['size_mb'] = $result['size_mb'];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function getPagination(int $page, int $totalPages, array $filter): string {
		$url = '&user_token=' . $this->session->data['user_token'];
		foreach (['level', 'category', 'user_id', 'date_from', 'date_to', 'search', 'sort', 'order'] as $key) {
			if (!empty($filter[$key])) {
				$url .= '&' . $key . '=' . urlencode($filter[$key]);
			}
		}
		$pagination = [];
		$pagination['total'] = $totalPages * 20;
		$pagination['page'] = $page;
		$pagination['limit'] = 20;
		$pagination['url'] = $this->url->link('extension/iskra_core/module/iskra_core_log', $url . '&page={page}');
		return $this->load->controller('common/pagination', $pagination);
	}
}
