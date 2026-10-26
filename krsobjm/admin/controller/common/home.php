<?php
class ControllerAdminCommonHome extends Controller {
	public function index() {
		$this->load->language('admin/common/home');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->data['heading_title'] = $this->language->get('heading_title');
		$this->data['error_install'] = is_dir(DIR_PUBLIC . 'install') ? $this->language->get('error_install') : '';
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
		$route = $this->currentRouteArea();

		$publicRoutes = array(
			'admin/common/login',
		);

		// La sessione e' la stessa del lato pubblico: essere loggati non basta,
		// l'utente deve appartenere a un gruppo del modulo admin. Il controllo
		// vale a ogni richiesta, quindi chi viene tolto dal gruppo perde
		// subito l'accesso; il token va via per non rimbalzare tra login e home.
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
