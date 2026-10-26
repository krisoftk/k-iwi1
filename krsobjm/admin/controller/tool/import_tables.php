<?php
class ControllerAdminToolImportTables extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('admin/tool/import_tables');

		$this->load->model('admin/tool/import_tables');
		$this->load->model('admin/tool/generator');

		$this->getForm();
	}

	public function insert_database() {
		$this->prepare();

		if ($this->isPost() && $this->validateFormDatabase()) {
			$this->model_admin_tool_import_tables->addDatabase($this->request->post);

			$this->finishAndRedirect();
		}

		$this->getForm();
	}

	public function insert_table() {
		$this->prepare();

		if ($this->isPost() && $this->validateFormTable()) {
			$this->model_admin_tool_import_tables->addTable($this->request->post);

			$this->finishAndRedirect();
		}

		$this->getForm();
	}

	private function prepare() {
		$this->load->language('admin/tool/import_tables');

		$this->load->model('admin/tool/import_tables');
		$this->load->model('admin/tool/generator');
	}

	private function isPost() {
		return $this->request->server['REQUEST_METHOD'] === 'POST';
	}

	private function finishAndRedirect() {
		$this->session->data['success'] = $this->language->get('text_success');

		$this->redirect($this->url->link('admin/tool/import_tables', 'token=' . $this->session->data['token'], 'SSL'));
	}

	private function getForm() {
		$this->document->setTitle($this->language->get('heading_title'));

		$this->data['heading_title'] = $this->language->get('heading_title');
		$this->data['tab_database']  = $this->language->get('tab_database');
		$this->data['tab_table']     = $this->language->get('tab_table');

		$this->data['breadcrumbs'] = array(
			array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('admin/common/home', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => false,
			),
			array(
				'text'      => $this->language->get('heading_title'),
				'href'      => $this->url->link('admin/tool/import_tables', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => ' :: ',
			),
		);

		foreach (array('button_insert', 'text_select', 'text_type', 'text_name', 'text_ip', 'text_port', 'text_username', 'text_password', 'text_database', 'text_module_id') as $key) {
			$this->data[$key] = $this->language->get($key);
		}

		$this->data['action_insert_database'] = $this->url->link('admin/tool/import_tables/insert_database', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['action_insert_table']    = $this->url->link('admin/tool/import_tables/insert_table', 'token=' . $this->session->data['token'], 'SSL');

		foreach (array('type', 'name_database', 'name_table', 'ip', 'username', 'database_id') as $field) {
			$this->data['error_' . $field] = isset($this->error[$field]) ? $this->error[$field] : '';
		}

		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}

		foreach (array('type', 'name', 'ip', 'port', 'username', 'password', 'module_id') as $field) {
			$this->data[$field] = isset($this->request->post[$field]) ? $this->request->post[$field] : '';
		}

		$this->data['databases']   = $this->model_admin_tool_generator->getDatabases();
		$this->data['database_id'] = isset($this->request->post['database_id']) ? $this->request->post['database_id'] : '0';

		$this->template = 'admin/view/tool/import_tables.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}

	private function validateFormDatabase() {
		foreach (array('type' => 'type', 'name' => 'name_database', 'ip' => 'ip', 'username' => 'username', 'module_id' => 'module_id') as $field => $errorKey) {
			$length = utf8_strlen($this->request->post[$field]);

			if ($length < 1 || $length > 32) {
				$this->error[$errorKey] = $this->language->get('error_' . $field);
			}
		}

		return empty($this->error);
	}

	private function validateFormTable() {
		// the name goes unquoted into the generator's DESCRIBE and into the
		// names of the generated classes and files: only a plain SQL identifier
		if (!preg_match('/^[A-Za-z0-9_]{1,32}$/', $this->request->post['name'])) {
			$this->error['name_table'] = $this->language->get('error_name');
		}

		if (isset($this->request->post['database_id']) && $this->request->post['database_id'] == 0) {
			$this->error['database_id'] = $this->language->get('error_database_id');
		}

		return empty($this->error);
	}
}
?>
