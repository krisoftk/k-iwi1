<?php
class ControllerAdminCommonHeader extends Controller {
	protected function index() {
		$this->load->language('admin/common/header');

		$secure = isset($this->request->server['HTTPS']) && in_array($this->request->server['HTTPS'], array('on', '1'));

		$this->data['base']        = $secure ? HTTPS_PUBLIC : HTTP_PUBLIC;
		$this->data['title']       = $this->document->getTitle();
		$this->data['description'] = $this->document->getDescription();
		$this->data['keywords']    = $this->document->getKeywords();
		$this->data['links']       = $this->document->getLinks();
		$this->data['styles']      = $this->document->getStyles();
		$this->data['scripts']     = $this->document->getScripts();
		$this->data['lang']        = $this->language->get('code');
		$this->data['direction']   = $this->language->get('direction');

		foreach (array(
			'heading_title', 'text_backup', 'text_confirm', 'text_dashboard', 'text_design',
			'text_documentation', 'text_error_log', 'text_extension', 'text_help', 'text_language',
			'text_theme', 'text_localisation', 'text_logout', 'text_module', 'text_report',
			'text_setting', 'text_system', 'text_support', 'text_user', 'text_user_group',
			'text_users', 'text_framework', 'text_tools', 'text_generator', 'text_import_tables',
			'text_front_end',
		) as $key) {
			$this->data[$key] = $this->language->get($key);
		}

		$loggedIn = $this->isSessionValid();

		if (!$loggedIn) {
			$this->data['logged'] = '';
			$this->data['home']   = $this->url->link('admin/common/login', '', 'SSL');
		} else {
			$token = 'token=' . $this->session->data['token'];

			$this->data['logged']        = sprintf($this->language->get('text_logged'), $this->user->getUsername());
			$this->data['home']          = $this->url->link('admin/common/home', $token, 'SSL');
			$this->data['backup']        = $this->url->link('admin/tool/backup', $token, 'SSL');
			$this->data['error_log']     = $this->url->link('admin/tool/error_log', $token, 'SSL');
			$this->data['language']      = $this->url->link('admin/localisation/language', $token, 'SSL');
			$this->data['theme']         = $this->url->link('admin/design/theme', $token, 'SSL');
			$this->data['logout']        = $this->url->link('admin/common/logout', $token, 'SSL');
			$this->data['module']        = $this->url->link('admin/extension/module', $token, 'SSL');
			$this->data['report']        = $this->url->link('admin/extension/report', $token, 'SSL');
			$this->data['setting']       = $this->url->link('admin/setting/setting', $token, 'SSL');
			$this->data['user']          = $this->url->link('admin/user/user', $token, 'SSL');
			$this->data['user_group']    = $this->url->link('admin/user/group', $token, 'SSL');
			$this->data['generator']     = $this->url->link('admin/tool/generator', $token, 'SSL');
			$this->data['import_tables'] = $this->url->link('admin/tool/import_tables', $token, 'SSL');
			$this->data['front_end']     = HTTP_PUBLIC;
		}

		$this->data['help_links'] = $this->helpLinks();

		$this->template = $this->themeFile('header.tpl');

		$this->render();
	}

	private function isSessionValid() {
		return $this->user->isLogged()
			&& isset($this->request->get['token'])
			&& isset($this->session->data['token'])
			&& ($this->request->get['token'] === $this->session->data['token']);
	}

	// The Help entries come from the [help] section of config.ini: an empty
	// or missing value hides the entry. Only http(s) addresses, so a value
	// like "javascript:..." can't end up in the menu.
	private function helpLinks() {
		$links = array();

		foreach (array(
			'framework'     => $this->language->get('text_framework'),
			'documentation' => $this->language->get('text_documentation'),
			'support'       => $this->language->get('text_support'),
			// named after the author of the installation (framework.author)
			'vendor'        => $this->config->get('framework.author') ? htmlspecialchars($this->config->get('framework.author'), ENT_QUOTES, 'UTF-8') : $this->language->get('text_vendor'),
		) as $key => $text) {
			$href = trim((string) $this->config->get('help.' . $key));

			if (preg_match('#^https?://[^\s"<>]+$#i', $href)) {
				$links[] = array('text' => $text, 'href' => htmlspecialchars($href, ENT_QUOTES, 'UTF-8'));
			}
		}

		return $links;
	}

	private function themeFile($name) {
		$theme = $this->config->get('framework.theme');

		if (file_exists(DIR_TEMPLATE . $theme . '/' . $name)) {
			return 'admin/view/themes/' . $theme . '/' . $name;
		}

		return 'admin/view/themes/default/' . $name;
	}
}
?>
