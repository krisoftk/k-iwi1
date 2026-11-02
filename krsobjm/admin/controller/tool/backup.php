<?php
/**
 * Admin > Tools > Backup / Restore of the framework database. Backups live
 * in the backup/ folder of the project (outside the public folder); a
 * restore always goes through a confirmation page and makes an automatic
 * backup of the current database first.
 */
class ControllerAdminToolBackup extends Controller {
	// tables left out of a new backup unless chosen: the sessions are
	// temporary and hold the session data of whoever is logged in
	private $unchecked = array('krs_session');

	public function index() {
		$this->prepare();

		$this->getList();
	}

	public function create() {
		$this->prepare();

		if ($this->isPost() && $this->canWork()) {
			$available = array();

			foreach ($this->model_admin_tool_backup->tables() as $table) {
				$available[] = $table['name'];
			}

			$posted = isset($this->request->post['tables']) && is_array($this->request->post['tables']) ? $this->request->post['tables'] : array();
			// in the order of the database, only tables that exist
			$tables = array_values(array_intersect($available, $posted));

			if (!$tables) {
				$this->session->data['error'] = $this->language->get('error_no_tables');
			} else {
				try {
					set_time_limit(0);

					$name = $this->model_admin_tool_backup->create($tables);
					$this->session->data['success'] = sprintf($this->language->get('text_created'), $name);
				} catch (Throwable $e) {
					$this->session->data['error'] = sprintf($this->language->get('error_create'), htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
				}
			}
		}

		$this->redirect($this->link('admin/tool/backup'));
	}

	// The file goes straight to the browser, without loading it in memory
	// (and without the output compression of Response).
	public function download() {
		$this->prepare();

		$path = $this->model_admin_tool_backup->path(isset($this->request->get['file']) ? $this->request->get['file'] : '');

		if ($path === false) {
			$this->redirect($this->link('admin/tool/backup'));
		}

		while (ob_get_level()) {
			ob_end_clean();
		}

		header('Content-Type: application/sql');
		header('Content-Disposition: attachment; filename="' . basename($path) . '"');
		header('Content-Length: ' . filesize($path));
		header('X-Content-Type-Options: nosniff');

		readfile($path);
		exit;
	}

	public function delete() {
		$this->prepare();

		if ($this->isPost() && $this->model_admin_tool_backup->isWritable()) {
			$name = isset($this->request->post['file']) ? $this->request->post['file'] : '';

			if ($this->model_admin_tool_backup->delete($name)) {
				$this->session->data['success'] = sprintf($this->language->get('text_deleted'), htmlspecialchars($name, ENT_QUOTES, 'UTF-8'));
			}
		}

		$this->redirect($this->link('admin/tool/backup'));
	}

	// An uploaded backup is checked, kept in the folder and opened in the
	// confirmation page: nothing is restored yet.
	public function upload() {
		$this->prepare();

		if (!$this->isPost() || !$this->canWork()) {
			$this->redirect($this->link('admin/tool/backup'));
		}

		// Request passes $_FILES through htmlspecialchars too
		$files = $this->request->files;

		if (!isset($files['backup']['error']) || (int) $files['backup']['error'] === UPLOAD_ERR_NO_FILE) {
			$this->session->data['error'] = $this->language->get('error_upload_missing');
		} elseif (in_array((int) $files['backup']['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE))) {
			$this->session->data['error'] = sprintf($this->language->get('error_upload_size'), $this->uploadLimit());
		} else {
			$tmpName = html_entity_decode($files['backup']['tmp_name'], ENT_QUOTES, 'UTF-8');
			$error = '';
			$line = 0;

			if ((int) $files['backup']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($tmpName)) {
				$this->session->data['error'] = $this->language->get('error_upload');
			} elseif (($name = $this->model_admin_tool_backup->storeUpload($tmpName, $error, $line)) === false) {
				$this->session->data['error'] = $this->fileError($error, $line);
			} else {
				$this->redirect($this->link('admin/tool/backup/confirm', '&file=' . urlencode($name)));
			}
		}

		// a POST larger than post_max_size arrives with no fields at all
		if (!$files && !$this->request->post) {
			$this->session->data['error'] = sprintf($this->language->get('error_upload_size'), $this->uploadLimit());
		}

		$this->redirect($this->link('admin/tool/backup'));
	}

	// What a restore of the backup would do, and the button to run it
	public function confirm() {
		$this->prepare();

		$name = isset($this->request->get['file']) ? $this->request->get['file'] : '';
		$path = $this->model_admin_tool_backup->path($name);

		if ($path === false || !$this->canWork()) {
			$this->redirect($this->link('admin/tool/backup'));
		}

		$error = '';
		$line = 0;
		$info = $this->model_admin_tool_backup->verify($path, $error, $line);

		if ($info === false) {
			$this->session->data['error'] = $this->fileError($error, $line);

			$this->redirect($this->link('admin/tool/backup'));
		}

		$this->getConfirm($name, $info);
	}

	public function restore() {
		$this->prepare();

		$name = isset($this->request->post['file']) ? $this->request->post['file'] : '';
		$path = $this->model_admin_tool_backup->path($name);

		if (!$this->isPost() || $path === false || !$this->canWork()) {
			$this->redirect($this->link('admin/tool/backup'));
		}

		$back = $this->link('admin/tool/backup/confirm', '&file=' . urlencode($name));

		if (empty($this->request->post['agree'])) {
			$this->session->data['error'] = $this->language->get('error_agree');

			$this->redirect($back);
		}

		$error = '';
		$line = 0;

		if ($this->model_admin_tool_backup->verify($path, $error, $line) === false) {
			$this->session->data['error'] = $this->fileError($error, $line);

			$this->redirect($this->link('admin/tool/backup'));
		}

		// a restore stopped halfway would leave the database half old, half new
		set_time_limit(0);
		ignore_user_abort(true);

		$tables = array();

		foreach ($this->model_admin_tool_backup->tables() as $table) {
			$tables[] = $table['name'];
		}

		try {
			$safety = $this->model_admin_tool_backup->create($tables, 'prerestore');
		} catch (Throwable $e) {
			$this->session->data['error'] = sprintf($this->language->get('error_safety'), htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));

			$this->redirect($back);
		}

		$failure = $this->model_admin_tool_backup->restore($path, $line);

		if ($failure === '') {
			$this->session->data['success'] = sprintf($this->language->get('text_restored'), htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), $safety);
		} else {
			$this->session->data['error'] = sprintf($this->language->get('error_restore'), $line, htmlspecialchars($failure, ENT_QUOTES, 'UTF-8'), $safety);
		}

		$this->redirect($this->link('admin/tool/backup'));
	}

	private function prepare() {
		$this->language->load('admin/tool/backup');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('admin/tool/backup');
	}

	private function isPost() {
		return $this->request->server['REQUEST_METHOD'] === 'POST';
	}

	private function canWork() {
		return $this->model_admin_tool_backup->isSupported() && $this->model_admin_tool_backup->isWritable();
	}

	private function link($route, $query = '') {
		return $this->url->link($route, 'token=' . $this->session->data['token'] . $query, 'SSL');
	}

	private function fileError($error, $line) {
		return sprintf($this->language->get('error_file_' . $error), $line);
	}

	// the smaller of upload_max_filesize and post_max_size, as text (e.g. "8 MB")
	private function uploadLimit() {
		$bytes = array();

		foreach (array('upload_max_filesize', 'post_max_size') as $key) {
			$value = trim((string) ini_get($key));
			$number = (float) $value;

			switch (strtoupper(substr($value, -1))) {
				case 'G': $number *= 1024;
				case 'M': $number *= 1024;
				case 'K': $number *= 1024;
			}

			if ($number > 0) {
				$bytes[] = $number;
			}
		}

		return $bytes ? $this->size(min($bytes)) : '-';
	}

	private function size($bytes) {
		if ($bytes >= 1048576) {
			return number_format($bytes / 1048576, 1) . ' MB';
		}

		return number_format($bytes / 1024, 1) . ' KB';
	}

	private function breadcrumbs($extra = array()) {
		return array_merge(array(
			array('text' => $this->language->get('text_home'), 'href' => $this->link('admin/common/home'), 'separator' => false),
			array('text' => $this->language->get('heading_title'), 'href' => $this->link('admin/tool/backup'), 'separator' => ' :: '),
		), $extra);
	}

	private function messages() {
		$this->data['error_warning'] = '';
		$this->data['success'] = '';

		foreach (array('error' => 'error_warning', 'success' => 'success') as $from => $to) {
			if (isset($this->session->data[$from])) {
				$this->data[$to] = $this->session->data[$from];
				unset($this->session->data[$from]);
			}
		}
	}

	protected function getList() {
		foreach (array(
			'heading_title', 'text_help', 'text_create', 'text_create_help', 'text_upload', 'text_upload_help', 'text_backups',
			'text_no_backups', 'text_confirm_delete', 'text_kind_backup', 'text_kind_prerestore', 'text_kind_upload',
			'column_table', 'column_rows', 'column_size', 'column_date', 'column_kind', 'column_tables', 'column_action',
			'button_create', 'button_upload', 'button_download', 'button_restore', 'button_delete',
		) as $key) {
			$this->data[$key] = $this->language->get($key);
		}

		$model = $this->model_admin_tool_backup;

		$this->data['breadcrumbs'] = $this->breadcrumbs();
		$this->data['notice'] = '';

		if (!$model->isSupported()) {
			$this->data['notice'] = $this->language->get('error_driver');
		} elseif (!$model->isWritable()) {
			$this->data['notice'] = sprintf($this->language->get('error_writable'), htmlspecialchars($model->dir(), ENT_QUOTES, 'UTF-8'));
		}

		$this->data['can_work'] = $this->data['notice'] === '';
		$this->data['database'] = htmlspecialchars($model->databaseName(), ENT_QUOTES, 'UTF-8');
		$this->data['text_upload_limit'] = sprintf($this->language->get('text_upload_limit'), $this->uploadLimit());

		$this->data['action_create'] = $this->link('admin/tool/backup/create');
		$this->data['action_upload'] = $this->link('admin/tool/backup/upload');
		$this->data['action_delete'] = $this->link('admin/tool/backup/delete');

		$this->data['tables'] = array();

		if ($model->isSupported()) {
			try {
				foreach ($model->tables() as $table) {
					$this->data['tables'][] = array(
						'name'    => $table['name'],
						'rows'    => number_format($table['rows'], 0, '', '.'),
						'size'    => $this->size($table['size']),
						'checked' => !in_array($table['name'], $this->unchecked, true),
					);
				}
			} catch (Throwable $e) {
				$this->data['notice'] = sprintf($this->language->get('error_create'), htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
				$this->data['can_work'] = false;
			}
		}

		$this->data['backups'] = array();

		foreach ($model->backups() as $backup) {
			$this->data['backups'][] = array(
				'name'     => $backup['name'],
				'date'     => date($this->language->get('date_format_short') . ' H:i:s', $backup['time']),
				'kind'     => $this->language->get('text_kind_' . $backup['kind']),
				'database' => htmlspecialchars($backup['database'], ENT_QUOTES, 'UTF-8'),
				'tables'   => count($backup['tables']),
				'size'     => $this->size($backup['size']),
				'download' => $this->link('admin/tool/backup/download', '&file=' . urlencode($backup['name'])),
				'restore'  => $this->link('admin/tool/backup/confirm', '&file=' . urlencode($backup['name'])),
			);
		}

		$this->messages();

		$this->template = 'admin/view/tool/backup.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}

	protected function getConfirm($name, $info) {
		foreach (array(
			'heading_title', 'text_confirm_title', 'text_confirm_help', 'text_file', 'text_created_on', 'text_from_database',
			'text_server', 'text_replaced', 'text_untouched', 'text_none', 'text_agree', 'button_restore', 'button_cancel',
		) as $key) {
			$this->data[$key] = $this->language->get($key);
		}

		$current = array();

		foreach ($this->model_admin_tool_backup->tables() as $table) {
			$current[] = $table['name'];
		}

		$database = $this->model_admin_tool_backup->databaseName();

		$this->data['breadcrumbs'] = $this->breadcrumbs(array(
			array('text' => $this->language->get('text_confirm_title'), 'href' => $this->link('admin/tool/backup/confirm', '&file=' . urlencode($name)), 'separator' => ' :: '),
		));

		$this->data['file'] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
		$this->data['created'] = htmlspecialchars($info['created'], ENT_QUOTES, 'UTF-8');
		$this->data['database'] = htmlspecialchars($info['database'], ENT_QUOTES, 'UTF-8');
		$this->data['server'] = htmlspecialchars($info['server'], ENT_QUOTES, 'UTF-8');
		$this->data['other_database'] = ($info['database'] !== $database)
			? sprintf($this->language->get('text_other_database'), htmlspecialchars($info['database'], ENT_QUOTES, 'UTF-8'), htmlspecialchars($database, ENT_QUOTES, 'UTF-8'))
			: '';
		$this->data['replaced'] = $info['tables'];
		$this->data['untouched'] = array_values(array_diff($current, $info['tables']));
		$this->data['text_target'] = sprintf($this->language->get('text_target'), htmlspecialchars($database, ENT_QUOTES, 'UTF-8'));

		$this->data['action'] = $this->link('admin/tool/backup/restore');
		$this->data['cancel'] = $this->link('admin/tool/backup');

		$this->messages();

		$this->template = 'admin/view/tool/backup_confirm.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}
}
?>
