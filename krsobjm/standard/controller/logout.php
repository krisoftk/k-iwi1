<?php  
class ControllerStandardLogout extends Controller {
	
	public function index() {
		$this->user->logout();
		
		$this->redirect($this->url->link('standard/home/refresh', '', 'SSL'));
	}
}
?>