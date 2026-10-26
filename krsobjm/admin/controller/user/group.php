<?php
class ControllerAdminUserGroup extends Controller {
	private $error = array();

	public function index() {
		$this->language->load('admin/user/group');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('admin/user/group');

		$this->getList();
	}

	public function insert() {
		$this->prepareForm();

		if ($this->isPost() && $this->validateForm()) {
			$this->model_admin_user_group->addUserGroup($this->request->post);

			$this->finishAndRedirect();
		}

		$this->getForm();
	}

	public function update() {
		$this->prepareForm();

		if ($this->isPost() && $this->validateForm()) {
			$this->model_admin_user_group->editUserGroup($this->request->get['user_group_id'], $this->request->post);

			$this->finishAndRedirect();
		}

		$this->getForm();
	}

	public function delete() {
		$this->language->load('admin/user/group');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('admin/user/group');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $groupId) {
				$this->model_admin_user_group->deleteUserGroup($groupId);
			}

			$this->finishAndRedirect();
		}

		$this->getList();
	}

	private function prepareForm() {
		$this->language->load('admin/user/group');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('admin/user/group');
		$this->load->model('admin/extension/prog');
		$this->load->model('admin/extension/module');
	}

	private function isPost() {
		return $this->request->server['REQUEST_METHOD'] === 'POST';
	}

	private function finishAndRedirect() {
		$this->session->data['success'] = $this->language->get('text_success');

		$this->redirect($this->url->link('admin/user/group', 'token=' . $this->session->data['token'] . $this->listQueryString(), 'SSL'));
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
				'href'      => $this->url->link('admin/user/group', 'token=' . $this->session->data['token'] . $query, 'SSL'),
				'separator' => ' :: ',
			),
		);

		$this->data['insert'] = $this->url->link('admin/user/group/insert', 'token=' . $this->session->data['token'] . $query, 'SSL');
		$this->data['delete'] = $this->url->link('admin/user/group/delete', 'token=' . $this->session->data['token'] . $query, 'SSL');

		$filter = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('admin.list_limit'),
			'limit' => $this->config->get('admin.list_limit'),
		);

		$total   = $this->model_admin_user_group->getTotalUserGroups();
		$results = $this->model_admin_user_group->getUserGroups($filter);

		$this->data['user_groups'] = array();

		foreach ($results as $result) {
			$this->data['user_groups'][] = array(
				'user_group_id' => $result['ID'],
				'name'          => $result['NAME'],
				'module_name'   => $result['MODULE_NAME'],
				'selected'      => isset($this->request->post['selected']) && in_array($result['ID'], $this->request->post['selected']),
				'action'        => array(
					array(
						'text' => $this->language->get('text_edit'),
						'href' => $this->url->link('admin/user/group/update', 'token=' . $this->session->data['token'] . '&user_group_id=' . $result['ID'] . $query, 'SSL'),
					),
				),
			);
		}

		foreach (array('heading_title', 'text_no_results', 'column_name', 'column_module_name', 'column_action', 'button_insert', 'button_delete') as $key) {
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

		foreach (array('name' => 'NAME', 'module_name' => 'MODULE_NAME') as $key => $column) {
			$this->data['sort_' . $key] = $this->url->link('admin/user/group', 'token=' . $this->session->data['token'] . '&sort=' . $column . '&order=' . $toggledOrder . $pageSuffix, 'SSL');
		}

		$pagination = new Pagination();
		$pagination->total = $total;
		$pagination->page  = $page;
		$pagination->limit = $this->config->get('admin.list_limit');
		$pagination->text  = $this->language->get('text_pagination');
		$pagination->url   = $this->url->link('admin/user/group', 'token=' . $this->session->data['token'] . $this->listQueryString() . '&page={page}', 'SSL');

		$this->data['pagination'] = $pagination->render();
		$this->data['sort']       = $sort;
		$this->data['order']      = $order;

		$this->template = 'admin/view/user/group_list.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}

	protected function getForm() {
		foreach (array(
			'heading_title', 'text_select_all', 'text_unselect_all', 'tab_group', 'tab_prog',
			'column_prog_name', 'column_prog_menu', 'entry_name', 'entry_modules',
			'button_save', 'button_cancel',
		) as $key) {
			$this->data[$key] = $this->language->get($key);
		}

		foreach (array('warning', 'name', 'progs') as $field) {
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
				'href'      => $this->url->link('admin/user/group', 'token=' . $this->session->data['token'] . $query, 'SSL'),
				'separator' => ' :: ',
			),
		);

		$groupId = isset($this->request->get['user_group_id']) ? $this->request->get['user_group_id'] : null;

		$this->data['action'] = $groupId
			? $this->url->link('admin/user/group/update', 'token=' . $this->session->data['token'] . '&user_group_id=' . $groupId . $query, 'SSL')
			: $this->url->link('admin/user/group/insert', 'token=' . $this->session->data['token'] . $query, 'SSL');

		$this->data['cancel'] = $this->url->link('admin/user/group', 'token=' . $this->session->data['token'] . $query, 'SSL');

		$groupInfo = null;
		$groupProgIds = array();
		$groupModule = null;

		if ($groupId && !$this->isPost()) {
			$groupInfo   = $this->model_admin_user_group->getUserGroup($groupId);
			$groupModule = $this->model_admin_user_group->getModuleByGroupId($groupId);

			foreach ($this->model_admin_user_group->getProgByGroupId($groupId) as $prog) {
				$groupProgIds[] = $prog['ID'];
			}
		}

		$this->data['progs']   = $this->model_admin_extension_prog->getProgs();
		$this->data['modules'] = $this->model_admin_extension_module->getModules();

		$this->data['group_progs'] = isset($this->request->post['progs']) ? $this->request->post['progs'] : $groupProgIds;

		$this->data['name'] = isset($this->request->post['name'])
			? $this->request->post['name']
			: ($groupInfo ? $groupInfo['NAME'] : '');

		$this->data['group_module'] = isset($this->request->post['module'])
			? $this->request->post['module']
			: ($groupInfo ? $groupModule['ID'] : 0);

		$this->template = 'admin/view/user/group_form.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}

	protected function validateForm() {
		$length = utf8_strlen($this->request->post['name']);

		if ($length < 3 || $length > 64) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (empty($this->request->post['progs'])) {
			$this->error['progs'] = $this->language->get('error_progs');
		}

		return empty($this->error);
	}

	protected function validateDelete() {
		foreach ($this->request->post['selected'] as $groupId) {
			$usersInGroup = $this->model_admin_user_group->getTotalUsersByGroupId($groupId);

			if ($usersInGroup) {
				$this->error['warning'] = sprintf($this->language->get('error_user'), $usersInGroup);
			}
		}

		return empty($this->error);
	}
}
?>
