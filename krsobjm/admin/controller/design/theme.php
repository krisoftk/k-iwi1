<?php
/**
 * Admin > System > Themes: the installed themes of the site and of the admin,
 * which one is active and whether each one has all its files. Activating a
 * theme writes framework.theme / framework.theme_admin in config.ini through
 * the same save as Admin > Settings (backup copy, owner and permissions kept).
 */
class ControllerAdminDesignTheme extends Controller {
	// config.ini key, folder of the templates and of the public files of each area
	private $areas = array(
		'site'  => array('key' => 'framework.theme',       'templates' => 'standard/view/themes/', 'assets' => 'themes/'),
		'admin' => array('key' => 'framework.theme_admin', 'templates' => 'admin/view/themes/',    'assets' => 'themes/admin/'),
	);

	public function index() {
		$this->prepare();

		$this->getList();
	}

	public function activate() {
		$this->prepare();

		$model = $this->model_admin_setting_setting;
		$area = isset($this->request->post['area']) && is_string($this->request->post['area']) ? $this->request->post['area'] : '';
		$theme = isset($this->request->post['theme']) && is_string($this->request->post['theme']) ? $this->request->post['theme'] : '';

		if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !isset($this->areas[$area]) || !in_array($theme, $this->themes($area), true)) {
			$this->session->data['error'] = $this->language->get('error_theme');
		} elseif (!$model->isWritable()) {
			$this->session->data['error'] = sprintf($this->language->get('text_readonly'), $model->path());
		} else {
			$key = $this->areas[$area]['key'];
			$text = $model->read();
			$new = $model->applyValues($text, array($key => $theme));
			$parsed = $model->parse($new);

			// as in Settings: the value read back must be the wanted one
			if (!is_array($parsed) || !isset($parsed[$key]) || (string) $parsed[$key] !== $theme) {
				$this->session->data['error'] = $this->language->get('error_theme');
			} elseif ($new !== $text && !$model->save($new)) {
				$this->session->data['error'] = $this->language->get('error_save');
			} else {
				$this->session->data['success'] = sprintf($this->language->get('text_success_' . $area), htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'));
			}
		}

		$this->redirect($this->link('admin/design/theme'));
	}

	private function prepare() {
		$this->language->load('admin/design/theme');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('admin/setting/setting');
	}

	private function link($route, $query = '') {
		return $this->url->link($route, 'token=' . $this->session->data['token'] . $query, 'SSL');
	}

	// The theme folders of an area. In the site's public folder "admin" holds
	// the admin themes, so it can't be a site theme.
	private function themes($area) {
		$themes = array();

		foreach (glob(DIR_MODULE . $this->areas[$area]['templates'] . '*', GLOB_ONLYDIR) ?: array() as $path) {
			$name = basename($path);

			if (preg_match('/^[A-Za-z0-9_.-]+$/', $name) && !($area === 'site' && $name === 'admin')) {
				$themes[] = $name;
			}
		}

		sort($themes);

		return $themes;
	}

	// What's wrong with a theme: its public folder is missing, or a template
	// it uses points to files that aren't there. The templates the theme
	// doesn't have come from the area's base folder (standard/view/*.tpl).
	private function problems($area, $theme) {
		$assets = DIR_PUBLIC . $this->areas[$area]['assets'] . $theme . '/';

		if (!is_dir($assets)) {
			return array(sprintf($this->language->get('text_missing_folder'), 'krsobjp/' . $this->areas[$area]['assets'] . $theme . '/'));
		}

		$templates = array();

		foreach (glob(DIR_MODULE . $this->areas[$area]['templates'] . $theme . '/*.tpl') ?: array() as $file) {
			$templates[basename($file)] = $file;
		}

		if ($area === 'site') {
			foreach (glob(DIR_MODULE . 'standard/view/*.tpl') ?: array() as $file) {
				$templates += array(basename($file) => $file);
			}
		}

		$missing = array();

		foreach ($templates as $file) {
			$source = file_get_contents($file);

			// makePath('css/x.css') is resolved in the theme's public folder,
			// "themes/<name>/..." links point to that folder directly
			preg_match_all('/makePath\(\s*[\'"]\/?([^\'"$]+)[\'"]\s*\)/', $source, $paths);
			preg_match_all('#["\']themes/([A-Za-z0-9_.-]+)/([^"\'?$]+)#', $source, $links, PREG_SET_ORDER);

			foreach ($paths[1] as $path) {
				if (!is_file($assets . $path)) {
					$missing[] = 'krsobjp/' . $this->areas[$area]['assets'] . $theme . '/' . $path;
				}
			}

			foreach ($links as $link) {
				if (!is_file(DIR_PUBLIC . 'themes/' . $link[1] . '/' . $link[2])) {
					$missing[] = 'krsobjp/themes/' . $link[1] . '/' . $link[2];
				}
			}
		}

		$problems = array();

		foreach (array_unique($missing) as $path) {
			$problems[] = sprintf($this->language->get('text_missing_file'), htmlspecialchars($path, ENT_QUOTES, 'UTF-8'));
		}

		return $problems;
	}

