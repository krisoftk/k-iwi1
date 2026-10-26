<?php
class ControllerAdminExtensionModule extends Controller {
	public function index() {
		$this->load->language('admin/extension/module');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->data['breadcrumbs'] = array(
			array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('admin/common/home', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => false,
			),
			array(
				'text'      => $this->language->get('heading_title'),
				'href'      => $this->url->link('admin/extension/module', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => ' :: ',
			),
		);

		foreach (array('heading_title', 'text_no_results', 'text_confirm', 'column_name', 'column_action') as $key) {
			$this->data[$key] = $this->language->get($key);
		}

		foreach (array('success', 'error') as $flash) {
			$this->data[$flash] = isset($this->session->data[$flash]) ? $this->session->data[$flash] : '';
			unset($this->session->data[$flash]);
		}

		$this->load->model('admin/setting/extension');

		$installed = $this->model_admin_setting_extension->getInstalled();

		$this->data['extensions'] = $this->scanExtensions($installed);

		$this->template = 'admin/view/extension/module.tpl';
		$this->children = array('admin/common/header', 'admin/common/footer');

		$this->response->setOutput($this->render());
	}

	private function scanExtensions($installed) {
		$extensions = array();

		foreach ((array) glob(DIR_MODULE . '*/installation') as $installationDir) {
			$name = basename(dirname($installationDir));

			$isInstalled = in_array($name, $installed);

			$extensions[] = array(
				'name'   => $name,
				'action' => array(
					array(
						'text' => $this->language->get($isInstalled ? 'text_uninstall' : 'text_install'),
						'href' => $this->url->link('admin/extension/module/' . ($isInstalled ? 'uninstall' : 'install'), 'token=' . $this->session->data['token'] . '&extension=' . $name, 'SSL'),
					),
				),
			);
		}

		return $extensions;
	}

	public function install() {
		$this->runInstaller('install');
	}

	public function uninstall() {
		$this->runInstaller('uninstall');
	}

	private function runInstaller($action) {
		$extension = isset($this->request->get['extension']) ? $this->request->get['extension'] : '';

		// the name ends up in a require: only a module name, no paths
		if (!preg_match('/^[A-Za-z0-9_]+$/', $extension) || !is_file(DIR_MODULE . $extension . '/installation/install.php')) {
			$this->redirect($this->url->link('admin/extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$this->load->library('system/install');

		require_once(DIR_MODULE . $extension . '/installation/install.php');

		$installerClass = str_replace('_', '', $extension . 'install');
		$class = new $installerClass($this->registry);

		if (method_exists($class, $action)) {
			$class->$action();
		}

		$this->redirect($this->url->link('admin/extension/module', 'token=' . $this->session->data['token'], 'SSL'));
	}
}
?>
