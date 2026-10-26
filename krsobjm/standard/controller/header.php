<?php   
class ControllerStandardHeader extends Controller {
	protected function index() {
		$this->data['title'] = $this->document->getTitle();
		
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$this->data['base'] = HTTPS_PUBLIC;
		} else {
			$this->data['base'] = HTTP_PUBLIC;
		}
		
		$this->data['author'] = $this->config->get('framework.author');
		$this->data['description'] = $this->document->getDescription();
		$this->data['keywords'] = $this->document->getKeywords();
		$this->data['links'] = $this->document->getLinks();	 
		$this->data['styles'] = $this->document->getStyles();
		$this->data['scripts'] = $this->document->getScripts();

		if (file_exists(DIR_TEMPLATE . $this->config->get('framework.theme') . '/header.tpl')) {
			$this->template = 'standard/view/themes/' . $this->config->get('framework.theme') . '/header.tpl';
		} else {
			$this->template = 'standard/view/header.tpl';
		}
		
		$this->children = array(
			'standard/header/extras'
		);
		
    	$this->render();
	}
	
	protected function extras() {
		if (file_exists(DIR_TEMPLATE . $this->config->get('framework.theme') . '/header_ext.tpl')) {
			$this->template = 'standard/view/themes/' . $this->config->get('framework.theme') . '/header_ext.tpl';
			
			$this->render();
		}		
	}
}
?>