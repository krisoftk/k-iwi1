<?php
class ControllerAdminCommonLogout extends Controller {
	public function index() {
		audit_log($this->user, 'logout');

		$this->user->logout();

		unset($this->session->data['token']);

		$this->redirect($this->url->link('admin/common/login', '', 'SSL'));
	}
}
?>
