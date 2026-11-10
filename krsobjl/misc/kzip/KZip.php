<?php
/*
 * ZIP archives, read and written in plain PHP: it needs only zlib (no zip
 * extension). The methods follow the part of ZipArchive that applications
 * use, so `new ZipArchive` can become `new KZip`:
 *
 *   $zip = new KZip();
 *   if ($zip->open($file, KZip::CREATE) === true) {
 *       $zip->addFile($path, 'name.xls');
 *       $zip->addFromString('readme.txt', $text);
 *       $zip->close();
 *   }
 *
 *   $zip->open($upload);           // read
 *   $zip->getNames();              // names of the entries
 *   $zip->getFromName('a/b.xml');  // content of one entry, false if missing
 *   $zip->extractTo($dir);         // only regular files, never outside $dir
 *
 * Entries are stored (method 0) or deflated (method 8); ZIP64, encryption
 * and other methods aren't supported: open() returns an error code for them.
 * Changes are written by close(), which rewrites the whole archive.
 */
class KZip {
	// open() flags, same values as ZipArchive
	const CREATE = 1;
	const EXCL = 2;
	const OVERWRITE = 8;

	// open() errors, same values as ZipArchive
	const ER_EXISTS = 10;
	const ER_INCONS = 21;
	const ER_NOENT = 9;
	const ER_OPEN = 11;
	const ER_READ = 5;
	const ER_NOZIP = 19;
	const ER_COMPNOTSUPP = 18;

	// limits against archives that unpack to far more than they weigh
	public $maxEntrySize = 268435456;    // 256 MB per entry
	public $maxTotalSize = 1073741824;   // 1 GB per archive

	public $numFiles = 0;

	private $file = null;
	private $data = '';          // content of the archive read by open()
	private $entries = array();  // name => entry, in archive order
	private $changed = false;

	public function open($file, $flags = 0) {
		$this->file = $file;
		$this->data = '';
		$this->entries = array();
		$this->changed = false;
		$this->numFiles = 0;

		$exists = is_file($file);

		if ($exists && ($flags & self::EXCL)) {
			return self::ER_EXISTS;
		}

		if (!$exists || ($flags & self::OVERWRITE)) {
			if (!$exists && !($flags & (self::CREATE | self::OVERWRITE))) {
				return self::ER_NOENT;
			}

			// a new archive is written by close(), even if empty
			$this->changed = true;

			return true;
		}

		$data = @file_get_contents($file);

		if ($data === false) {
			return self::ER_OPEN;
		}

		$result = $this->readDirectory($data);

		if ($result !== true) {
			$this->file = null;

			return $result;
		}

		$this->data = $data;

		return true;
	}

	public function addFile($path, $name = null) {
		if (!is_file($path) || !is_readable($path)) {
			return false;
		}

		$content = file_get_contents($path);

		if ($content === false) {
			return false;
		}

		return $this->addFromString($name !== null && $name !== '' ? $name : basename($path), $content, filemtime($path));
	}

	public function addFromString($name, $content, $time = null) {
		if ($this->file === null) {
			return false;
		}

		$name = $this->cleanName($name);

		if ($name === '') {
			return false;
		}

		$this->entries[$name] = array(
			'name'    => $name,
			'content' => (string) $content,
			'time'    => $time ? $time : time(),
		);
		$this->numFiles = count($this->entries);
		$this->changed = true;

		return true;
	}

	public function deleteName($name) {
		if (!isset($this->entries[$name])) {
			return false;
		}

		unset($this->entries[$name]);
		$this->numFiles = count($this->entries);
		$this->changed = true;

		return true;
	}

	public function getNames() {
		return array_keys($this->entries);
	}

	public function locateName($name) {
		$index = array_search($name, array_keys($this->entries), true);

		return $index === false ? false : $index;
	}

	public function getNameIndex($index) {
		$names = array_keys($this->entries);

		return isset($names[$index]) ? $names[$index] : false;
	}

	public function getFromName($name) {
		if (!isset($this->entries[$name])) {
			return false;
		}

		return $this->content($this->entries[$name]);
	}

