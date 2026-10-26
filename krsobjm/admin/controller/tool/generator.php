<?php
class ControllerAdminToolGenerator extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('admin/tool/generator');

		$this->load->model('admin/tool/generator');

		$this->getForm();
	}

	public function tables() {
		$this->load->model('admin/tool/generator');

		$json = isset($this->request->post['database_id'])
			? $this->model_admin_tool_generator->getTables($this->request->post['database_id'])
			: null;

		$this->response->setOutput(json_encode($json));
	}

	public function fields() {
		$this->load->model('admin/tool/generator');

		$json = isset($this->request->post['table_id'])
			? $this->model_admin_tool_generator->getDescribeTable($this->request->post['table_id'])
			: null;

		$this->response->setOutput(json_encode($json));
	}

	public function generator() {
		$this->load->language('admin/tool/generator');

		$this->load->model('admin/tool/generator');

		if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validateForm()) {
			$this->model_admin_tool_generator->generator($this->request->post);

			$this->session->data['success'] = sprintf($this->language->get('text_success'), $this->request->post['module']);

			$this->redirect($this->url->link('admin/tool/generator', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$this->getForm();
	}

	private function getForm() {
		$this->document->setTitle($this->language->get('heading_title'));

		$this->data['heading_title'] = $this->language->get('heading_title');

		$this->data['breadcrumbs'] = array(
			array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('admin/common/home', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => false,
			),
			array(
				'text'      => $this->language->get('heading_title'),
				'href'      => $this->url->link('admin/tool/generator', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => ' :: ',
			),
		);

		foreach (array(
			'text_select', 'text_database', 'text_table', 'text_module', 'text_delete_table',
			'text_search_check', 'text_insert_check', 'text_delete_check', 'text_pagination_check',
			'text_search_description', 'text_insert_description', 'text_relationship_description',
			'text_relationship_one_to_many', 'text_relationship_many_to_one', 'text_relationship_many_to_many',
			'tab_general', 'tab_insert', 'tab_delete', 'tab_search', 'tab_relationship', 'button_generator',
			'column_view', 'column_field', 'column_type', 'column_null', 'column_key', 'column_default', 'column_extra',
			'button_add_relationship', 'button_remove_relationship',
		) as $key) {
			$this->data[$key] = $this->language->get($key);
		}

		$this->data['action_tables']    = $this->url->link('admin/tool/generator/tables', 'token=' . $this->session->data['token']);
		$this->data['action_fields']    = $this->url->link('admin/tool/generator/fields', 'token=' . $this->session->data['token']);
		$this->data['action_generator'] = $this->url->link('admin/tool/generator/generator', 'token=' . $this->session->data['token']);

		foreach (array('database_id', 'table_id', 'module', 'fields', 'fields_key', 'fields_insert', 'fields_search') as $field) {
			$this->data['error_' . $field] = isset($this->error[$field]) ? $this->error[$field] : '';
		}

		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}

		foreach (array('module', 'delete_table', 'search_check', 'insert_check', 'delete_check', 'pagination_check') as $field) {
			$this->data[$field] = isset($this->request->post[$field]) ? $this->request->post[$field] : '';
		}

		$this->data['pagination_limit'] = isset($this->request->post['pagination_limit']) ? $this->request->post['pagination_limit'] : '5';

		$this->data['databases'] = $this->model_admin_tool_generator->getDatabases();

		if (isset($this->request->post['database_id'])) {
			$this->data['database_id'] = $this->request->post['database_id'];
			$this->data['tables']      = $this->model_admin_tool_generator->getTables($this->request->post['database_id']);

			if (!isset($this->request->post['table_id'])) {
				$this->data['table_id'] = '0';
			} else {
				$this->data['table_id'] = $this->request->post['table_id'];

				if ($this->request->post['table_id'] != 0) {
					$this->data['fields']       = $this->model_admin_tool_generator->getDescribeTable($this->request->post['table_id']);
					$this->data['fields_check'] = isset($this->request->post['fields']) ? $this->request->post['fields'] : array();
				}
			}
		} else {
			$this->data['database_id'] = '0';
		}

		$this->template = 'admin/view/tool/generator.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}

	private function anyFieldHas($flag) {
		if (!isset($this->request->post['fields'])) {
			return false;
		}

		foreach ($this->request->post['fields'] as $field) {
			if (isset($field[$flag])) {
				return true;
			}
		}

		return false;
	}

	// the folder the generator couldn't write, or '' if it can
	private function unwritableDir($module) {
		$dir = DIR_MODULE . $module;

		if (!is_dir($dir)) {
			return is_writable(DIR_MODULE) ? '' : DIR_MODULE;
		}

		foreach (array('', '/controller', '/model', '/view', '/installation', '/language') as $sub) {
			if (is_dir($dir . $sub) && !is_writable($dir . $sub)) {
				return $dir . $sub;
			}
		}

		return '';
	}

	private function validateForm() {
		if (!isset($this->request->post['table_id']) || $this->request->post['database_id'] == 0) {
			$this->error['database_id'] = $this->language->get('error_database_id');
		}

		if (!isset($this->request->post['table_id']) || $this->request->post['table_id'] == 0) {
			$this->error['table_id'] = $this->language->get('error_table_id');
		}

		// The module name becomes a folder under krsobjm/ and part of the
		// class names and routes: only letters, digits and underscores,
		// starting with a letter (no "../", spaces or dashes).
		$module = isset($this->request->post['module']) ? $this->request->post['module'] : '';

		if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,31}$/', $module)) {
			$this->error['module'] = $this->language->get('error_module');
		} elseif (in_array(strtolower($module), array('admin', 'standard', 'common', 'install', 'error'))) {
			// framework modules: the generator would overwrite their files
			$this->error['module'] = $this->language->get('error_module_reserved');
		} elseif ($blocked = $this->unwritableDir($module)) {
			// better to say it here than to leave the module half written and an error 500
			$this->error['module'] = sprintf($this->language->get('error_writable'), $blocked);
		}

		if (isset($this->request->post['fields']) && !$this->anyFieldHas('check')) {
			$this->error['fields'] = $this->language->get('error_fields');
		}

		if (isset($this->request->post['search_check']) && !$this->anyFieldHas('field_search_check')) {
			$this->error['fields_search'] = $this->language->get('error_fields_search');
		}

		if (isset($this->request->post['insert_check'])) {
			if (!$this->anyFieldHas('key_check')) {
				$this->error['fields_key'] = $this->language->get('error_fields_key_insert');
			}

			if (!$this->anyFieldHas('field_insert_check')) {
				$this->error['fields_insert'] = $this->language->get('error_fields_insert');
			}
		}

		if (isset($this->request->post['delete_check']) && !$this->anyFieldHas('key_check')) {
			$this->error['fields_key'] = $this->language->get('error_fields_key_delete');
		}

		return empty($this->error);
	}
}
?>
