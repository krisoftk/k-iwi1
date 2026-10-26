<?php
class ControllerAdminToolErrorLog extends Controller {
	public function index() {
		$this->load->language('admin/tool/error_log');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->data['heading_title'] = $this->language->get('heading_title');
		$this->data['button_clear']  = $this->language->get('button_clear');

		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}

		$this->data['breadcrumbs'] = array(
			array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('admin/common/home', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => false,
			),
			array(
				'text'      => $this->language->get('heading_title'),
				'href'      => $this->url->link('admin/tool/error_log', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => ' :: ',
			),
		);

		$this->data['clear'] = $this->url->link('admin/tool/error_log/clear', 'token=' . $this->session->data['token'], 'SSL');

		$file = DIR_LOGS . $this->config->get('setting.file_log');

		$this->data['log'] = file_exists($file) ? file_get_contents($file) : '';

		$this->template = 'admin/view/tool/error_log.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}

	public function clear() {
		$this->load->language('admin/tool/error_log');

		$file = fopen(DIR_LOGS . $this->config->get('setting.file_log'), 'w+');
		fclose($file);

		$this->session->data['success'] = $this->language->get('text_success');

		$this->redirect($this->url->link('admin/tool/error_log', 'token=' . $this->session->data['token'], 'SSL'));
	}
}
?>
