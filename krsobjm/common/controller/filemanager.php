<?php
class ControllerCommonFileManager extends Controller {
	private $error = array();

	public function index() {
		if ($this->denyAnonymous()) {
			return;
		}

		$this->language->load('common/filemanager');

		$this->data['title'] = $this->language->get('heading_title');

		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$this->data['base'] = HTTPS_PUBLIC;
		} else {
			$this->data['base'] = HTTP_PUBLIC;
		}

		$this->data['entry_folder'] = $this->language->get('entry_folder');
		$this->data['entry_move'] = $this->language->get('entry_move');
		$this->data['entry_copy'] = $this->language->get('entry_copy');
		$this->data['entry_rename'] = $this->language->get('entry_rename');

		$this->data['button_folder'] = $this->language->get('button_folder');
		$this->data['button_delete'] = $this->language->get('button_delete');
		$this->data['button_move'] = $this->language->get('button_move');
		$this->data['button_copy'] = $this->language->get('button_copy');
		$this->data['button_rename'] = $this->language->get('button_rename');
		$this->data['button_upload'] = $this->language->get('button_upload');
		$this->data['button_refresh'] = $this->language->get('button_refresh');
		$this->data['button_submit'] = $this->language->get('button_submit'); 

		$this->data['error_select'] = $this->language->get('error_select');
		$this->data['error_directory'] = $this->language->get('error_directory');
		
		$this->data['action_files'] = $this->url->link('common/filemanager/files', '', 'SSL');
		$this->data['action_directory'] = $this->url->link('common/filemanager/directory', '', 'SSL');
		$this->data['action_folders'] = $this->url->link('common/filemanager/folders', '', 'SSL');
		$this->data['action_image'] = $this->url->link('common/filemanager/image', '', 'SSL');
		$this->data['action_create'] = $this->url->link('common/filemanager/create', '', 'SSL');
		$this->data['action_delete'] = $this->url->link('common/filemanager/delete', '', 'SSL');
		$this->data['action_move'] = $this->url->link('common/filemanager/move', '', 'SSL');
		$this->data['action_copy'] = $this->url->link('common/filemanager/copy', '', 'SSL');
		$this->data['action_rename'] = $this->url->link('common/filemanager/rename', '', 'SSL');
		$this->data['action_upload'] = $this->url->link('common/filemanager/upload', '', 'SSL');
				
		$this->data['directory'] = HTTP_PUBLIC . 'image/data/';

		$this->load->model('common/image');

		$this->data['no_image'] = $this->model_common_image->resize('no_image.jpg', 100, 100);

		// field and CKEditorFuncNum end up inside the page's JavaScript:
		// only an HTML id and a number are accepted, otherwise a crafted
		// link would run code with the session of whoever opens it
		if (isset($this->request->get['field']) && preg_match('/^[A-Za-z0-9_-]+$/', $this->request->get['field'])) {
			$this->data['field'] = $this->request->get['field'];
		} else {
			$this->data['field'] = '';
		}

		if (isset($this->request->get['CKEditorFuncNum']) && ctype_digit($this->request->get['CKEditorFuncNum'])) {
			$this->data['fckeditor'] = $this->request->get['CKEditorFuncNum'];
		} else {
			$this->data['fckeditor'] = false;
		}

		$this->template = 'common/filemanager.tpl';

