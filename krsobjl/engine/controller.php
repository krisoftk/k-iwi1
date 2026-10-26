<?php
/**
 * Base for controllers: besides exposing the registry's services, it
 * provides the three tools every controller uses to build a response:
 * forward() (internal route), redirect() (HTTP) and render() (puts the
 * "child" controllers declared in $children into the template).
 */
abstract class Controller {
	protected $registry;

	protected $template;
	protected $children = array();
	protected $data = array();
	protected $output;

	public function __construct($registry) {
		$this->registry = $registry;
	}

	public function __get($service) {
		return $this->registry->get($service);
	}

	public function __set($service, $value) {
		$this->registry->set($service, $value);
	}

	protected function forward($route, $args = array()) {
		return new Action($route, $args);
	}

	protected function redirect($url, $status = 302) {
		header('Status: ' . $status);
		header('Location: ' . str_replace('&amp;', '&', $url));
		exit();
	}

	protected function getChild($route, $args = array()) {
		$action = new Action($route, $args);

		$file   = $action->getFile();
		$class  = $action->getClass();
		$method = $action->getMethod();

		if (!file_exists($file)) {
			trigger_error('Error: Could not load controller ' . $route . '!');
			exit();
		}

		require_once($file);

		$controller = new $class($this->registry);
		$controller->$method($action->getArgs());

		return $controller->output;
	}

	protected function render() {
		foreach ($this->children as $child) {
			$this->data[basename($child)] = $this->getChild($child);
		}

		$view = DIR_MODULE . $this->template;

		if (!file_exists($view)) {
			trigger_error('Error: Could not load template ' . $view . '!');
			exit();
		}

		foreach ($this->data as $__krs_key => $__krs_value) {
			$$__krs_key = $__krs_value;
		}

		ob_start();
		require($view);
		$this->output = ob_get_clean();

		return $this->output;
	}
}
?>
