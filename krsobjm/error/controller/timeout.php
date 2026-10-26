<?php   
class ControllerErrorTimeout extends Controller {
	public function index() {	
		session_unset();
		
		$this->session->data['warnig']['timeout'] = $this->language->get('warning_timeout');
		
		$redirect = '
			<script type="text/javascript"><!--
				location.href = "' . $this->url->link('', '', 'SSL') . '";
			//--></script>';
				
		$this->response->setOutput($redirect);
  	}
  	
  	public function kajax() {
  		session_unset();
  	
  		$this->session->data['warnig']['timeout'] = $this->language->get('warning_timeout');
  	
  		$json = array();
  		$json['timeout'] = true;
  		$json['location'] = $this->url->link('', '', 'SSL');
  	
  		$this->response->addHeader('Content-Type: application/json');
  		$this->response->setOutput(json_encode($json));
  	}
}
?>