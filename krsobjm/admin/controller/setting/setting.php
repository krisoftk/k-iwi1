<?php
class ControllerAdminSettingSetting extends Controller {
	const MAX_EDITOR = 65536;

	private $error = array();

	// Known keys of config.ini: field type, optional (if empty the line is
	// removed and the code's default applies), limits. The keys of the file
	// that aren't here show up as text fields.
	private $schema = array(
		'database.driver'               => array('type' => 'select'),
		'database.host'                 => array('type' => 'text', 'required' => true),
		'database.port'                 => array('type' => 'int', 'min' => 1, 'max' => 65535),
		'database.user'                 => array('type' => 'text', 'required' => true),
		'database.password'             => array('type' => 'password'),
		'database.name'                 => array('type' => 'text', 'required' => true),
		'framework.theme'               => array('type' => 'select'),
		'framework.theme_admin'         => array('type' => 'select'),
		'framework.title_page'          => array('type' => 'text'),
		'framework.title_application'   => array('type' => 'text'),
		'framework.author'              => array('type' => 'text'),
		'framework.copyright'           => array('type' => 'text'),
		'framework.meta_description'    => array('type' => 'text'),
		'setting.use_ssl'               => array('type' => 'bool'),
		'setting.use_session_db'        => array('type' => 'bool'),
		'setting.error_display'         => array('type' => 'bool'),
		'setting.error_log'             => array('type' => 'bool'),
		'setting.file_log'              => array('type' => 'filename', 'required' => true),
		'setting.compression'           => array('type' => 'int', 'min' => 0, 'max' => 9),
		'setting.language'              => array('type' => 'select'),
		'setting.timeout'               => array('type' => 'int', 'min' => 60, 'max' => 604800),
		'setting.decimals'              => array('type' => 'int', 'min' => 0, 'max' => 10),
		'setting.dec_point'             => array('type' => 'text'),
		'setting.thousands_sep'         => array('type' => 'text'),
		'setting.login_max_attempts'    => array('type' => 'int', 'optional' => true, 'min' => 1, 'max' => 1000),
		'setting.login_lockout_minutes' => array('type' => 'int', 'optional' => true, 'min' => 1, 'max' => 10080),
		'setting.scheduler_ssl_verify'  => array('type' => 'bool', 'optional' => true),
		'report.time_expire'            => array('type' => 'int', 'min' => 1, 'max' => 86400),
		'report.base_url'               => array('type' => 'url'),
	);

	public function index() {
		$this->prepare();

		$this->getForm();
	}

	// save from the form with fields
	public function save() {
		$this->prepare();

		if ($this->isPost() && $this->model_admin_setting_setting->isWritable()) {
			$text = $this->model_admin_setting_setting->read();
			$values = $this->validateFields($text);

			if ($values !== false) {
				$new = $this->model_admin_setting_setting->applyValues($text, $values);

				if ($this->checkAndSave($new, $values)) {
					$this->session->data['success'] = $this->language->get('text_success');

					$this->redirect($this->link('admin/setting/setting'));
				}
			}
		}

		$this->getForm();
	}

	// save from the text editor
	public function editor() {
		$this->prepare();

		if ($this->isPost() && $this->model_admin_setting_setting->isWritable()) {
			$text = isset($this->request->post['config_text']) && is_string($this->request->post['config_text'])
				? str_replace("\r\n", "\n", $this->decode($this->request->post['config_text']))
				: '';

			// same line endings as the current file (the browser always sends CRLF)
			if (strpos($this->model_admin_setting_setting->read(), "\r\n") !== false) {
				$text = str_replace("\n", "\r\n", $text);
			}

			if (strlen($text) > self::MAX_EDITOR) {
				$this->error['editor'] = sprintf($this->language->get('error_editor_size'), self::MAX_EDITOR / 1024);
			} elseif ($this->checkAndSave($text, null)) {
				$this->session->data['success'] = $this->language->get('text_success_editor');

				$this->redirect($this->link('admin/setting/setting'));
			}

			$this->data['editor_text'] = $text;
		}

		$this->getForm();
	}

