<?php
/**
 * Backup and restore of the framework database (database.* in config.ini)
 * for Admin > Tools > Backup / Restore.
 *
 * The backup is a plain .sql file written in PHP (no mysqldump: shared
 * hosting often can't run external programs), with one statement per line
 * and a SHA-256 of the whole content in the last line. A restore only
 * accepts files that pass that check and contain nothing but the
 * statements written here, for the tables listed in the header.
 *
 * It uses its own mysqli connection instead of KPDO: it needs real string
 * escaping, unbuffered reads for large tables and session settings
 * (foreign key checks, sql_mode) that mustn't leak into the framework's
 * connection. MySQL/MariaDB only.
 */
class ModelAdminToolBackup extends Model {
	const FORMAT = 'k-iwi-backup 1';
	// an INSERT line is closed when it reaches this size (max_allowed_packet
	// is at least 4 MB on MySQL and MariaDB)
	const MAX_INSERT = 524288;

	private $link;

	public function dir() {
		return dirname(rtrim(DIR_CONFIG, '/')) . '/backup/';
	}

	public function isWritable() {
		return is_dir($this->dir()) && is_writable($this->dir());
	}

	public function isSupported() {
		return $this->config->get('database.driver') === 'mysql';
	}

	public function databaseName() {
		return (string) $this->config->get('database.name');
	}

	// Base tables of the database (views aren't backed up), with rows and size
	public function tables() {
		$tables = array();
		$result = $this->query('SHOW TABLE STATUS');

		while ($row = $result->fetch_assoc()) {
			if ($row['Engine'] !== null && $this->isTableName($row['Name'])) {
				$tables[] = array(
					'name' => $row['Name'],
					'rows' => (int) $row['Rows'],
					'size' => (int) $row['Data_length'] + (int) $row['Index_length'],
				);
			}
		}

		$result->free();

		return $tables;
	}

