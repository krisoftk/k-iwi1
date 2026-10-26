<?php  
class ControllerStandardLogin extends Controller {
	public function index() {
		$this->language->load('standard/login');

		foreach (array('text_login_hint', 'entry_username', 'entry_password', 'button_login', 'button_logout', 'button_close') as $key) {
			$this->data[$key] = $this->language->get($key);
		}
		
		if (isset($this->session->data['warnig']['timeout'])) {
			$this->data['warnig'] = $this->language->get('warning');
			$this->data['warnig_timeout'] = $this->session->data['warnig']['timeout'];
			
			session_unset();
		} else {
			$this->data['warnig_timeout'] = false;
		}
		
		$this->data['login'] = $this->url->link('standard/login', '', 'SSL');
		
		if(($this->user->isLogged())) {
			$this->data['page_header'] = $this->language->get('text_home');
			
			$this->data['logged'] = true;
			$this->data['user'] = $this->user->getDetails();
			$this->data['logout'] = $this->url->link('standard/logout', '', 'SSL');
		} else {
			$this->data['logged'] = false;
			if (isset($this->request->post['user']) && isset($this->request->post['password'])) {
				$this->data['user'] = $this->request->post['user'];
				// la password non torna mai nella pagina (resterebbe in cache e cronologia)
				$this->data['password'] = '';
			
				if ($this->user->login($this->request->post['user'], $this->request->post['password'])) {
					$this->redirect($this->url->link('standard/home/refresh', '', 'SSL'));
				} elseif ($this->user->isLoginBlocked()) {
					$this->data['error'] = sprintf($this->language->get('text_error_attempts'), $this->user->getLoginLockoutMinutes());
				} else {
					$this->data['error'] = $this->language->get('text_error');
				}
			} else {
				$this->data['user'] = '';
				$this->data['password'] = '';
			}
		}
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('framework.theme') . '/login.tpl')) {
			$this->template = 'standard/view/themes/' . $this->config->get('framework.theme') . '/login.tpl';
		} else {
			$this->template = 'standard/view/login.tpl';
		}
		
		$this->response->setOutput($this->render());
	}
}
?>