<?php
/**
 * Front controller: first runs the registered pre-actions (e.g. the login
 * guard), and if none of them produces an alternative action, runs the
 * requested action, following any forward() returned by the controllers,
 * down to a final "not found" action in case of error.
 */
final class Front {
	private $registry;
	private $preActions = array();
	private $notFound;

	public function __construct($registry) {
		$this->registry = $registry;
	}

	public function addPreAction($action) {
		$this->preActions[] = $action;
	}

	public function dispatch($action, $notFound) {
		$this->notFound = $notFound;

		foreach ($this->preActions as $preAction) {
			$result = $this->invoke($preAction);

			if ($result) {
				$action = $result;
				break;
			}
		}

		while ($action) {
			$action = $this->invoke($action);
		}
	}

	private function invoke($action) {
		$file   = $action->getFile();
		$class  = $action->getClass();
		$method = $action->getMethod();
		$args   = $action->getArgs();

		if (!file_exists($file)) {
			return $this->consumeNotFound();
		}

		require_once($file);

		$controller = new $class($this->registry);

		if (!is_callable(array($controller, $method))) {
			return $this->consumeNotFound();
		}

		return call_user_func_array(array($controller, $method), $args);
	}

	private function consumeNotFound() {
		$notFound = $this->notFound;
		$this->notFound = null;

		return $notFound;
	}
}
?>
