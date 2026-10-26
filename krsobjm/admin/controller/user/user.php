<?php
class ControllerAdminUserUser extends Controller {
	private $error = array();

	public function index() {
		$this->language->load('admin/user/user');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('admin/user/user');

		$this->getList();
	}

	public function insert() {
		$this->prepareForm();

		if ($this->isPost() && $this->validateForm()) {
			$this->model_admin_user_user->addUser($this->request->post);

			$this->finishAndRedirect();
		}

		$this->getForm();
	}

	public function update() {
		$this->prepareForm();

		if ($this->isPost() && $this->validateForm()) {
			$this->model_admin_user_user->editUser($this->request->get['user_id'], $this->request->post);

			$this->finishAndRedirect();
		}

		$this->getForm();
	}

	public function delete() {
		$this->language->load('admin/user/user');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('admin/user/user');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $userId) {
				$this->model_admin_user_user->deleteUser($userId);
			}

			$this->finishAndRedirect();
		}

		$this->getList();
	}

	private function prepareForm() {
		$this->language->load('admin/user/user');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('admin/user/user');
		$this->load->model('admin/user/group');
		$this->load->model('admin/extension/module');
	}

	private function isPost() {
		return $this->request->server['REQUEST_METHOD'] === 'POST';
	}

	private function finishAndRedirect() {
		$this->session->data['success'] = $this->language->get('text_success');

		$this->redirect($this->url->link('admin/user/user', 'token=' . $this->session->data['token'] . $this->listQueryString(), 'SSL'));
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

	private function breadcrumbs($extra = '') {
		return array(
			array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('admin/common/home', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => false,
			),
			array(
				'text'      => $this->language->get('heading_title'),
				'href'      => $this->url->link('admin/user/user', 'token=' . $this->session->data['token'] . $extra, 'SSL'),
				'separator' => ' :: ',
			),
		);
	}

	protected function getList() {
		$this->config->load('admin');

		$sort  = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'USERNAME';
		$order = isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC';
		$page  = isset($this->request->get['page']) ? $this->request->get['page'] : 1;

		$query = $this->listQueryString();

		$this->data['breadcrumbs'] = $this->breadcrumbs($query);
		$this->data['insert']      = $this->url->link('admin/user/user/insert', 'token=' . $this->session->data['token'] . $query, 'SSL');
		$this->data['delete']      = $this->url->link('admin/user/user/delete', 'token=' . $this->session->data['token'] . $query, 'SSL');

		$filter = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('admin.list_limit'),
			'limit' => $this->config->get('admin.list_limit'),
		);

		$total   = $this->model_admin_user_user->getTotalUsers();
		$results = $this->model_admin_user_user->getUsers($filter);

		$this->data['users'] = array();

		foreach ($results as $result) {
			$this->data['users'][] = array(
				'user_id'    => $result['ID'],
				'username'   => $result['USERNAME'],
				'status'     => $result['ACTIVATION'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['REGISTER_DATA'])),
				'selected'   => isset($this->request->post['selected']) && in_array($result['ID'], $this->request->post['selected']),
				'action'     => array(
					array(
						'text' => $this->language->get('text_edit'),
						'href' => $this->url->link('admin/user/user/update', 'token=' . $this->session->data['token'] . '&user_id=' . $result['ID'] . $query, 'SSL'),
					),
				),
			);
		}

		foreach (array('heading_title', 'text_no_results', 'column_username', 'column_status', 'column_date_added', 'column_action', 'button_insert', 'button_delete') as $key) {
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

		foreach (array('username' => 'USERNAME', 'status' => 'ACTIVATION', 'date_added' => 'REGISTER_DATA') as $key => $column) {
			$this->data['sort_' . $key] = $this->url->link('admin/user/user', 'token=' . $this->session->data['token'] . '&sort=' . $column . '&order=' . $toggledOrder . $pageSuffix, 'SSL');
		}

		$pagination = new Pagination();
		$pagination->total = $total;
		$pagination->page  = $page;
		$pagination->limit = $this->config->get('admin.list_limit');
		$pagination->text  = $this->language->get('text_pagination');
		$pagination->url   = $this->url->link('admin/user/user', 'token=' . $this->session->data['token'] . $this->listQueryString() . '&page={page}', 'SSL');

		$this->data['pagination'] = $pagination->render();
		$this->data['sort']       = $sort;
		$this->data['order']      = $order;

		$this->template = 'admin/view/user/user_list.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}

	protected function getForm() {
		foreach (array(
			'heading_title', 'text_enabled', 'text_disabled', 'tab_user', 'tab_group',
			'entry_username', 'entry_password', 'entry_confirm', 'entry_firstname', 'entry_lastname',
			'entry_email', 'entry_status', 'entry_captcha', 'entry_usergroup', 'entry_modules', 'entry_language',
			'button_save', 'button_cancel', 'button_remove', 'button_add_usergroup',
		) as $key) {
			$this->data[$key] = $this->language->get($key);
		}

		foreach (array('warning', 'username', 'password', 'confirm', 'firstname', 'lastname', 'usergroups', 'language') as $field) {
			$this->data['error_' . $field] = isset($this->error[$field]) ? $this->error[$field] : '';
		}

		$query = $this->listQueryString();

		$this->data['breadcrumbs'] = $this->breadcrumbs($query);

		$userId = isset($this->request->get['user_id']) ? $this->request->get['user_id'] : null;

		$this->data['action'] = $userId
			? $this->url->link('admin/user/user/update', 'token=' . $this->session->data['token'] . '&user_id=' . $userId . $query, 'SSL')
			: $this->url->link('admin/user/user/insert', 'token=' . $this->session->data['token'] . $query, 'SSL');

		$this->data['cancel'] = $this->url->link('admin/user/user', 'token=' . $this->session->data['token'] . $query, 'SSL');

		$userInfo = null;
		$userGroups = array();
		$userModule = null;

		if ($userId && !$this->isPost()) {
			$userInfo   = $this->model_admin_user_user->getUser($userId);
			$userGroups = $this->model_admin_user_user->getUserGroupByUserId($userId);
			$userModule = $this->model_admin_user_user->getModuleByUserId($userId);
		}

		$this->data['all_usergroups'] = $this->model_admin_user_group->getUserGroups();
		$this->data['all_modules']    = $this->model_admin_extension_module->getModules();
		$this->data['all_languages']  = $this->activeLanguages();

		// the empty entry means "no choice": setting.language applies
		$defaultLanguage = Language::choose($this->data['all_languages'], array($this->config->get('setting.language')));
		$this->data['text_language_default'] = sprintf($this->language->get('text_language_default'), $defaultLanguage['name']);

		if (isset($this->request->post['usergroups'])) {
			$this->data['usergroups'] = $this->request->post['usergroups'];
		} else {
			$this->data['usergroups'] = array();

			foreach ($userGroups as $usergroup) {
				$this->data['usergroups'][] = $usergroup['ID'];
			}
		}

		if (isset($this->request->post['module'])) {
			$this->data['module'] = $this->request->post['module'];
		} else {
			$this->data['module'] = $userInfo ? $userModule['ID'] : 0;
		}

		$fields = array(
			'username'  => 'USERNAME',
			'firstname' => 'FIRSTNAME',
			'lastname'  => 'LASTNAME',
			'email'     => 'EMAIL',
			'status'    => 'ACTIVATION',
			'language'  => 'LANGUAGE',
		);

		foreach ($fields as $field => $column) {
			if (isset($this->request->post[$field])) {
				$this->data[$field] = $this->request->post[$field];
			} elseif ($userInfo) {
				$this->data[$field] = $userInfo[$column];
			} else {
				$this->data[$field] = ($field === 'status') ? 0 : '';
			}
		}

		$this->data['password'] = isset($this->request->post['password']) ? $this->request->post['password'] : '';
		$this->data['confirm']  = isset($this->request->post['confirm']) ? $this->request->post['confirm'] : '';

		$this->template = 'admin/view/user/user_form.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}

	protected function validateForm() {
		$username = $this->request->post['username'];
		$length   = utf8_strlen($username);

		if ($length < 3 || $length > 20) {
			$this->error['username'] = $this->language->get('error_username');
		}

		$existing = $this->model_admin_user_user->getUserByUsername($username);
		$userId   = isset($this->request->get['user_id']) ? $this->request->get['user_id'] : null;

		if ($existing && (!$userId || $userId != $existing['ID'])) {
			$this->error['warning'] = $this->language->get('error_exists');
		}

		if (utf8_strlen($this->request->post['firstname']) < 1 || utf8_strlen($this->request->post['firstname']) > 32) {
			$this->error['firstname'] = $this->language->get('error_firstname');
		}

		if (utf8_strlen($this->request->post['lastname']) < 1 || utf8_strlen($this->request->post['lastname']) > 32) {
			$this->error['lastname'] = $this->language->get('error_lastname');
		}

		if ($this->request->post['password'] || !$userId) {
			$passwordLength = utf8_strlen($this->request->post['password']);

			// 72 bytes: beyond that, bcrypt (password_hash) ignores the rest
			if ($passwordLength < 8 || strlen($this->request->post['password']) > 72) {
				$this->error['password'] = $this->language->get('error_password');
			}

			if ($this->request->post['password'] !== $this->request->post['confirm']) {
				$this->error['confirm'] = $this->language->get('error_confirm');
			}
		}

		// Existing groups only: an empty form row (usergroups[0]=) or a made-up
		// id passed the empty() check, the user was created and the group
		// association failed, leaving the user without a group.
		$existing = array();

		foreach ($this->model_admin_user_group->getUserGroups() as $group) {
			$existing[] = (string) $group['ID'];
		}

		$posted = (isset($this->request->post['usergroups']) && is_array($this->request->post['usergroups'])) ? array_filter($this->request->post['usergroups'], 'is_scalar') : array();
		$groups = array_values(array_unique(array_intersect(array_map('strval', $posted), $existing)));

		if (!$groups) {
			$this->error['usergroups'] = $this->language->get('error_usergroups');
		} else {
			$this->request->post['usergroups'] = $groups;
		}

		// '' = no choice (setting.language applies), otherwise an active language
		if (!isset($this->request->post['language']) || !is_string($this->request->post['language'])) {
			$this->request->post['language'] = '';
		}

		$language = $this->request->post['language'];

		if ($language !== '' && !array_key_exists($language, $this->activeLanguages())) {
			$this->error['language'] = $this->language->get('error_language');
		}

		return empty($this->error);
	}

	// languages the user can choose: the active ones of krs_languages
	private function activeLanguages() {
		$this->load->model('common/language');

		$languages = array();

		foreach ($this->model_common_language->getLanguages() as $code => $language) {
			if ($language['status']) {
				$languages[$code] = $language;
			}
		}

		return $languages;
	}

	protected function validateDelete() {
		foreach ($this->request->post['selected'] as $userId) {
			if ($this->user->getId() == $userId) {
				$this->error['warning'] = $this->language->get('error_account');
			}
		}

		return empty($this->error);
	}
}
?>