		$this->response->setOutput($this->render());
	}	

	public function image() {
		if ($this->denyAnonymous()) {
			return;
		}

		$this->load->model('common/image');

		if (isset($this->request->get['image'])) {
			$image = html_entity_decode($this->request->get['image'], ENT_QUOTES, 'UTF-8');

			if (strpos($image, 'data/') === 0 && $this->resolvePath(substr($image, 5))) {
				$this->response->setOutput($this->model_common_image->resize($image, 100, 100));
			}
		}
	}

	public function directory() {	
		if ($this->denyAnonymous()) {
			return;
		}

		$json = array();

		if (isset($this->request->post['directory']) && ($parent = $this->resolvePath($this->request->post['directory']))) {
			$directories = glob($parent . '/*', GLOB_ONLYDIR);

			if ($directories) {
				$i = 0;

				foreach ($directories as $directory) {
					$json[$i]['data'] = basename($directory);
					$json[$i]['attributes']['directory'] = $this->relativePath($directory);

					$children = glob(rtrim($directory, '/') . '/*', GLOB_ONLYDIR);

					if ($children)  {
						$json[$i]['children'] = ' ';
					}

					$i++;
				}
			}		
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}

	public function files() {
		if ($this->denyAnonymous()) {
			return;
		}

		$json = array();

		$directory = $this->resolvePath(isset($this->request->post['directory']) ? $this->request->post['directory'] : '');

		$allowed = array(
			'.jpg',
			'.jpeg',
			'.png',
			'.gif'
		);

		$files = $directory ? glob($directory . '/*') : array();

		if ($files) {
			foreach ($files as $file) {
				if (is_file($file)) {
					$ext = strrchr($file, '.');
				} else {
					$ext = '';
				}	

				if (in_array(strtolower($ext), $allowed)) {
					$size = filesize($file);

					$i = 0;

					$suffix = array(
						'B',
						'KB',
						'MB',
						'GB',
						'TB',
						'PB',
						'EB',
						'ZB',
						'YB'
					);

					while (($size / 1024) > 1) {
						$size = $size / 1024;
						$i++;
					}

					$json[] = array(
						'filename' => utf8_from_latin1(basename($file)),
						'file'     => $this->relativePath($file),
						'size'     => round(utf8_substr($size, 0, utf8_strpos($size, '.') + 4), 2) . $suffix[$i]
					);
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE));	
	}	

	public function create() {
		if ($this->denyAnonymous()) {
			return;
		}

		$this->language->load('common/filemanager');

		$json = array();

		if (isset($this->request->post['directory'])) {
			if (isset($this->request->post['name']) && ($name = $this->validName($this->request->post['name']))) {
				$directory = $this->resolvePath($this->request->post['directory']);

				if (!$directory || !is_dir($directory)) {
					$json['error'] = $this->language->get('error_directory');
				} elseif (file_exists($directory . '/' . $name)) {
					$json['error'] = $this->language->get('error_exists');
				}
			} else {
				$json['error'] = $this->language->get('error_name');
			}
		} else {
			$json['error'] = $this->language->get('error_directory');
		}

		if (!isset($json['error'])) {	
			mkdir($directory . '/' . $name, 0777);

			$json['success'] = $this->language->get('text_create');
		}	

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function delete() {
		if ($this->denyAnonymous()) {
			return;
		}

		$this->language->load('common/filemanager');

		$json = array();

		if (isset($this->request->post['path'])) {
			$path = $this->resolvePath($this->request->post['path']);

			if (!$path) {
				$json['error'] = $this->language->get('error_select');
			} elseif ($path == $this->rootPath()) {
				$json['error'] = $this->language->get('error_delete');
			}
		} else {
			$json['error'] = $this->language->get('error_select');
		}

		if (!isset($json['error'])) {
			if (is_file($path)) {
				unlink($path);
			} elseif (is_dir($path)) {
				$files = array();

				$path = array($path . '*');

				while(count($path) != 0) {
					$next = array_shift($path);

					foreach(glob($next) as $file) {
						if (is_dir($file)) {
							$path[] = $file . '/*';
						}

						$files[] = $file;
					}
				}

				rsort($files);

				foreach ($files as $file) {
					if (is_file($file)) {
						unlink($file);
					} elseif(is_dir($file)) {
						rmdir($file);	
					} 
				}				
			}

			$json['success'] = $this->language->get('text_delete');
		}				

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function move() {
		if ($this->denyAnonymous()) {
			return;
		}

		$this->language->load('common/filemanager');

		$json = array();

		if (isset($this->request->post['from']) && isset($this->request->post['to'])) {
			$from = $this->resolvePath($this->request->post['from']);
			$to = $this->resolvePath($this->request->post['to']);

			if (!$from) {
				$json['error'] = $this->language->get('error_missing');
			} elseif ($from == $this->rootPath()) {
				$json['error'] = $this->language->get('error_default');
			} elseif (!$to || !is_dir($to)) {
				$json['error'] = $this->language->get('error_move');
			} elseif (file_exists($to . '/' . basename($from))) {
				$json['error'] = $this->language->get('error_exists');
			}
		} else {
			$json['error'] = $this->language->get('error_directory');
		}

		if (!isset($json['error'])) {
			rename($from, $to . '/' . basename($from));

			$json['success'] = $this->language->get('text_move');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}	

	public function copy() {
		if ($this->denyAnonymous()) {
			return;
		}

		$this->language->load('common/filemanager');

		$json = array();

		if (isset($this->request->post['path']) && isset($this->request->post['name'])) {
			if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 255)) {
				$json['error'] = $this->language->get('error_filename');
			}

			$old_name = $this->resolvePath($this->request->post['path']);
			$name = $this->validName($this->request->post['name']);

			if (!$old_name || $old_name == $this->rootPath()) {
				$json['error'] = $this->language->get('error_copy');
			} elseif (!$name) {
				$json['error'] = $this->language->get('error_filename');
			} else {
				// a file keeps its extension: the chosen name can't turn
				// an image into something else (e.g. .php)
				$ext = is_file($old_name) ? strrchr($old_name, '.') : '';

				$new_name = dirname($old_name) . '/' . $name . $ext;

				if (file_exists($new_name)) {
					$json['error'] = $this->language->get('error_exists');
				}
			}
		} else {
			$json['error'] = $this->language->get('error_select');
		}

		if (!isset($json['error'])) {
			if (is_file($old_name)) {
				copy($old_name, $new_name);
			} else {
				$this->recursiveCopy($old_name, $new_name);
			}

			$json['success'] = $this->language->get('text_copy');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));	
	}

	function recursiveCopy($source, $destination) { 
		$directory = opendir($source); 

		@mkdir($destination); 

		while (false !== ($file = readdir($directory))) {
			if (($file != '.') && ($file != '..')) { 
				if (is_dir($source . '/' . $file)) { 
					$this->recursiveCopy($source . '/' . $file, $destination . '/' . $file); 
				} else { 
					copy($source . '/' . $file, $destination . '/' . $file); 
				} 
			} 
		} 

		closedir($directory); 
	} 

	public function folders() {
		if ($this->denyAnonymous()) {
			return;
		}

		$this->response->setOutput($this->recursiveFolders(DIR_IMAGE . 'data/'));	
	}

	protected function recursiveFolders($directory) {
		$output = '';

		$relative = htmlspecialchars($this->relativePath($directory), ENT_QUOTES, 'UTF-8');

		$output .= '<option value="' . $relative . '">' . $relative . '</option>';

		$directories = glob(rtrim($directory, '/') . '/*', GLOB_ONLYDIR);

		foreach ($directories  as $directory) {
			$output .= $this->recursiveFolders($directory);
		}

		return $output;
	}

	public function rename() {
		if ($this->denyAnonymous()) {
			return;
		}

		$this->language->load('common/filemanager');

		$json = array();

		if (isset($this->request->post['path']) && isset($this->request->post['name'])) {
			if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 255)) {
				$json['error'] = $this->language->get('error_filename');
			}

			$old_name = $this->resolvePath($this->request->post['path']);
			$name = $this->validName($this->request->post['name']);

			if (!$old_name || $old_name == $this->rootPath()) {
				$json['error'] = $this->language->get('error_rename');
			} elseif (!$name) {
				$json['error'] = $this->language->get('error_filename');
			} else {
				// a file keeps its extension: the chosen name can't turn
				// an image into something else (e.g. .php)
				$ext = is_file($old_name) ? strrchr($old_name, '.') : '';

				$new_name = dirname($old_name) . '/' . $name . $ext;

				if (file_exists($new_name)) {
					$json['error'] = $this->language->get('error_exists');
				}
			}
		}

		if (!isset($json['error'])) {
			rename($old_name, $new_name);

			$json['success'] = $this->language->get('text_rename');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function upload() {
		if ($this->denyAnonymous()) {
			return;
		}

		$this->language->load('common/filemanager');

		$json = array();

		if (isset($this->request->post['directory'])) {
			if (isset($this->request->files['image']) && $this->request->files['image']['tmp_name']) {
				$filename = $this->validName(basename(html_entity_decode($this->request->files['image']['name'], ENT_QUOTES, 'UTF-8')));

				if (!$filename || (strlen($filename) < 3) || (strlen($filename) > 255)) {
					$json['error'] = $this->language->get('error_filename');
				}

				$directory = $this->resolvePath($this->request->post['directory']);

				if (!$directory || !is_dir($directory)) {
					$json['error'] = $this->language->get('error_directory');
				}

				if ($this->request->files['image']['size'] > 3000000) {
					$json['error'] = $this->language->get('error_file_size');
				}

				// The type declared by the browser ($_FILES['type']) is decided by
				// the sender, so it doesn't count: what counts is the final
				// extension of the name and the real content of the file, which must be an image.
				$allowed = array(
					'.jpg'  => IMAGETYPE_JPEG,
					'.jpeg' => IMAGETYPE_JPEG,
					'.gif'  => IMAGETYPE_GIF,
					'.png'  => IMAGETYPE_PNG
				);

				$ext = $filename ? strtolower(strrchr($filename, '.')) : '';

				if (!isset($allowed[$ext])) {
					$json['error'] = $this->language->get('error_file_type');
				} elseif (is_uploaded_file($this->request->files['image']['tmp_name'])) {
					$info = @getimagesize($this->request->files['image']['tmp_name']);

					if (!$info || $info[2] != $allowed[$ext]) {
						$json['error'] = $this->language->get('error_file_type');
					}
				}

				if ($this->request->files['image']['error'] != UPLOAD_ERR_OK) {
					$json['error'] = 'error_upload_' . $this->request->files['image']['error'];
				}			
			} else {
				$json['error'] = $this->language->get('error_file');
			}
		} else {
			$json['error'] = $this->language->get('error_directory');
		}

		if (!isset($json['error'])) {	
			if (@move_uploaded_file($this->request->files['image']['tmp_name'], $directory . '/' . $filename)) {		
				$json['success'] = $this->language->get('text_uploaded');
			} else {
				$json['error'] = $this->language->get('error_uploaded');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function rootPath() {
		return realpath(DIR_IMAGE . 'data');
	}

	// Every path received from the client is relative to image/data/ and is
	// accepted only if, resolved with realpath (so also following ".." and
	// symbolic links), it stays inside it. The old str_replace('../', '')
	// was bypassed with "....//", which becomes "../" after the replacement.
	// Returns the absolute path, or false if it doesn't exist or gets out.
	private function resolvePath($relative) {
		$root = $this->rootPath();

		if (!$root || !is_string($relative)) {
			return false;
		}

		$relative = html_entity_decode($relative, ENT_QUOTES, 'UTF-8');

		// realpath() with a null byte throws a ValueError (error 500)
		if (strpos($relative, "\0") !== false) {
			return false;
		}

		$path = realpath($root . '/' . $relative);

		if ($path === false || ($path !== $root && strpos($path, $root . DIRECTORY_SEPARATOR) !== 0)) {
			return false;
		}

		return $path;
	}

	private function relativePath($path) {
		return ltrim(substr($path, strlen($this->rootPath())), '/');
	}

	// Name of a file or folder to create: a single element, without
	// separators, without ".." and without hidden files (e.g. .htaccess).
	private function validName($name) {
		$name = trim(html_entity_decode((string) $name, ENT_QUOTES, 'UTF-8'));

		if ($name === '' || $name[0] == '.' || strpbrk($name, "/\\\0") !== false) {
			return false;
		}

		return $name;
	}

	// Defence in depth: the pre-action of krsobjp/index.php already blocks
	// anonymous users, but these operations touch the filesystem (upload,
	// deletion, overwriting) and mustn't depend on a single check. JSON
	// response like the other errors of this file.
	private function denyAnonymous() {
		if ($this->user->isLogged()) {
			return false;
		}

		$this->language->load('common/filemanager');

		http_response_code(403);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(array('error' => $this->language->get('error_login'))));

		return true;
	}
} 
?>