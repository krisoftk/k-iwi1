<?php  
class ControllerStandardLogout extends Controller {
	
	// Only with a POST, which the site's pre-action accepts only with the
	// session's CSRF token: with a GET, a link or an image on any other site
	// could log the user out. A GET just goes back to the home page.
	public function index() {
		if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
			$this->redirect($this->url->link('', '', 'SSL'));
		}

		$this->user->logout();
		
		$this->redirect($this->url->link('standard/home/refresh', '', 'SSL'));
	}
}
?>