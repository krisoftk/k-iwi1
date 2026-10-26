<?php
/**
 * Contenitore per i parametri letti dai file .ini sotto DIR_CONFIG. Le
 * chiavi restano quelle "puntate" gia' usate nei file ini esistenti
 * (es. "database.host", "setting.language").
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