	private function prepare() {
		$this->language->load('admin/setting/setting');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('admin/setting/setting');
		$this->load->model('admin/localisation/language');
	}

	private function isPost() {
		return $this->request->server['REQUEST_METHOD'] === 'POST';
	}

	private function link($route, $query = '') {
		return $this->url->link($route, 'token=' . $this->session->data['token'] . $query, 'SSL');
	}

	// Request passes everything through htmlspecialchars: the file gets the real text
	private function decode($value) {
		return html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
	}

	private function options($key) {
		switch ($key) {
			case 'database.driver':
				return array('mysql' => 'MySQL / MariaDB', 'odbc' => 'ODBC');
			case 'framework.theme':
				return $this->themes(DIR_MODULE . 'standard/view/themes/');
			case 'framework.theme_admin':
				return $this->themes(DIR_MODULE . 'admin/view/themes/');
			case 'setting.language':
				$options = array();

				foreach ($this->model_admin_localisation_language->getLanguages() as $language) {
					if ($language['STATUS']) {
						$options[$language['CODE']] = $language['NAME'] . ' (' . $language['CODE'] . ')';
					}
				}

				return $options;
		}

		return array();
	}

	private function themes($dir) {
		$themes = array();

		foreach (glob($dir . '*', GLOB_ONLYDIR) ?: array() as $path) {
			$themes[basename($path)] = basename($path);
		}

		return $themes;
	}

	// Values to write (key => text, null = remove the line), or false with
	// the errors in $this->error.
	private function validateFields($text) {
		$current = $this->model_admin_setting_setting->parse($text);
		$posted = isset($this->request->post['config']) && is_array($this->request->post['config']) ? $this->request->post['config'] : array();
		$values = array();

		foreach ($this->fieldKeys($text) as $key) {
			$field = $this->fieldName($key);
			$spec = isset($this->schema[$key]) ? $this->schema[$key] : array('type' => 'text');

			if (!isset($posted[$field]) || !is_string($posted[$field])) {
				continue;
			}

			$value = trim($this->decode($posted[$field]));

			// the password is never shown: empty = keep the current one
			if ($spec['type'] === 'password' && $value === '') {
				continue;
			}

			if ($value === '' && !empty($spec['optional'])) {
				$values[$key] = null;

				continue;
			}

			$error = $this->validateValue($key, $spec, $value);

			if ($error) {
				$this->error['config'][$field] = $error;
			} else {
				$values[$key] = $value;
			}
		}

		if (!empty($this->error['config'])) {
			$this->error['warning'] = $this->language->get('error_form');

			return false;
		}

		// only the changed lines are written: the rest of the file stays as it is
		foreach ($values as $key => $value) {
			if ($value === null ? !isset($current[$key]) : (isset($current[$key]) && (string) $current[$key] === $value)) {
				unset($values[$key]);
			}
		}

		return $values;
	}

	private function validateValue($key, $spec, $value) {
		// quotes, line breaks and ${...} would change the meaning of the ini line
		if (preg_match('/["\x00-\x1f\x7f]/', $value) || strpos($value, '${') !== false) {
			return $this->language->get('error_chars');
		}

		if ($value === '') {
			return (!empty($spec['required'])) ? $this->language->get('error_required') : '';
		}

		switch ($spec['type']) {
			case 'int':
				if (!ctype_digit($value) || (isset($spec['min']) && (int) $value < $spec['min']) || (isset($spec['max']) && (int) $value > $spec['max'])) {
					return sprintf($this->language->get('error_int'), $spec['min'], $spec['max']);
				}
				break;
			case 'bool':
				if ($value !== '0' && $value !== '1') {
					return $this->language->get('error_form');
				}
				break;
			case 'select':
				if (!array_key_exists($value, $this->options($key))) {
					return $this->language->get('error_select');
				}
				break;
			case 'filename':
				if (!preg_match('/^[A-Za-z0-9_.-]{1,64}$/', $value) || $value[0] === '.') {
					return $this->language->get('error_filename');
				}
				break;
			case 'url':
				if (!preg_match('#^https?://[^\s]+$#i', $value)) {
					return $this->language->get('error_url');
				}
				break;
		}

		return '';
	}

