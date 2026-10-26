<?php
class ControllerAdminCommonFooter extends Controller {
	protected function index() {
		$this->load->language('admin/common/footer');

		$this->data['text_footer'] = sprintf($this->language->get('text_footer'), VERSION);

		$theme = $this->config->get('framework.theme');

		if (file_exists(DIR_TEMPLATE . $theme . '/footer.tpl')) {
			$this->template = 'admin/view/themes/' . $theme . '/footer.tpl';
		} else {
			$this->template = 'admin/view/themes/default/footer.tpl';
		}

		$this->render();
	}
}
?>