	public function getFromIndex($index) {
		$name = $this->getNameIndex($index);

		return $name === false ? false : $this->getFromName($name);
	}

	// Extracts the regular files (all or the given names) under $dir. Names
	// are cleaned like the ones added: no absolute paths, no "..", so nothing
	// lands outside $dir.
	public function extractTo($dir, $names = null) {
		if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
			return false;
		}

		$base = rtrim(realpath($dir), '/\\');
		$total = 0;

		foreach ($this->entries as $name => $entry) {
			if ($names !== null && !in_array($name, (array) $names, true)) {
				continue;
			}

			$clean = $this->cleanName($name);

			if ($clean === '' || substr($name, -1) === '/') {
				continue;
			}

			$content = $this->content($entry);

			if ($content === false) {
				return false;
			}

			$total += strlen($content);

			if ($total > $this->maxTotalSize) {
				trigger_error('KZip: the archive unpacks to more than ' . $this->maxTotalSize . ' bytes', E_USER_WARNING);

				return false;
			}

			$target = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean);
			$parent = dirname($target);

			if (!is_dir($parent) && !@mkdir($parent, 0777, true)) {
				return false;
			}

			if (strpos(realpath($parent) . DIRECTORY_SEPARATOR, $base . DIRECTORY_SEPARATOR) !== 0 || is_link($target)) {
				return false;
			}