	// Checks shared by form and editor, then the save. $values are the wanted
	// values (from the form only): read back from the new text they must come
	// out identical, otherwise writing the ini altered them.
	private function checkAndSave($text, $values) {
		$parseError = null;
		$parsed = $this->model_admin_setting_setting->parse($text, $parseError);

		if ($parsed === false) {
			$this->error[$values === null ? 'editor' : 'warning'] = sprintf($this->language->get('error_parse'), htmlspecialchars($parseError, ENT_QUOTES, 'UTF-8'));

			return false;
		}

		if ($values !== null) {
			foreach ($values as $key => $value) {
				if ($value !== null && (!isset($parsed[$key]) || (string) $parsed[$key] !== $value)) {
					$this->error['config'][$this->fieldName($key)] = $this->language->get('error_chars');
				}
			}

			if (!empty($this->error['config'])) {
				$this->error['warning'] = $this->language->get('error_form');

				return false;
			}
		}

		foreach (array('database.driver', 'database.host', 'database.user', 'database.name') as $key) {
			if (!isset($parsed[$key]) || $parsed[$key] === '') {
				$this->error[$values === null ? 'editor' : 'warning'] = sprintf($this->language->get('error_missing'), $key);

				return false;
			}
		}

		// with wrong database settings the site (admin included) wouldn't
		// start anymore: the connection is tried before saving
		$current = $this->model_admin_setting_setting->parse($this->model_admin_setting_setting->read());
		$dbChanged = false;

		foreach (array('database.driver', 'database.host', 'database.port', 'database.user', 'database.password', 'database.name') as $key) {
			if ((isset($current[$key]) ? (string) $current[$key] : '') !== (isset($parsed[$key]) ? (string) $parsed[$key] : '')) {
				$dbChanged = true;
			}
		}

		if ($dbChanged) {
			$dbError = $this->model_admin_setting_setting->testDatabase(
				$parsed['database.driver'], $parsed['database.host'], isset($parsed['database.port']) ? $parsed['database.port'] : '',
				$parsed['database.user'], isset($parsed['database.password']) ? $parsed['database.password'] : '', $parsed['database.name']
			);

			if ($dbError !== '') {
				$this->error[$values === null ? 'editor' : 'warning'] = sprintf($this->language->get('error_database'), htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'));

				return false;
			}
		}

		if (!$this->model_admin_setting_setting->save($text)) {
			$this->error['warning'] = $this->language->get('error_save');

			return false;
		}

		return true;
	}

	// the form keys: those of the file plus the known optional ones
	private function fieldKeys($text) {
		$keys = array();

		foreach ($this->model_admin_setting_setting->sections($text) as $sectionKeys) {
			$keys = array_merge($keys, $sectionKeys);
		}

		foreach ($this->schema as $key => $spec) {
			if (!in_array($key, $keys)) {
				$keys[] = $key;
			}
		}

		return array_unique($keys);
	}

	// in HTML field names the dot would become "_" in $_POST
	private function fieldName($key) {
		return str_replace('.', '__', $key);
	}

	private function label($key, $prefix) {
		$name = $prefix . str_replace('.', '_', $key);
		$text = $this->language->get($name);

		return ($text === $name) ? ($prefix === 'entry_' ? $key : '') : $text;
	}

	protected function getForm() {
		foreach (array(
			'heading_title', 'text_yes', 'text_no', 'text_readonly', 'text_editor_help', 'text_backup_help',
			'text_no_backups', 'text_backup_loaded', 'text_password_keep', 'text_default_value', 'tab_editor', 'tab_backup',
			'column_backup', 'column_size', 'column_action', 'button_save', 'button_save_editor', 'button_load_backup',
		) as $key) {
			$this->data[$key] = $this->language->get($key);
		}

		$model = $this->model_admin_setting_setting;
		$writable = $model->isWritable();
		$text = $model->read();
		$current = $model->parse($text);
		$current = is_array($current) ? $current : array();
		$posted = isset($this->request->post['config']) && is_array($this->request->post['config']) ? $this->request->post['config'] : null;

		$this->data['writable'] = $writable;
		$this->data['readonly_warning'] = $writable ? '' : sprintf($this->language->get('text_readonly'), $model->path());

		$this->data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->link('admin/common/home'), 'separator' => false),
			array('text' => $this->language->get('heading_title'), 'href' => $this->link('admin/setting/setting'), 'separator' => ' :: '),
		);

