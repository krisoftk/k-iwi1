<?php
class ControllerAdminLocalisationLanguage extends Controller {
	const MAX_UPLOAD = 2097152;

	private $error = array();

	public function index() {
		$this->prepare();

		$this->getList();
	}

	public function insert() {
		$this->prepare();

		if ($this->isPost() && $this->validateForm()) {
			$languageId = $this->model_admin_localisation_language->addLanguage($this->formData());

			// after creating it we stay in the form, where the texts are uploaded
			$this->session->data['success'] = $this->language->get('text_success');

			$this->redirect($this->link('admin/localisation/language/update', '&language_id=' . (int) $languageId));
		}

		$this->getForm();
	}

	public function update() {
		$this->prepare();

		if ($this->isPost() && $this->validateForm()) {
			$this->model_admin_localisation_language->editLanguage($this->request->get['language_id'], $this->formData());

			$this->session->data['success'] = $this->language->get('text_success');

			$this->redirect($this->link('admin/localisation/language'));
		}

		$this->getForm();
	}

	public function delete() {
		$this->prepare();

		if (isset($this->request->post['selected']) && is_array($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $languageId) {
				$this->model_admin_localisation_language->deleteLanguage($languageId);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$this->redirect($this->link('admin/localisation/language'));
		}

		$this->getList();
	}

	public function download() {
		$this->prepare();

		$language = $this->requestedLanguage();

		if (!$language) {
			$this->redirect($this->link('admin/localisation/language'));
		}

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->addHeader('Content-Disposition: attachment; filename="k-iwi-language-' . $language['CODE'] . '.json"');
		$this->response->setOutput($this->model_admin_localisation_language_pack->toJson($language));
	}

	public function upload() {
		$this->prepare();

		$language = $this->requestedLanguage();

		if (!$language) {
			$this->redirect($this->link('admin/localisation/language'));
		}

		$back = $this->link('admin/localisation/language/update', '&language_id=' . $language['ID']);

		$message = $this->importPack($language);

		if ($message === true) {
			$this->session->data['success'] = $this->language->get('text_upload_success');
		} else {
			$this->session->data['upload_error'] = $message;
		}

		$this->redirect($back);
	}

	// true if the pack was uploaded, otherwise the error message
	private function importPack($language) {
		// Request passes $_FILES through htmlspecialchars too: numbers as strings
		// and a temporary file path to decode
		$files = $this->request->files;

		if (!$this->isPost() || !isset($files['pack']['error']) || (int) $files['pack']['error'] === UPLOAD_ERR_NO_FILE) {
			return $this->language->get('error_upload_missing');
		}

		$file = $files['pack'];
		$file['tmp_name'] = html_entity_decode($file['tmp_name'], ENT_QUOTES, 'UTF-8');

		if ((int) $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
			return $this->language->get('error_upload');
		}

		if ((int) $file['size'] > self::MAX_UPLOAD) {
			return sprintf($this->language->get('error_upload_size'), self::MAX_UPLOAD / 1048576);
		}

		$errors = array();
		$files = $this->model_admin_localisation_language_pack->parse(file_get_contents($file['tmp_name']), $errors);

		if ($files === false) {
			$details = array();

			foreach ($errors as $error) {
				$details[] = is_array($error)
					? sprintf($this->language->get('error_pack_' . $error[0]), htmlspecialchars($error[1], ENT_QUOTES, 'UTF-8'))
					: $this->language->get('error_pack_' . $error);
			}

			return $this->language->get('error_pack') . '<br />' . implode('<br />', $details);
		}

		$blocked = $this->model_admin_localisation_language_pack->import($language, $files);

		if ($blocked) {
			return sprintf($this->language->get('error_writable'), implode(', ', array_map('htmlspecialchars', $blocked)));
		}

		return true;
	}

	private function prepare() {
		$this->language->load('admin/localisation/language');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('admin/localisation/language');
		$this->load->model('admin/localisation/language_pack');
	}

	private function isPost() {
		return $this->request->server['REQUEST_METHOD'] === 'POST';
	}

	private function link($route, $query = '') {
		return $this->url->link($route, 'token=' . $this->session->data['token'] . $query, 'SSL');
	}

	private function requestedLanguage() {
		if (!isset($this->request->get['language_id'])) {
			return false;
		}

		return $this->model_admin_localisation_language->getLanguage($this->request->get['language_id']);
	}

	// the language of config.ini: it can't be disabled or deleted
	private function defaultCode() {
		$languages = array();

		foreach ($this->model_admin_localisation_language->getLanguages() as $language) {
			$languages[$language['CODE']] = array('code' => $language['CODE'], 'status' => $language['STATUS']);
		}

		$default = Language::choose($languages, array($this->config->get('setting.language')));

		return $default['code'];
	}

	private function formData() {
		$post = $this->request->post;

		return array(
			'name'       => trim($post['name']),
			'code'       => trim($post['code']),
			'locale'     => isset($post['locale']) ? trim($post['locale']) : '',
			'directory'  => isset($post['directory']) ? trim($post['directory']) : '',
			'sort_order' => isset($post['sort_order']) ? (int) $post['sort_order'] : 0,
			'status'     => !empty($post['status']) ? '1' : '0',
		);
	}

	private function breadcrumbs() {
		return array(
			array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->link('admin/common/home'),
				'separator' => false,
			),
			array(
				'text'      => $this->language->get('heading_title'),
				'href'      => $this->link('admin/localisation/language'),
				'separator' => ' :: ',
			),
		);
	}

