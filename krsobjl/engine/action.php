<?php
/**
 * Rappresenta l'invocazione risolta di una route: file del controller,
 * classe da istanziare, metodo da chiamare ed eventuali argomenti extra
 * passati esplicitamente (non tramite la route testuale).
 */
final class Action {
	private $file;
	private $class;
	private $method = 'index';
	private $args = array();

	public function __construct($route, $args = array()) {
		$resolved = PathResolver::resolve($route, 'controller');

		$this->file  = $resolved['file'];
		$this->class = 'Controller' . $resolved['class'];

		$remainder = $resolved['remainder'];

		if (!empty($remainder)) {
			$this->method = array_shift($remainder);
		}

		if ($args) {
			$this->args = $args;
		}
	}

	public function getFile() {
		return $this->file;
	}

	public function getClass() {
		return $this->class;
	}

	public function getMethod() {
		return $this->method;
	}

	public function getArgs() {
		return $this->args;
	}
}
?>