	// Optional preview.jpg / preview.png in the theme's public folder
	private function preview($area, $theme) {
		$secure = isset($this->request->server['HTTPS']) && in_array($this->request->server['HTTPS'], array('on', '1'));

		foreach (array('preview.jpg', 'preview.png') as $name) {
			$file = DIR_PUBLIC . $this->areas[$area]['assets'] . $theme . '/' . $name;

			if (is_file($file)) {
				return ($secure ? HTTPS_PUBLIC : HTTP_PUBLIC) . $this->areas[$area]['assets'] . rawurlencode($theme) . '/' . $name . '?v=' . filemtime($file);
			}
		}

		return '';
	}

	protected function getList() {
		foreach (array(
			'heading_title', 'text_help', 'text_active', 'text_complete', 'text_no_preview', 'text_templates',
			'text_confirm_incomplete', 'button_activate',
		) as $key) {
			$this->data[$key] = $this->language->get($key);
		}

		$model = $this->model_admin_setting_setting;

		// the values of the file: in the admin framework.theme is replaced
		// with framework.theme_admin at startup (krsobjp/admin/index.php)
		$current = $model->parse($model->read());
		$current = is_array($current) ? $current : array();

		$this->data['writable'] = $model->isWritable();
		$this->data['readonly_warning'] = $this->data['writable'] ? '' : sprintf($this->language->get('text_readonly'), $model->path());

		$this->data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->link('admin/common/home'), 'separator' => false),
			array('text' => $this->language->get('heading_title'), 'href' => $this->link('admin/design/theme'), 'separator' => ' :: '),
		);

		$this->data['action'] = $this->link('admin/design/theme/activate');
		$this->data['areas'] = array();

		foreach ($this->areas as $area => $info) {
			$active = isset($current[$info['key']]) ? (string) $current[$info['key']] : '';
			$themes = array();

			foreach ($this->themes($area) as $theme) {
				$themes[] = array(
					'name'      => htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'),
					'active'    => $theme === $active,
					'preview'   => $this->preview($area, $theme),
					'templates' => count(glob(DIR_MODULE . $info['templates'] . $theme . '/*.tpl') ?: array()),
					'problems'  => $this->problems($area, $theme),
				);
			}

			$this->data['areas'][] = array(
				'area'   => $area,
				'title'  => $this->language->get('tab_' . $area),
				'key'    => $info['key'],
				'themes' => $themes,
				// a value of config.ini without a folder: the code falls back to another theme
				'unknown' => ($active !== '' && !in_array($active, $this->themes($area), true))
					? sprintf($this->language->get('text_unknown'), htmlspecialchars($active, ENT_QUOTES, 'UTF-8'))
					: '',
			);
		}

		$this->data['error_warning'] = '';
		$this->data['success'] = '';

		foreach (array('error' => 'error_warning', 'success' => 'success') as $from => $to) {
			if (isset($this->session->data[$from])) {
				$this->data[$to] = $this->session->data[$from];
				unset($this->session->data[$from]);
			}
		}

		$this->template = 'admin/view/design/theme_list.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}
}
?>