		$this->data['action'] = $this->link('admin/setting/setting/save');
		$this->data['action_editor'] = $this->link('admin/setting/setting/editor');

		// sections of the file, plus those of the missing optional keys
		$sections = $model->sections($text);

		foreach ($this->schema as $key => $spec) {
			$section = strstr($key, '.', true);

			if (!isset($sections[$section])) {
				$sections[$section] = array();
			}

			if (!in_array($key, $sections[$section])) {
				$sections[$section][] = $key;
			}
		}

		unset($sections['']);

		$this->data['sections'] = array();

		foreach ($sections as $section => $keys) {
			$fields = array();

			foreach ($keys as $key) {
				$spec = isset($this->schema[$key]) ? $this->schema[$key] : array('type' => 'text');
				$name = $this->fieldName($key);

				if ($spec['type'] === 'password') {
					$value = '';
				} elseif ($posted !== null && isset($posted[$name]) && is_string($posted[$name])) {
					$value = $this->decode($posted[$name]);
				} else {
					$value = isset($current[$key]) ? (string) $current[$key] : '';
				}

				$options = array();

				foreach ($this->options($key) as $optionValue => $optionText) {
					$options[] = array('value' => htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8'), 'text' => htmlspecialchars($optionText, ENT_QUOTES, 'UTF-8'));
				}

				// a value of the file that isn't among the choices stays visible and selected
				if ($spec['type'] === 'select' && $value !== '' && !array_key_exists($value, $this->options($key))) {
					$options[] = array('value' => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), 'text' => htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . ' (?)');
				}

				$fields[] = array(
					'key'      => $key,
					'name'     => $name,
					'type'     => $spec['type'],
					'label'    => $this->label($key, 'entry_'),
					'help'     => $this->label($key, 'help_'),
					'value'    => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
					'options'  => $options,
					'optional' => !empty($spec['optional']),
					'error'    => isset($this->error['config'][$name]) ? $this->error['config'][$name] : '',
				);
			}

			$title = $this->language->get('tab_' . $section);

			$this->data['sections'][] = array(
				'id'     => preg_replace('/[^a-z0-9_]/', '_', strtolower($section)),
				'title'  => ($title === 'tab_' . $section) ? htmlspecialchars($section, ENT_QUOTES, 'UTF-8') : $title,
				'fields' => $fields,
			);
		}

		// editor: the submitted text if the save failed, a chosen backup,
		// or the current file
		$this->data['backup_loaded'] = '';

		if (!isset($this->data['editor_text'])) {
			$this->data['editor_text'] = $text;

			if (isset($this->request->get['backup'])) {
				$backup = $model->readBackup($this->request->get['backup']);

				if ($backup !== false) {
					$this->data['editor_text'] = $backup;
					$this->data['backup_loaded'] = sprintf($this->language->get('text_backup_loaded'), htmlspecialchars($this->request->get['backup'], ENT_QUOTES, 'UTF-8'));
				}
			}
		}

		$this->data['editor_text'] = htmlspecialchars($this->data['editor_text'], ENT_QUOTES, 'UTF-8');
		$this->data['open_editor'] = isset($this->error['editor']) || $this->data['backup_loaded'] !== '';

		$this->data['backups'] = array();

		foreach ($model->backups() as $backup) {
			$this->data['backups'][] = array(
				'name' => $backup['name'],
				'date' => date($this->language->get('date_format_short') . ' H:i:s', $backup['time']),
				'size' => number_format($backup['size'] / 1024, 1) . ' KB',
				'load' => $this->link('admin/setting/setting', '&backup=' . urlencode($backup['name'])),
			);
		}

		$this->data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$this->data['error_editor'] = isset($this->error['editor']) ? $this->error['editor'] : '';

		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}

		$this->template = 'admin/view/setting/setting_form.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}
}
?>
