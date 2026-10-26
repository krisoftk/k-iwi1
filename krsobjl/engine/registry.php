<?php
/**
 * Shared key/value container: it's where the bootstrap publishes the
 * application services (db, session, url, ...) and where Loader,
 * Controller and Model get them through their __get/__set.
 */
final class Registry {
	private $services = array();

	public function set($key, $value) {
		$this->services[$key] = $value;
	}

	public function get($key) {
		return array_key_exists($key, $this->services) ? $this->services[$key] : null;
	}

	public function has($key) {
		return array_key_exists($key, $this->services);
	}
}
?>