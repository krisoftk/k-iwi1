<?php   
class ControllerStandardFooter extends Controller {
	
	protected function index() {
		$this->load->model('common/image');
		
		$id_body = "#content";
		$id_info = "#info";
		$loader = '<div class="navbar-brand" id="loading">&nbsp;</div>';
		 
		$this->data['kajax'] = initializeKAJAX($id_body, $id_info, $loader);
		$this->data['menu'] = $this->url->link('standard/menu', '', 'SSL');
		$this->data['login'] = $this->url->link('standard/login', '', 'SSL');

		if (file_exists(DIR_TEMPLATE . $this->config->get('framework.theme') . '/footer.tpl')) {
			$this->template = 'standard/view/themes/' . $this->config->get('framework.theme') . '/footer.tpl';
		} else {
			$this->template = 'standard/view/footer.tpl';
		}
		
    	$this->render();
	}
}
?>