	protected function getList() {
		$this->data['breadcrumbs'] = $this->breadcrumbs();
		$this->data['insert'] = $this->link('admin/localisation/language/insert');
		$this->data['delete'] = $this->link('admin/localisation/language/delete');

		$defaultCode = $this->defaultCode();
		$languages = $this->model_admin_localisation_language->getLanguages();

		// completeness: text files present compared with the default language
		$defaultFiles = 0;

		foreach ($languages as $language) {
			if ($language['CODE'] === $defaultCode) {
				$defaultFiles = $this->model_admin_localisation_language_pack->countFiles($language);
			}
		}

		$this->data['languages'] = array();

		foreach ($languages as $language) {
			$this->data['languages'][] = array(
				'language_id' => $language['ID'],
				'name'        => $language['NAME'] . (($language['CODE'] === $defaultCode) ? $this->language->get('text_default') : ''),
				'code'        => $language['CODE'],
				'directory'   => $language['DIRECTORY'],
				'files'       => sprintf($this->language->get('text_files'), $this->model_admin_localisation_language_pack->countFiles($language), $defaultFiles),
				'status'      => $language['STATUS'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'selected'    => isset($this->request->post['selected']) && is_array($this->request->post['selected']) && in_array($language['ID'], $this->request->post['selected']),
				'action'      => array(
					array(
						'text' => $this->language->get('text_edit'),
						'href' => $this->link('admin/localisation/language/update', '&language_id=' . $language['ID']),
					),
					array(
						'text' => $this->language->get('button_download'),
						'href' => $this->link('admin/localisation/language/download', '&language_id=' . $language['ID']),
					),
				),
			);
		}

		foreach (array(
			'heading_title', 'text_no_results', 'text_help_list', 'column_name', 'column_code', 'column_directory',
			'column_files', 'column_status', 'column_action', 'button_insert', 'button_delete',
		) as $key) {
			$this->data[$key] = $this->language->get($key);
		}

		$this->data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}

		$this->template = 'admin/view/localisation/language_list.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}

	protected function getForm() {
		foreach (array(
			'heading_title', 'text_enabled', 'text_disabled', 'text_pack', 'text_pack_help', 'text_pack_new',
			'entry_name', 'entry_code', 'entry_locale', 'entry_directory', 'entry_sort_order', 'entry_status',
			'entry_pack', 'help_code', 'help_locale', 'help_directory', 'help_directory_fixed',
			'button_save', 'button_cancel', 'button_download', 'button_upload',
		) as $key) {
			$this->data[$key] = $this->language->get($key);
		}

		foreach (array('warning', 'name', 'code', 'locale', 'directory', 'status') as $field) {
			$this->data['error_' . $field] = isset($this->error[$field]) ? $this->error[$field] : '';
		}

		$this->data['breadcrumbs'] = $this->breadcrumbs();

		$language = $this->requestedLanguage();

		$this->data['editing'] = (bool) $language;
		$this->data['action'] = $language
			? $this->link('admin/localisation/language/update', '&language_id=' . $language['ID'])
			: $this->link('admin/localisation/language/insert');
		$this->data['cancel'] = $this->link('admin/localisation/language');

		if ($language) {
			$this->data['download'] = $this->link('admin/localisation/language/download', '&language_id=' . $language['ID']);
			$this->data['upload'] = $this->link('admin/localisation/language/upload', '&language_id=' . $language['ID']);
		}

		$fields = array(
			'name'       => 'NAME',
			'code'       => 'CODE',
			'locale'     => 'LOCALE',
			'directory'  => 'DIRECTORY',
			'sort_order' => 'SORT_ORDER',
			'status'     => 'STATUS',
		);

		foreach ($fields as $field => $column) {
			if ($this->isPost() && isset($this->request->post[$field])) {
				$this->data[$field] = $this->request->post[$field];
			} elseif ($language) {
				$this->data[$field] = $language[$column];
			} else {
				$this->data[$field] = ($field === 'status') ? '1' : '';
			}
		}

		// the folder stays the one set at creation, even if the POST says otherwise
		if ($language) {
			$this->data['directory'] = $language['DIRECTORY'];
		}

		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}

		if (isset($this->session->data['upload_error'])) {
			$this->data['error_warning'] = $this->session->data['upload_error'];
			unset($this->session->data['upload_error']);
		}

		$this->template = 'admin/view/localisation/language_form.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}

	protected function validateForm() {
		$post = $this->request->post;

		foreach (array('name', 'code', 'locale', 'directory', 'sort_order') as $field) {
			if (isset($post[$field]) && !is_string($post[$field])) {
				$this->error['warning'] = $this->language->get('error_form');

				return false;
			}
		}

		$data = $this->formData();
		$language = $this->requestedLanguage();

		if (isset($this->request->get['language_id']) && !$language) {
			$this->error['warning'] = $this->language->get('error_form');

			return false;
		}

		$length = utf8_strlen($data['name']);

		if ($length < 1 || $length > 64) {
			$this->error['name'] = $this->language->get('error_name');
		}

		// e.g. it_IT, en_GB, pt_BR, de: it's also the value of setting.language
		if (!preg_match('/^[a-z]{2,3}(_[A-Z]{2})?$/', $data['code'])) {
			$this->error['code'] = $this->language->get('error_code');
		} else {
			$existing = $this->model_admin_localisation_language->getLanguageBy('CODE', $data['code']);

			if ($existing && (!$language || $existing['ID'] != $language['ID'])) {
				$this->error['code'] = $this->language->get('error_code_exists');
			}
		}

		if (utf8_strlen($data['locale']) > 255 || !preg_match('/^[A-Za-z0-9_.,@-]*$/', $data['locale'])) {
			$this->error['locale'] = $this->language->get('error_locale');
		}

		// the folder becomes a path on disk: only lowercase letters, digits and _
		if (!$language) {
			if (!preg_match('/^[a-z0-9_]{2,32}$/', $data['directory'])) {
				$this->error['directory'] = $this->language->get('error_directory');
			} elseif ($this->model_admin_localisation_language->getLanguageBy('DIRECTORY', $data['directory'])) {
				$this->error['directory'] = $this->language->get('error_directory_exists');
			}
		}

		// the language of config.ini stays enabled and with the same code, otherwise
		// setting.language wouldn't find it anymore and the default would change by itself
		if ($language && $language['CODE'] === $this->defaultCode()) {
			if ($data['status'] !== '1') {
				$this->error['status'] = $this->language->get('error_default_status');
			}

			if ($data['code'] !== $language['CODE'] && !isset($this->error['code'])) {
				$this->error['code'] = $this->language->get('error_default_code');
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_form');
		}

		return empty($this->error);
	}

	protected function validateDelete() {
		$defaultCode = $this->defaultCode();

		foreach ($this->request->post['selected'] as $languageId) {
			$language = $this->model_admin_localisation_language->getLanguage($languageId);

			if ($language && $language['CODE'] === $defaultCode) {
				$this->error['warning'] = $this->language->get('error_default_delete');
			}
		}

		return empty($this->error);
	}
}
?>
