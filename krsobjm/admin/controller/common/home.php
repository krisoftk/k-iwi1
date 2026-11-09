<?php
class ControllerAdminCommonHome extends Controller {
	public function index() {
		$this->load->language('admin/common/home');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->data['heading_title'] = $this->language->get('heading_title');
		// the installer locks itself once K-iwi is installed (see krsobjp/install/index.php):
		// leaving it is a note for production servers, not an error
		$this->data['text_install'] = is_dir(DIR_PUBLIC . 'install') ? $this->language->get('text_install') : '';
		$this->data['error_logs']    = $this->checkLogsWritable() ? '' : sprintf($this->language->get('error_logs'), DIR_LOGS);

		$this->data['breadcrumbs'] = array(
			array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('admin/common/home', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => false,
			),
		);

		$this->data['token'] = $this->session->data['token'];

		$theme = $this->config->get('framework.theme');

		if (file_exists(DIR_TEMPLATE . $theme . '/home.tpl')) {
			$this->template = 'admin/view/themes/' . $theme . '/home.tpl';
		} else {
			$this->template = 'admin/view/themes/default/home.tpl';
		}

		$this->children = array(
			'admin/common/header',
			'admin/common/footer',
		);

		$this->response->setOutput($this->render());
	}

	private function checkLogsWritable() {
		$probe = DIR_LOGS . 'test';

		$handle = @fopen($probe, 'a+');

		if (!$handle) {
			return false;
		}

		fclose($handle);
		unlink($probe);

		return true;
	}

	public function login() {
		// Only the admin's own routes (and the error pages) run from this
		// entry point: the site's controllers and the installer expect the
		// site's checks, not these.
		if (isset($this->request->get['route']) && !in_array(PathResolver::area($this->request->get['route']), array('admin', 'error'), true)) {
			return $this->forward('error/not_found');
		}

		$route = $this->currentRouteArea();

		$publicRoutes = array(
			'admin/common/login',
		);

		// The session is the same as the public side's: being logged in isn't
		// enough, the user must belong to a group of the admin module. The check
		// runs on every request, so whoever is removed from the group loses access
		// right away; the token goes away so it doesn't bounce between login and home.
		if (!$this->user->canAccessModule('admin') && !in_array($route, $publicRoutes)) {
			unset($this->session->data['token']);

			return $this->forward('admin/common/login');
		}

		$tokenExemptRoutes = array(
			'admin/common/login',
			'admin/common/logout',
			'error/not_found',
		);

		$hasRoute = isset($this->request->get['route']);

		if ($hasRoute && in_array($route, $tokenExemptRoutes)) {
			return;
		}

		if (!$this->hasValidToken()) {
			return $this->forward('admin/common/login');
		}

		// Logged in with the installer's default password (see
		// ControllerAdminCommonLogin::attemptLogin): only the user's own form
		// (and the logout) until a new password is saved there.
		if (!empty($this->session->data['must_change_password']) && !$this->isOwnPasswordForm($route)) {
			$this->redirect($this->url->link('admin/user/user/update', 'token=' . $this->session->data['token'] . '&user_id=' . $this->user->getId(), 'SSL'));
		}
	}

	private function isOwnPasswordForm($route) {
		if ($route === 'admin/common/logout') {
			return true;
		}

		$requested = new Action(isset($this->request->get['route']) ? $this->request->get['route'] : '');
		$form = new Action('admin/user/user/update');

		return $requested->getFile() === $form->getFile()
			&& strtolower($requested->getMethod()) === 'update'
			&& isset($this->request->get['user_id'])
			&& (string) $this->request->get['user_id'] === (string) $this->user->getId();
	}

	private function currentRouteArea() {
		if (!isset($this->request->get['route'])) {
			return '';
		}

		$segments = array_slice(explode('/', $this->request->get['route']), 0, 3);

		return implode('/', $segments);
	}

	private function hasValidToken() {
		return isset($this->request->get['token'])
			&& isset($this->session->data['token'])
			&& ($this->request->get['token'] === $this->session->data['token']);
	}
}
?>
