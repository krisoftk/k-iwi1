<?php
/**
 * Reads and writes config/config.ini for Admin > Settings.
 *
 * The file is edited line by line (comments, order and sections stay where
 * they are) and every save goes through three checks: the text must be
 * valid ini, the values read back must be the wanted ones, and a copy is
 * kept in config/backup/ before overwriting.
 */
class ModelAdminSettingSetting extends Model {
	const MAX_BACKUPS = 10;

	public function path() {
		return DIR_CONFIG . 'config.ini';
	}

	public function read() {
		return (string) file_get_contents($this->path());
	}

	// needs to write the file and the backup folder (or create it)
	public function isWritable() {
		$backup = DIR_CONFIG . 'backup';

		return is_writable($this->path()) && (is_dir($backup) ? is_writable($backup) : is_writable(DIR_CONFIG));
	}

	// Values of the ini text as Config reads them ("section.name" keys),
	// or false with the reason in $error.
	public function parse($text, &$error = null) {
		$error = null;

		set_error_handler(function ($errno, $errstr) use (&$error) {
			$error = $errstr;

			return true;
		});

		$values = parse_ini_string($text, false);

		restore_error_handler();

		if ($values === false && $error === null) {
			$error = 'parse_ini_string';
		}

		return $values;
	}

	// sections and keys in file order: array('database' => array('database.host', ...))
	public function sections($text) {
		$sections = array();
		$current = '';

		foreach ($this->lines($text) as $line) {
			if (preg_match('/^\s*\[([^\]]+)\]/', $line, $match)) {
				$current = trim($match[1]);
				$sections += array($current => array());
			} elseif (($key = $this->lineKey($line)) !== null) {
				$sections[$current][] = $key;
			}
		}

		return $sections;
	}

	// Replaces the values of the given keys, leaving the rest of the file
	// untouched. null removes the line (for optional keys left empty); a new
	// key goes at the end of its section ("setting.x" -> [setting]).
	public function applyValues($text, $values) {
		$eol = (strpos($text, "\r\n") !== false) ? "\r\n" : "\n";
		$lines = $this->lines($text);
		$out = array();
		$seen = array();
		$current = '';
		$lastLine = array();

		foreach ($lines as $line) {
			if (preg_match('/^\s*\[([^\]]+)\]/', $line, $match)) {
				$current = trim($match[1]);
				$out[] = $line;
				$lastLine[$current] = count($out) - 1;

				continue;
			}

			$key = $this->lineKey($line);

			if ($key !== null && array_key_exists($key, $values)) {
				$seen[$key] = true;

				if ($values[$key] === null) {
					continue;
				}

				$line = $this->formatLine($key, $values[$key]);
			}

			$out[] = $line;

			if ($key !== null) {
				$lastLine[$current] = count($out) - 1;
			}
		}

		foreach ($values as $key => $value) {
			if (isset($seen[$key]) || $value === null) {
				continue;
			}

			$section = strstr($key, '.', true);

			if ($section !== false && isset($lastLine[$section])) {
				$at = $lastLine[$section] + 1;

				array_splice($out, $at, 0, array($this->formatLine($key, $value)));

				foreach ($lastLine as $name => $index) {
					if ($index >= $at) {
						$lastLine[$name] = $index + 1;
					}
				}

				$lastLine[$section] = $at;
			} else {
				while ($out && trim(end($out)) === '') {
					array_pop($out);
				}

				$out[] = '';
				$out[] = '[' . $section . ']';
				$out[] = $this->formatLine($key, $value);
				$lastLine[$section] = count($out) - 1;
			}
		}

		return rtrim(implode($eol, $out)) . $eol;
	}

	// Tries the connection with the given settings; '' if it works, otherwise
	// the reason. The mysqli warnings of a failed attempt don't go to the
	// log: the reason ends up in the message anyway.
	public function testDatabase($driver, $host, $port, $user, $password, $name) {
		set_error_handler(function () {
			return true;
		});

		try {
			$db = new KPDO($driver, $host, $port, $user, $password, $name);
			$db->disconnect();

			$error = '';
		} catch (Throwable $e) {
			$error = $e->getMessage();
		}

		restore_error_handler();

		return $error;
	}

	// Safety copy of the current file, then a write on the file itself: a
	// renamed temporary file would be atomic but would change the owner,
	// permissions and ACL of config.ini (it would become the web server's).
	// Without the safety copy nothing is written.
	public function save($text) {
		$dir = DIR_CONFIG . 'backup/';

		if (!is_dir($dir) && !mkdir($dir, 0770)) {
			return false;
		}

		if (!copy($this->path(), $dir . 'config-' . date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '.ini')) {
			return false;
		}

		$backups = $this->backups();

		foreach (array_slice($backups, self::MAX_BACKUPS) as $backup) {
			unlink($dir . $backup['name']);
		}

		return file_put_contents($this->path(), $text, LOCK_EX) === strlen($text);
	}

	// most recent first
	public function backups() {
		$backups = array();

		foreach (glob(DIR_CONFIG . 'backup/config-*.ini') ?: array() as $file) {
			if ($this->isBackupName(basename($file))) {
				$backups[] = array('name' => basename($file), 'time' => filemtime($file), 'size' => filesize($file));
			}
		}

		usort($backups, function ($a, $b) {
			return strcmp($b['name'], $a['name']);
		});

		return $backups;
	}

	public function readBackup($name) {
		if (!$this->isBackupName($name) || !is_file(DIR_CONFIG . 'backup/' . $name)) {
			return false;
		}

		return file_get_contents(DIR_CONFIG . 'backup/' . $name);
	}

	private function isBackupName($name) {
		return (bool) preg_match('/^config-\d{8}-\d{6}-[0-9a-f]{4}\.ini$/', $name);
	}

	private function lines($text) {
		return explode("\n", str_replace("\r\n", "\n", $text));
	}

	private function lineKey($line) {
		if (preg_match('/^\s*([A-Za-z0-9_.\-]+)\s*=/', $line, $match)) {
			return $match[1];
		}

		return null;
	}

	private function formatLine($key, $value) {
		return $key . ' = "' . $value . '"';
	}
}
?>
