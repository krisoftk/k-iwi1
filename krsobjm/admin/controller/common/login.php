<?php
class ControllerAdminCommonLogin extends Controller {
	private $warning;

	public function index() {
		$this->load->language('admin/common/login');

		$this->document->setTitle($this->language->get('heading_title'));

		if ($this->isAlreadyLoggedIn()) {
			$this->redirect($this->url->link('admin/common/home', 'token=' . $this->session->data['token'], 'SSL'));
		}

		if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->attemptLogin()) {
			$this->session->data['token'] = bin2hex(random_bytes(16));

			// Request applies htmlspecialchars to all the input: without decoding,
			// the URL parameters would arrive as &amp;
			$redirect = isset($this->request->post['redirect']) ? html_entity_decode($this->request->post['redirect'], ENT_QUOTES, 'UTF-8') : '';

			if ($redirect && $this->isLocalUrl($redirect)) {
				$this->redirect($redirect . '&token=' . $this->session->data['token']);
			}

			$this->redirect($this->url->link('admin/common/home', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$this->data['heading_title']  = $this->language->get('heading_title');
		$this->data['text_login']     = $this->language->get('text_login');
		$this->data['entry_username'] = $this->language->get('entry_username');
		$this->data['entry_password'] = $this->language->get('entry_password');
		$this->data['button_login']   = $this->language->get('button_login');

		if ($this->hasStaleToken()) {
			$this->warning = $this->language->get('error_token');
		}

		$this->data['error_warning'] = $this->warning ? $this->warning : '';

		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}

		$this->data['action']   = $this->url->link('admin/common/login', '', 'SSL');
		$this->data['username'] = isset($this->request->post['username']) ? $this->request->post['username'] : '';
		// the password never goes back into the page (it would stay in cache and history)
		$this->data['password'] = '';
		$this->data['redirect'] = $this->buildRedirectUrl();

		$theme = $this->config->get('framework.theme');

		if (file_exists(DIR_TEMPLATE . $theme . '/login.tpl')) {
			$this->template = 'admin/view/themes/' . $theme . '/login.tpl';
		} else {
			$this->template = 'admin/view/themes/default/login.tpl';
		}

		$this->children = array(
			'admin/common/header',
			'admin/common/footer',
		);

		$this->response->setOutput($this->render());
	}

	private function isAlreadyLoggedIn() {
		return $this->user->canAccessModule('admin')
			&& isset($this->request->get['token'])
			&& isset($this->session->data['token'])
			&& ($this->request->get['token'] === $this->session->data['token']);
	}

	private function hasStaleToken() {
		if (isset($this->session->data['token']) && !isset($this->request->get['token'])) {
			return true;
		}

		return isset($this->request->get['token'])
			&& isset($this->session->data['token'])
			&& ($this->request->get['token'] !== $this->session->data['token']);
	}

	private function buildRedirectUrl() {
		if (!isset($this->request->get['route'])) {
			return '';
		}

		$route = $this->request->get['route'];

		$remainingArgs = $this->request->get;
		unset($remainingArgs['route']);
		unset($remainingArgs['token']);

		$query = $remainingArgs ? http_build_query($remainingArgs) : '';

		return $this->url->link($route, $query, 'SSL');
	}

	// The redirect field comes from the form, so anyone can put an external
	// address in it: after the login we only go back to a page of this site,
	// otherwise the user (and the token at the end of the URL) would end up elsewhere.
	private function isLocalUrl($url) {
		return strpos($url, HTTPS_SERVER) === 0 || strpos($url, HTTP_SERVER) === 0;
	}

	private function attemptLogin() {
		if (!isset($this->request->post['username'], $this->request->post['password'])) {
			return false;
		}

		// only users of the admin module's groups; the others get the same
		// message as wrong credentials, without saying that they are right
		if (!$this->user->login($this->request->post['username'], $this->request->post['password'], 'admin')) {
			$this->warning = $this->user->isLoginBlocked() ? sprintf($this->language->get('error_attempts'), $this->user->getLoginLockoutMinutes()) : $this->language->get('error_login');

			return false;
		}

		return true;
	}
}
?>
