<?php  
class ControllerStandardHome extends Controller {
	public function index() {
		$this->document->setTitle($this->config->get('framework.title_page'));
		$this->document->setDescription($this->config->get('framework.meta_description'));

		$this->data['heading_title'] = $this->config->get('framework.title_application');
		$this->data['lang'] = $this->language->get('code');
		$this->data['direction'] = $this->language->get('direction');
		$this->data['action_logout'] = $this->url->link('standard/logout', '', 'SSL');
		$this->data['action_change_password'] = $this->url->link('standard/home/changePassword', '', 'SSL');
		$this->data['copyright'] = $this->config->get('framework.copyright');

		foreach (array(
			'text_change_password', 'text_logout', 'text_toggle_navigation', 'text_print', 'text_print_alt',
			'text_pdf', 'text_pdf_alt', 'text_mail', 'text_mail_alt', 'entry_current_password',
			'entry_new_password', 'entry_confirm_password', 'button_cancel', 'error_communication',
		) as $key) {
			$this->data[$key] = $this->language->get($key);
		}
		
		// CSS jQuery UI
		$ui = DIR_THEMES . $this->config->get('framework.theme') . '/css/ui/jquery-ui.css';
		if (file_exists($ui)) {
			$this->document->addStyle(makePath('css/ui/jquery-ui.css'));
		} else {
			$this->document->addStyle('css/plugins/ui/readmon/jquery-ui.min.css');
		}
		
		// CSS File Tree
		$file_tree = DIR_THEMES . $this->config->get('framework.theme') . '/css/filetree/jquery.filetree.css';
		if (file_exists($file_tree)) {
			$this->document->addStyle(makePath('css/filetree/jquery.filetree.css'));
		} else {
			$this->document->addStyle('css/plugins/filetree/jquery.filetree.css');
		}
		
		// CSS File Upload
		$upload = DIR_THEMES . $this->config->get('framework.theme') . '/css/fileupload/jquery.fileupload.css';
		if (file_exists($upload)) {
			$this->document->addStyle(makePath('css/fileupload/jquery.fileupload.css'));
		} else {
			$this->document->addStyle('js/plugins/fileupload/css/jquery.fileupload.css');
		}
		
		$upload_ui = DIR_THEMES . $this->config->get('framework.theme') . '/css/fileupload/jquery.fileupload-ui.css';
		if (file_exists($upload_ui)) {
			$this->document->addStyle(makePath('css/fileupload/jquery.fileupload-ui.css'));
		} else {
			$this->document->addStyle('js/plugins/fileupload/css/jquery.fileupload-ui.css');
		}
		
		// CSS Fixed Header Table
		$fixedheadertable = DIR_THEMES . $this->config->get('framework.theme') . '/css/fixedheadertable/jquery.fixedheadertable.css';
		if (file_exists($fixedheadertable)) {
			$this->document->addStyle(makePath('css/fixedheadertable/jquery.fixedheadertable.css'));
		} else {
			$this->document->addStyle('css/plugins/fixedheadertable/jquery.fixedheadertable.css');
		}
		
		// CSS Time Picker
		$timepicker_addon = DIR_THEMES . $this->config->get('framework.theme') . '/css/timepicker/jquery-ui-timepicker-addon.css';
		if (file_exists($timepicker_addon)) {
			$this->document->addStyle(makePath('css/timepicker/jquery-ui-timepicker-addon.css'));
		} else {
			$this->document->addStyle('css/plugins/timepicker/jquery-ui-timepicker-addon.css');
		}
		
		// CSS Two Side Multi Select
		$twosidedmultiselect = DIR_THEMES . $this->config->get('framework.theme') . '/css/twosidedmultiselect/jquery.twosidedmultiselect.css';
		if (file_exists($twosidedmultiselect)) {
			$this->document->addStyle(makePath('css/twosidedmultiselect/jquery.twosidedmultiselect.css'));
		} else {
			$this->document->addStyle('css/plugins/twosidedmultiselect/jquery.twosidedmultiselect.css');
		}
		
		// CSS Color picker
		$colorpicker = DIR_THEMES . $this->config->get('framework.theme') . '/css/colorpicker/jquery.colorpicker.css';
		if (file_exists($colorpicker)) {
			$this->document->addStyle(makePath('css/colorpicker/jquery.colorpicker.css'));
		} else {
			$this->document->addStyle('js/plugins/colorpicker/jquery.colorpicker.css');
		}
		
		//Data Tables
		$dataTables = DIR_THEMES . $this->config->get('framework.theme') . '/css/dataTables/dataTables.bootstrap.css';
		if (file_exists($dataTables)) {
			$this->document->addStyle(makePath('/css/dataTables/dataTables.bootstrap.css'));
		} else {
			$this->document->addStyle('css/plugins/dataTables/dataTables.bootstrap.css');
		}
		
		//Date Time Picker
		$dateTimePicker = DIR_THEMES . $this->config->get('framework.theme') . '/css/datetimepicker/bootstrap-datetimepicker.min.css';
		if (file_exists($dateTimePicker)) {
			$this->document->addStyle(makePath('/css/datetimepicker/bootstrap-datetimepicker.min.css'));
		} else {
			$this->document->addStyle('css/plugins/datetimepicker/bootstrap-datetimepicker.min.css');
		}
		
		//JqPlot
		$jqplot = DIR_THEMES . $this->config->get('framework.theme') . '/css/jqplot/jquery.jqplot.min.css';
		if (file_exists($jqplot)) {
		    $this->document->addStyle(makePath('/css/jqplot/jquery.jqplot.min.css'));
		} else {
		    $this->document->addStyle('js/jqplot/jquery.jqplot.min.css');
		}
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('framework.theme') . '/home.tpl')) {
			$this->template = 'standard/view/themes/' . $this->config->get('framework.theme') . '/home.tpl';
		} else {
			$this->template = 'standard/view/themes/default/home.tpl';
		}
		
		$this->children = array(
			'standard/header',
			'standard/footer'
		);
		
		$this->response->setOutput($this->render());
	}
	
	// Pre-action registered in krsobjp/index.php: denies by default every route
	// to whoever isn't authenticated (session or API key), except the few pages
	// needed to reach the login. Same scheme as the admin side
	// (ControllerAdminCommonHome::login), so new modules, or those made by the
	// generator, are protected from the start without doing anything.
	public function login() {
		if ($this->user->hasApiKey() && !$this->user->isApiAuthenticated()) {
			return $this->forward('standard/home/unauthorized');
		}

		if ($this->isPublicRoute()) {
			return;
		}

		if ($this->user->isLogged()) {
			if (!$this->canRunRoute()) {
				return $this->forward('standard/home/forbidden');
			}

			return;
		}

		if ($this->wantsJson()) {
			return $this->forward('standard/home/unauthorized');
		}

		if (!empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			return $this->forward('error/timeout/kajax');
		}

		$this->redirect($this->url->link('', '', 'SSL'));
	}

	public function unauthorized() {
		http_response_code(401);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(array('result' => false, 'stato' => 'auth', 'id' => 0, 'msg' => $this->language->get('error_unauthorized'))));
	}

	public function forbidden() {
		$message = $this->language->get('error_forbidden');

		if ($this->wantsJson() || $this->user->isApiAuthenticated()) {
			http_response_code(403);

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(array('result' => false, 'stato' => 'auth', 'id' => 0, 'msg' => $message)));

			return;
		}

		// KAJAX calls ignore responses with an error code without showing
		// anything: for them the message comes with a 200, so it shows
		// up in the page content like the other warnings.
		if (empty($this->request->server['HTTP_X_REQUESTED_WITH'])) {
			http_response_code(403);
		}

		$this->response->setOutput('<div class="alert alert-danger">' . $message . '</div>');
	}

	// The menu shows each user only the programs they have permission on; here
	// the same permission applies also to whoever opens the route by hand.
	// Routes that don't match any registered program (APIs, scheduler jobs,
	// ...) only require the login, as long as their module is installed.
	private function canRunRoute() {
		if (!isset($this->request->get['route'])) {
			return true;
		}

		$this->load->model('standard/menu');

		if (!$this->model_standard_menu->is_route_module_available($this->request->get['route'])) {
			return false;
		}

		$progs = $this->model_standard_menu->find_route_progs($this->request->get['route']);

		if (!$progs) {
			return true;
		}

		foreach ($progs as $prog) {
			if ($this->user->hasPermission($prog['ID'])) {
				return true;
			}
		}

		return false;
	}

	// Compares the controller and method the route really resolves to, not
	// the string: "standard//login" or "standard/home/REFRESH" reach the same
	// methods and mustn't be able to get around (or stay out of) the list.
	private function isPublicRoute() {
		if (!isset($this->request->get['route'])) {
			return true;
		}

		$publicRoutes = array(
			'standard/home',
			'standard/home/refresh',
			'standard/login',
			'standard/logout',
			'standard/menu',
			'error/not_found',
			'error/timeout',
			'error/timeout/kajax',
		);

		$requested = new Action($this->request->get['route']);

		foreach ($publicRoutes as $route) {
			$public = new Action($route);

			if ($public->getFile() === $requested->getFile() && strtolower($public->getMethod()) === strtolower($requested->getMethod())) {
				return true;
			}
		}

		return false;
	}

	private function wantsJson() {
		return isset($this->request->server['HTTP_ACCEPT']) && strpos($this->request->server['HTTP_ACCEPT'], 'application/json') !== false;
	}

	public function refresh() {
		$redirect = '
			<script type="text/javascript"><!--
				location.href = "' . $this->url->link('', '', 'SSL') . '";
			//--></script>';

		$this->response->setOutput($redirect);
	}

	// Self-service password change of the logged-in user ("Change password"
	// item in the top-right menu). The current password is verified with the
	// same logic as the login (auth_check_user_login), not just compared with
	// the data in the session, so it keeps working if the hash changes in future.
	public function changePassword() {
		$json = array();

		if (!$this->user->isLogged()) {
			$json['error'] = $this->language->get('error_session');
		} else {
			$current_password = isset($this->request->post['current_password']) ? $this->request->post['current_password'] : '';
			$new_password = isset($this->request->post['new_password']) ? $this->request->post['new_password'] : '';
			$confirm_password = isset($this->request->post['confirm_password']) ? $this->request->post['confirm_password'] : '';

			if (!$this->auth->auth_check_user_login($this->user->getUsername(), $current_password)) {
				if ($this->auth->auth_login_blocked()) {
					$this->language->load('standard/login');

					$json['error'] = sprintf($this->language->get('text_error_attempts'), $this->auth->login_lockout_minutes());
				} else {
					$json['error'] = $this->language->get('error_current_password');
				}
			} elseif (utf8_strlen($new_password) < 8 || strlen($new_password) > 72) {
				// 72 bytes: beyond that, bcrypt (password_hash) ignores the rest
				$json['error'] = $this->language->get('error_new_password');
			} elseif ($new_password != $confirm_password) {
				$json['error'] = $this->language->get('error_confirm_password');
			} else {
				$this->load->model('standard/home');
				$this->model_standard_home->changePassword($this->user->getId(), $new_password);

				$json['success'] = $this->language->get('text_password_changed');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
?>