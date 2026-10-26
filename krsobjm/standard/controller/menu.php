<?php  
class ControllerStandardMenu extends Controller {
	public function index() {
		$this->data['text_no_menu'] = $this->language->get('text_no_menu');
		$this->data['text_not_logged'] = $this->language->get('text_not_logged');

		if (($this->user->isLogged())) {

			$this->load->model('standard/menu');
			
			$this->data['logged'] = true;
			$this->data['menu'] = $this->model_standard_menu->create_menu($this->user, $this->request->post['menu']);
			
			$this->model_standard_menu->create_menu_label();
			$this->data['labels'] = $this->language;
		} else {
			$this->data['logged'] = false;
		}
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('framework.theme') . '/menu.tpl')) {
			$this->template = 'standard/view/themes/' . $this->config->get('framework.theme') . '/menu.tpl';
		} else {
			$this->template = 'standard/view/menu.tpl';
		}
		
		$this->response->setOutput($this->render());
	}
}
?>