<?php   
class ControllerErrorNotFound extends Controller {
	public function index() {		
		$this->language->load('error/not_found');

		$this->data['text_error'] = $this->language->get('text_error');
		$this->data['button_continue'] = $this->language->get('button_continue');
		
		//$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . '/1.1 404 Not Found');
		
		$this->data['continue'] = $this->url->link();

		$this->template = 'error/view/not_found.tpl';
		
		$this->response->setOutput($this->render());
  	}
}
?>