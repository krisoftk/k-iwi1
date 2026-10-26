<?php
/**
 * Sanitized copy (recursive htmlspecialchars) of the input superglobal
 * arrays, so the rest of the application can read $this->request->get
 * (etc.) without worrying about escaping.
 */
class Request {
	public $get = array();
	public $post = array();
	public $request = array();
	public $cookie = array();
	public $files = array();
	public $server = array();

	public function __construct() {
		$_GET     = $this->sanitize($_GET);
		$_POST    = $this->sanitize($_POST);
		$_REQUEST = $this->sanitize($_REQUEST);
		$_COOKIE  = $this->sanitize($_COOKIE);
		$_FILES   = $this->sanitize($_FILES);
		$_SERVER  = $this->sanitize($_SERVER);

		$this->get     = $_GET;
		$this->post    = $_POST;
		$this->request = $_REQUEST;
		$this->cookie  = $_COOKIE;
		$this->files   = $_FILES;
		$this->server  = $_SERVER;
	}

	public function sanitize($value) {
		if (is_array($value)) {
			$clean = array();

			foreach ($value as $key => $item) {
				$clean[$this->sanitize($key)] = $this->sanitize($item);
			}

			return $clean;
		}

		return htmlspecialchars($value, ENT_COMPAT);
	}
}
?>