			if (file_put_contents($target, $content) === false) {
				return false;
			}
		}

		return true;
	}

	public function close() {
		if ($this->file === null) {
			return false;
		}

		$result = true;

		if ($this->changed) {
			$result = $this->write($this->file);
		}

		$this->file = null;
		$this->data = '';
		$this->entries = array();
		$this->numFiles = 0;
		$this->changed = false;

		return $result;
	}

	// --- reading -----------------------------------------------------------

	private function readDirectory($data) {
		$length = strlen($data);

		// end of central directory: 22 bytes + a comment of up to 64 KB
		$eocd = strrpos(substr($data, max(0, $length - 65557)), "PK\x05\x06");

		if ($eocd === false) {
			return self::ER_NOZIP;
		}

		$eocd += max(0, $length - 65557);

		if ($eocd + 22 > $length) {
			return self::ER_NOZIP;
		}

		$end = unpack('vdisk/vcdDisk/ventriesDisk/ventries/VcdSize/VcdOffset', substr($data, $eocd + 4, 16));

		if ($end['entries'] == 0xFFFF || $end['cdOffset'] == 0xFFFFFFFF) {
			return self::ER_COMPNOTSUPP;  // ZIP64
		}

		if ($end['disk'] != 0 || $end['cdDisk'] != 0 || $end['cdOffset'] + $end['cdSize'] > $eocd) {
			return self::ER_INCONS;
		}

		$pos = $end['cdOffset'];

		for ($i = 0; $i < $end['entries']; $i++) {
			if ($pos + 46 > $length || substr($data, $pos, 4) !== "PK\x01\x02") {
				return self::ER_INCONS;
			}

			$h = unpack('vmade/vneed/vflags/vmethod/vtime/vdate/Vcrc/Vcsize/Vsize/vnameLen/vextraLen/vcommentLen/vdisk/vattrIn/VattrEx/Voffset', substr($data, $pos + 4, 42));
			$name = substr($data, $pos + 46, $h['nameLen']);

			if ($h['flags'] & 1) {
				return self::ER_COMPNOTSUPP;  // encrypted
			}

			if ($h['method'] != 0 && $h['method'] != 8) {
				return self::ER_COMPNOTSUPP;
			}

			if ($h['size'] == 0xFFFFFFFF || $h['csize'] == 0xFFFFFFFF || $h['offset'] == 0xFFFFFFFF) {
				return self::ER_COMPNOTSUPP;  // ZIP64
			}

			$this->entries[$name] = array(
				'name'   => $name,
				'method' => $h['method'],
				'crc'    => $h['crc'],
				'csize'  => $h['csize'],
				'size'   => $h['size'],
				'offset' => $h['offset'],
				'time'   => $this->fromDos($h['date'], $h['time']),
			);

			$pos += 46 + $h['nameLen'] + $h['extraLen'] + $h['commentLen'];
		}

		$this->numFiles = count($this->entries);

		return true;
	}

	private function content($entry) {
		if (array_key_exists('content', $entry)) {
			return $entry['content'];
		}

		if ($entry['size'] > $this->maxEntrySize) {
			trigger_error('KZip: ' . $entry['name'] . ' is larger than ' . $this->maxEntrySize . ' bytes', E_USER_WARNING);

			return false;
		}

		$pos = $entry['offset'];

		if (substr($this->data, $pos, 4) !== "PK\x03\x04") {
			return false;
		}

		$local = unpack('vnameLen/vextraLen', substr($this->data, $pos + 26, 4));
		$raw = substr($this->data, $pos + 30 + $local['nameLen'] + $local['extraLen'], $entry['csize']);

		if (strlen($raw) != $entry['csize']) {
			return false;
		}

		if ($entry['method'] == 8) {
			$content = @gzinflate($raw, $entry['size'] + 1);
		} else {
			$content = $raw;
		}

		if ($content === false || strlen($content) != $entry['size'] || (crc32($content) & 0xFFFFFFFF) != $entry['crc']) {
			return false;
		}

		return $content;
	}

	// --- writing -----------------------------------------------------------

	private function write($file) {
		$out = '';
		$directory = '';
		$count = 0;

		foreach ($this->entries as $name => $entry) {
			$content = $this->content($entry);

			if ($content === false) {
				return false;
			}

			$crc = crc32($content) & 0xFFFFFFFF;
			$size = strlen($content);
			$packed = gzdeflate($content, 6);
			$method = 8;

			// small or already compressed content: stored as it is
			if ($packed === false || strlen($packed) >= $size) {
				$packed = $content;
				$method = 0;
			}

			if ($size > 0xFFFFFFFE || strlen($out) > 0xFFFFFFFE) {
				trigger_error('KZip: archives over 4 GB are not supported', E_USER_WARNING);

				return false;
			}

			list($date, $time) = $this->toDos($entry['time']);

			$offset = strlen($out);
			$flags = 0x0800;  // names in UTF-8

			$out .= "PK\x03\x04" . pack('vvvvvVVVvv', 20, $flags, $method, $time, $date, $crc, strlen($packed), $size, strlen($name), 0) . $name . $packed;
			$directory .= "PK\x01\x02" . pack('vvvvvvVVVvvvvvVV', 0x0314, 20, $flags, $method, $time, $date, $crc, strlen($packed), $size, strlen($name), 0, 0, 0, 0, 0100644 << 16, $offset) . $name;
			$count++;
		}

		$out .= $directory . "PK\x05\x06" . pack('vvvvVVv', 0, 0, $count, $count, strlen($directory), strlen($out), 0);

		// written aside and then moved, so a failure doesn't leave half an archive
		$temp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';

		if (file_put_contents($temp, $out) === false) {
			return false;
		}

		if (!@rename($temp, $file)) {
			@unlink($temp);

			return false;
		}

		return true;
	}

	// --- helpers -----------------------------------------------------------

	// Relative path with "/" separators, without empty, "." and ".." parts
	private function cleanName($name) {
		$parts = array();

		foreach (explode('/', str_replace('\\', '/', (string) $name)) as $part) {
			if ($part === '' || $part === '.' || $part === '..' || strpos($part, "\0") !== false) {
				continue;
			}

			$parts[] = $part;
		}

		return implode('/', $parts) . (substr((string) $name, -1) === '/' && $parts ? '/' : '');
	}

	private function toDos($timestamp) {
		$d = getdate(max($timestamp, 315532800));  // DOS dates start in 1980

		return array(
			(($d['year'] - 1980) << 9) | ($d['mon'] << 5) | $d['mday'],
			($d['hours'] << 11) | ($d['minutes'] << 5) | intdiv($d['seconds'], 2),
		);
	}

	private function fromDos($date, $time) {
		return mktime($time >> 11, ($time >> 5) & 63, ($time & 31) * 2, ($date >> 5) & 15, $date & 31, ($date >> 9) + 1980);
	}
}
?>
