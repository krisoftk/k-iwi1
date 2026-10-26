<?php
class ControllerAdminExtensionReport extends Controller {
	private $error = array();

	public function index() {
		$this->language->load('admin/extension/report');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('admin/extension/report');

		$this->getList();
	}

	public function insert() {
		$this->prepareForm();

		if ($this->isPost() && $this->validateForm()) {
			$this->model_admin_extension_report->addReport($this->request->post);

			$this->finishAndRedirect();
		}

		$this->getForm();
	}

	public function update() {
		$this->prepareForm();

		if ($this->isPost() && $this->validateForm()) {
			$this->model_admin_extension_report->editReport($this->request->get['report_id'], $this->request->post);

			$this->finishAndRedirect();
		}

		$this->getForm();
	}

	public function delete() {
		$this->language->load('admin/extension/report');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('admin/extension/report');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $reportId) {
				$this->model_admin_extension_report->deleteReport($reportId);
			}

			$this->finishAndRedirect();
		}

		$this->getList();
	}

	private function prepareForm() {
		$this->language->load('admin/extension/report');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('admin/extension/report');
		$this->load->model('admin/extension/prog');
	}

	private function isPost() {
		return $this->request->server['REQUEST_METHOD'] === 'POST';
	}

	private function finishAndRedirect() {
		$this->session->data['success'] = $this->language->get('text_success');

		$this->redirect($this->url->link('admin/extension/report', 'token=' . $this->session->data['token'] . $this->listQueryString(), 'SSL'));
	}

	private function listQueryString() {
		$query = '';

		foreach (array('sort', 'order', 'page') as $key) {
			if (isset($this->request->get[$key])) {
				$query .= '&' . $key . '=' . $this->request->get[$key];
			}
		}

		return $query;
	}

	protected function getList() {
		$this->config->load('admin');

		$sort  = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'NAME';
		$order = isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC';
		$page  = isset($this->request->get['page']) ? $this->request->get['page'] : 1;

		$query = $this->listQueryString();

		$this->data['breadcrumbs'] = array(
			array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('admin/common/home', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => false,
			),
			array(
				'text'      => $this->language->get('heading_title'),
				'href'      => $this->url->link('admin/extension/report', 'token=' . $this->session->data['token'] . $query, 'SSL'),
				'separator' => ' :: ',
			),
		);

		$this->data['insert'] = $this->url->link('admin/extension/report/insert', 'token=' . $this->session->data['token'] . $query, 'SSL');
		$this->data['delete'] = $this->url->link('admin/extension/report/delete', 'token=' . $this->session->data['token'] . $query, 'SSL');

		$filter = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('admin.list_limit'),
			'limit' => $this->config->get('admin.list_limit'),
		);

		$total   = $this->model_admin_extension_report->getTotalReports();
		$results = $this->model_admin_extension_report->getReports($filter);

		$this->data['reports'] = array();

		foreach ($results as $result) {
			$this->data['reports'][] = array(
				'report_id'   => $result['ID'],
				'prog_id'     => $result['PROG_ID'],
				'name'        => $result['NAME'],
				'description' => $result['DESCRIPTION'],
				'selected'    => isset($this->request->post['selected']) && in_array($result['ID'], $this->request->post['selected']),
				'action'      => array(
					array(
						'text' => $this->language->get('text_edit'),
						'href' => $this->url->link('admin/extension/report/update', 'token=' . $this->session->data['token'] . '&report_id=' . $result['ID'] . $query, 'SSL'),
					),
				),
			);
		}

		foreach (array('heading_title', 'text_no_results', 'column_prog', 'column_name', 'column_description', 'column_action', 'button_insert', 'button_delete') as $key) {
			$this->data[$key] = $this->language->get($key);
		}

		$this->data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}

		$toggledOrder = ($order === 'ASC') ? 'DESC' : 'ASC';
		$pageSuffix   = isset($this->request->get['page']) ? '&page=' . $this->request->get['page'] : '';

		foreach (array('prog_id' => 'PROG_ID', 'name' => 'NAME') as $key => $column) {
			$this->data['sort_' . $key] = $this->url->link('admin/extension/report', 'token=' . $this->session->data['token'] . '&sort=' . $column . '&order=' . $toggledOrder . $pageSuffix, 'SSL');
		}

		$pagination = new Pagination();
		$pagination->total = $total;
		$pagination->page  = $page;
		$pagination->limit = $this->config->get('admin.list_limit');
		$pagination->text  = $this->language->get('text_pagination');
		$pagination->url   = $this->url->link('admin/extension/report', 'token=' . $this->session->data['token'] . $this->listQueryString() . '&page={page}', 'SSL');

		$this->data['pagination'] = $pagination->render();
		$this->data['sort']       = $sort;
		$this->data['order']      = $order;

		$this->template = 'admin/view/extension/report_list.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}

	protected function getForm() {
		foreach (array('heading_title', 'text_enabled', 'text_disabled', 'entry_prog', 'entry_name', 'entry_description', 'entry_url', 'button_save', 'button_cancel', 'button_remove') as $key) {
			$this->data[$key] = $this->language->get($key);
		}

		foreach (array('warning', 'prog', 'name', 'url') as $field) {
			$this->data['error_' . $field] = isset($this->error[$field]) ? $this->error[$field] : '';
		}

		$query = $this->listQueryString();

		$this->data['breadcrumbs'] = array(
			array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('admin/common/home', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => false,
			),
			array(
				'text'      => $this->language->get('heading_title'),
				'href'      => $this->url->link('admin/extension/report', 'token=' . $this->session->data['token'] . $query, 'SSL'),
				'separator' => ' :: ',
			),
		);

		$reportId = isset($this->request->get['report_id']) ? $this->request->get['report_id'] : null;

		$this->data['action'] = $reportId
			? $this->url->link('admin/extension/report/update', 'token=' . $this->session->data['token'] . '&report_id=' . $reportId . $query, 'SSL')
			: $this->url->link('admin/extension/report/insert', 'token=' . $this->session->data['token'] . $query, 'SSL');

		$this->data['cancel'] = $this->url->link('admin/extension/report', 'token=' . $this->session->data['token'] . $query, 'SSL');

		$reportInfo = ($reportId && !$this->isPost()) ? $this->model_admin_extension_report->getReport($reportId) : null;

		$this->data['progs'] = $this->model_admin_extension_prog->getProgs();

		$fields = array('prog_id' => 'PROG_ID', 'name' => 'NAME', 'description' => 'DESCRIPTION', 'url' => 'URL');

		foreach ($fields as $field => $column) {
			if (isset($this->request->post[$field])) {
				$this->data[$field] = $this->request->post[$field];
			} elseif ($reportInfo) {
				$this->data[$field] = $reportInfo[$column];
			} else {
				$this->data[$field] = ($field === 'prog_id') ? 0 : '';
			}
		}

		$this->template = 'admin/view/extension/report_form.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}

	protected function validateForm() {
		$length = utf8_strlen($this->request->post['name']);

		if ($length < 3 || $length > 20) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (empty($this->request->post['prog_id'])) {
			$this->error['prog'] = $this->language->get('error_prog');
		}

		if (empty($this->request->post['url'])) {
			$this->error['url'] = $this->language->get('error_url');
		}

		return empty($this->error);
	}

	protected function validateDelete() {
		return empty($this->error);
	}
}
?>