	// Writes a backup of the given tables in the backup folder and returns
	// its name. $kind: backup (made by hand), prerestore (automatic, before
	// a restore). On failure the partial file is removed and the exception
	// goes up.
	public function create($tables, $kind = 'backup') {
		$name = $this->newName($kind);
		$path = $this->dir() . $name;
		$file = fopen($path, 'xb');

		if (!$file) {
			throw new RuntimeException('Cannot create ' . $name);
		}

		$hash = hash_init('sha256');
		$write = function ($line) use ($file, $hash) {
			hash_update($hash, $line . "\n");

			if (fwrite($file, $line . "\n") !== strlen($line) + 1) {
				throw new RuntimeException('Write error');
			}
		};

		try {
			$this->connect();

			// InnoDB tables are all read at the same point in time
			$this->query('START TRANSACTION WITH CONSISTENT SNAPSHOT');

			$version = $this->query('SELECT VERSION() AS v')->fetch_assoc();

			$write('-- K-iwi database backup');
			$write('-- format: ' . self::FORMAT);
			$write('-- created: ' . date('Y-m-d H:i:s P'));
			$write('-- database: ' . $this->databaseName());
			$write('-- server: ' . $version['v']);
			$write('-- tables: ' . implode(' ', $tables));
			$write('SET NAMES utf8mb4;');
			$write('SET FOREIGN_KEY_CHECKS = 0;');
			$write('SET UNIQUE_CHECKS = 0;');
			$write("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

			foreach ($tables as $table) {
				$this->writeTable($table, $write);
			}

			$write('SET FOREIGN_KEY_CHECKS = 1;');
			$write('SET UNIQUE_CHECKS = 1;');

			$this->query('COMMIT');

			$end = '-- end: sha256 ' . hash_final($hash);

			if (fwrite($file, $end . "\n") !== strlen($end) + 1 || !fclose($file)) {
				throw new RuntimeException('Write error');
			}
		} catch (Throwable $e) {
			if (is_resource($file)) {
				fclose($file);
			}

			unlink($path);

			throw $e;
		}

		return $name;
	}

	// most recent first, with the details of the header
	public function backups() {
		$backups = array();

		foreach (glob($this->dir() . '*.sql') ?: array() as $path) {
			$name = basename($path);

			if ($this->isBackupName($name)) {
				$backups[] = array_merge($this->header($path), array(
					'name' => $name,
					'kind' => strstr($name, '-', true),
					'time' => filemtime($path),
					'size' => filesize($path),
				));
			}
		}

		usort($backups, function ($a, $b) {
			return ($b['time'] - $a['time']) ?: strcmp($b['name'], $a['name']);
		});

		return $backups;
	}

	// path of a backup of the folder, false if the name isn't one
	public function path($name) {
		if (!is_string($name) || !$this->isBackupName($name) || !is_file($this->dir() . $name)) {
			return false;
		}

		return $this->dir() . $name;
	}

	public function delete($name) {
		$path = $this->path($name);

		return $path !== false && unlink($path);
	}

	// Checks an uploaded file and moves it into the folder: its name, or
	// false with the reason in $error (a code for the language file) and
	// the line in $line.
	public function storeUpload($tmpName, &$error, &$line = 0) {
		if ($this->verify($tmpName, $error, $line) === false) {
			return false;
		}

		$name = $this->newName('upload');

		if (!move_uploaded_file($tmpName, $this->dir() . $name)) {
			$error = 'store';

			return false;
		}

		return $name;
	}

	// Reads the whole file and checks it can be restored: header, SHA-256
	// in the last line, only known statements and only for the tables of
	// the header. Returns the header details, or false with the reason in
	// $error ('format', 'integrity', 'statement' + line number in $line).
	public function verify($path, &$error, &$line = 0) {
		$error = '';
		$line = 0;
		$file = fopen($path, 'rb');

		if (!$file) {
			$error = 'read';

			return false;
		}

		$header = array();
		$hash = hash_init('sha256');
		$end = null;
		$valid = true;

		while (($text = fgets($file)) !== false) {
			$line++;

			if ($end !== null) {
				// something after the closing line
				$valid = false;

				break;
			}

			if (strncmp($text, '-- end: sha256 ', 15) === 0) {
				$end = trim(substr($text, 15));

				continue;
			}

			hash_update($hash, $text);

			if ($line === 1 && $text !== "-- K-iwi database backup\n") {
				$error = 'format';
				fclose($file);

				return false;
			}

			if (preg_match('/^-- ([a-z]+): (.*)$/', rtrim($text, "\n"), $match)) {
				$header += array($match[1] => $match[2]);
			}
		}

		fclose($file);

		if (!isset($header['format']) || $header['format'] !== self::FORMAT || !isset($header['tables'])) {
			$error = 'format';

			return false;
		}

		if (!$valid || $end === null || !hash_equals(hash_final($hash), $end)) {
			$error = 'integrity';

			return false;
		}

		$tables = $header['tables'] === '' ? array() : explode(' ', $header['tables']);

		foreach ($tables as $table) {
			if (!$this->isTableName($table)) {
				$error = 'format';

				return false;
			}
		}

		// second pass, now that the content is known to be the one written:
		// every statement must be one of those this class writes
		$file = fopen($path, 'rb');
		$line = 0;

		while (($text = fgets($file)) !== false) {
			$line++;

			if (!$this->isAllowed(rtrim($text, "\n"), $tables)) {
				fclose($file);
				$error = 'statement';

				return false;
			}
		}

		fclose($file);
		$line = 0;

		return array(
			'created'  => isset($header['created']) ? $header['created'] : '',
			'database' => isset($header['database']) ? $header['database'] : '',
			'server'   => isset($header['server']) ? $header['server'] : '',
			'tables'   => $tables,
		);
	}

	// Runs the statements of a verified backup, one per line. Stops at the
	// first error: returns '' if everything went through, otherwise the
	// error, with the line number in $line. MySQL can't roll back
	// DROP/CREATE TABLE: that's why the caller makes a backup first.
	public function restore($path, &$line = 0) {
		$line = 0;
		$file = fopen($path, 'rb');

		if (!$file) {
			return 'Cannot read the file';
		}

		$this->connect();

		while (($text = fgets($file)) !== false) {
			$line++;
			$text = rtrim($text, "\n");

			if ($text === '' || strncmp($text, '-- ', 3) === 0) {
				continue;
			}

			if ($this->link->query($text) === false) {
				$error = $this->link->error;
				fclose($file);

				return $error;
			}
		}

		fclose($file);
		$line = 0;

		return '';
	}

	// The first lines of a backup ("-- name: value"), for the list
	private function header($path) {
		$header = array('created' => '', 'database' => '', 'tables' => array());
		$file = fopen($path, 'rb');

		for ($i = 0; $file && $i < 10 && ($text = fgets($file, 65536)) !== false; $i++) {
			if (preg_match('/^-- (created|database|tables): (.*)$/', rtrim($text, "\n"), $match)) {
				$header[$match[1]] = ($match[1] === 'tables') ? array_filter(explode(' ', $match[2])) : $match[2];
			}
		}

		if ($file) {
			fclose($file);
		}

		return $header;
	}

	private function writeTable($table, $write) {
		$quoted = '`' . $table . '`';
		$create = $this->query('SHOW CREATE TABLE ' . $quoted)->fetch_row();

		$write('DROP TABLE IF EXISTS ' . $quoted . ';');
		$write($this->oneLine($create[1]) . ';');

		$result = $this->link->query('SELECT * FROM ' . $quoted, MYSQLI_USE_RESULT);

		if ($result === false) {
			throw new RuntimeException($this->link->error);
		}

		$fields = $result->fetch_fields();
		$columns = array();

		foreach ($fields as $field) {
			$columns[] = '`' . str_replace('`', '``', $field->name) . '`';
		}

		$prefix = 'INSERT INTO ' . $quoted . ' (' . implode(', ', $columns) . ') VALUES ';
		$values = '';

		while ($row = $result->fetch_row()) {
			$literals = array();

			foreach ($row as $i => $value) {
				$literals[] = $this->literal($value, $fields[$i]);
			}

			$values .= ($values === '' ? '' : ',') . '(' . implode(',', $literals) . ')';

			if (strlen($values) >= self::MAX_INSERT) {
				$write($prefix . $values . ';');
				$values = '';
			}
		}

		if ($values !== '') {
			$write($prefix . $values . ';');
		}

		if ($this->link->errno) {
			throw new RuntimeException($this->link->error);
		}

		$result->free();
	}

	// Numbers as they are, binary data in hex (so the file stays text),
	// everything else as an escaped string: real_escape_string also escapes
	// line breaks, so every statement stays on one line.
	private function literal($value, $field) {
		static $numeric = array(
			MYSQLI_TYPE_DECIMAL, MYSQLI_TYPE_NEWDECIMAL, MYSQLI_TYPE_TINY, MYSQLI_TYPE_SHORT, MYSQLI_TYPE_LONG,
			MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE, MYSQLI_TYPE_LONGLONG, MYSQLI_TYPE_INT24, MYSQLI_TYPE_YEAR,
			// mysqlnd returns BIT values as integers
			MYSQLI_TYPE_BIT,
		);
		static $temporal = array(
			MYSQLI_TYPE_TIMESTAMP, MYSQLI_TYPE_DATE, MYSQLI_TYPE_TIME, MYSQLI_TYPE_DATETIME, MYSQLI_TYPE_NEWDATE,
		);

		if ($value === null) {
			return 'NULL';
		}

		if (in_array($field->type, $numeric, true) && is_numeric($value)) {
			return $value;
		}

		// charset 63 = binary (BLOB, BINARY, VARBINARY, BIT, geometry); MySQL
		// reports it for JSON too, which doesn't accept binary strings
		if ($field->charsetnr === 63 && !in_array($field->type, $temporal, true) && $field->type !== MYSQLI_TYPE_JSON) {
			return ($value === '') ? "''" : '0x' . bin2hex($value);
		}

		return "'" . $this->link->real_escape_string($value) . "'";
	}

	// SHOW CREATE TABLE puts every column on its own line: the line breaks
	// between the parts become spaces, those inside quoted text (comments,
	// defaults) become \n escapes.
	private function oneLine($sql) {
		$out = '';
		$quote = null;
		$length = strlen($sql);

		for ($i = 0; $i < $length; $i++) {
			$char = $sql[$i];

			if ($quote !== null) {
				if ($char === '\\' && $quote !== '`' && $i + 1 < $length) {
					$out .= $char . $sql[++$i];

					continue;
				}

				if ($char === $quote) {
					$quote = null;
				} elseif ($char === "\n") {
					$char = ($quote === '`') ? ' ' : '\\n';
				} elseif ($char === "\r") {
					$char = ($quote === '`') ? '' : '\\r';
				}
			} elseif ($char === "'" || $char === '"' || $char === '`') {
				$quote = $char;
			} elseif ($char === "\n" || $char === "\r") {
				$char = ' ';
			}

			$out .= $char;
		}

		return preg_replace('/ {2,}/', ' ', $out);
	}

	private function isAllowed($text, $tables) {
		if ($text === '' || strncmp($text, '-- ', 3) === 0) {
			return true;
		}

		if (preg_match("/^SET (NAMES utf8mb4|FOREIGN_KEY_CHECKS = [01]|UNIQUE_CHECKS = [01]|SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO');$/", $text)) {
			return true;
		}

		if (preg_match('/^(?:DROP TABLE IF EXISTS `([A-Za-z0-9_$]+)`;|CREATE TABLE `([A-Za-z0-9_$]+)` \(.*;|INSERT INTO `([A-Za-z0-9_$]+)` \(.*\) VALUES \(.*\);)$/s', $text, $match)) {
			$table = end($match);

			return in_array($table, $tables, true);
		}

		return false;
	}

	private function isTableName($name) {
		return (bool) preg_match('/^[A-Za-z0-9_$]{1,64}$/', $name);
	}

	private function isBackupName($name) {
		return (bool) preg_match('/^(backup|prerestore|upload)-\d{8}-\d{6}-[0-9a-f]{4}\.sql$/', $name);
	}

	private function newName($kind) {
		return $kind . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '.sql';
	}

	private function connect() {
		if ($this->link) {
			return;
		}

		mysqli_report(MYSQLI_REPORT_OFF);

		$port = $this->config->get('database.port');
		$link = @new mysqli(
			$this->config->get('database.host'), $this->config->get('database.user'), $this->config->get('database.password'),
			$this->databaseName(), ($port !== null && $port !== '') ? (int) $port : null
		);

		if (mysqli_connect_error()) {
			throw new RuntimeException(mysqli_connect_error());
		}

		$link->set_charset('utf8mb4');
		$this->link = $link;
	}

	private function query($sql) {
		$this->connect();

		$result = $this->link->query($sql);

		if ($result === false) {
			throw new RuntimeException($this->link->error);
		}

		return $result;
	}
}
?>
