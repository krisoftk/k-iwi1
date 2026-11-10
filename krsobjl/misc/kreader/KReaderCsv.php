<?php
/*
 * CSV for KReader: one sheet. Values become numbers when they look like a
 * number without leading zeros (as PHPExcel did: "12,5" or "0123" stay
 * text), empty fields are missing cells.
 */
class KReaderCsv {
	public static function read($file, $delimiter = ',', $enclosure = '"', $encoding = 'UTF-8') {
		$data = file_get_contents($file);

		if ($data === false) {
			return false;
		}

		if (strncmp($data, "\xEF\xBB\xBF", 3) === 0) {
			$data = substr($data, 3);
		} elseif (strncmp($data, "\xFF\xFE", 2) === 0) {
			$data = substr($data, 2);
			$encoding = 'UTF-16LE';
		} elseif (strncmp($data, "\xFE\xFF", 2) === 0) {
			$data = substr($data, 2);
			$encoding = 'UTF-16BE';
		}

		if ($encoding !== '' && strtoupper($encoding) !== 'UTF-8') {
			$converted = @iconv($encoding, 'UTF-8//TRANSLIT', $data);

			if ($converted === false) {
				trigger_error('KReader: the file is not in ' . $encoding, E_USER_WARNING);

				return false;
			}

			$data = $converted;
		}

		$handle = fopen('php://temp', 'r+');
		fwrite($handle, $data);
		rewind($handle);

		$rows = array();
		$r = 0;

		while (($fields = fgetcsv($handle, 0, $delimiter, $enclosure, '')) !== false) {
			$r++;

			foreach ($fields as $c => $value) {
				if ($value !== null && $value !== '') {
					$rows[$r][$c] = self::bind($value);
				}
			}
		}

		fclose($handle);

		$book = new KReaderBook();
		$book->addSheet(new KReaderSheet('Worksheet', $rows));

		return $book;
	}

	public static function bind($value) {
		if (preg_match('/^[+-]?([0-9]+\.?[0-9]*|[0-9]*\.?[0-9]+)([Ee][+-]?[0-2]?\d{1,3})?$/', $value)) {
			$unsigned = ltrim($value, '+-');

			// "0123": a code, not a number
			if (strlen($unsigned) > 1 && $unsigned[0] === '0' && $unsigned[1] !== '.') {
				return $value;
			}

			// an integer too long for PHP: kept as text, so no digit is lost
			if (strpbrk($value, '.eE') === false && strlen($unsigned) >= strlen((string) PHP_INT_MAX) && (float) $unsigned > PHP_INT_MAX) {
				return $value;
			}

			return (float) $value;
		}

		return $value;
	}
}
?>
