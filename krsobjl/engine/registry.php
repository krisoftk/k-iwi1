<?php
/**
 * Contenitore chiave/valore condiviso: e' il punto in cui il bootstrap
 * pubblica i servizi applicativi (db, session, url, ...) e da cui Loader,
 * Controller e Model li recuperano tramite i loro __get/__set.
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