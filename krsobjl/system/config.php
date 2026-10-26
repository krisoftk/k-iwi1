<?php
/**
 * Container for the parameters read from the .ini files under DIR_CONFIG.
 * The keys stay the "dotted" ones already used in the existing ini files
 * (e.g. "database.host", "setting.language").
 */
class Config {
	private $items = array();

	public function get($key) {
		return isset($this->items[$key]) ? $this->items[$key] : null;
	}

	public function set($key, $value) {
		$this->items[$key] = $value;
	}

	public function has($key) {
		return isset($this->items[$key]);
	}

	public function load($filename) {
		$file = DIR_CONFIG . $filename . '.ini';

		if (!file_exists($file)) {
			trigger_error('Error: Could not load config ' . $filename . '!');
			exit();
		}

		$values = @parse_ini_file($file, false);

		if ($values === false) {
			die('Errore nel file di configurazione ' . $filename . '.ini');
		}

		foreach ($values as $key => $value) {
			$this->items[$key] = $value;
		}
	}
}
?>
