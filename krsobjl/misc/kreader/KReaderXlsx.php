<?php
/*
 * XLSX (Office Open XML) for KReader: a ZIP (read with KZip) holding the
 * workbook, the shared strings and one XML document per sheet. Formulas
 * give the result saved by the program that wrote the file.
 */
class KReaderXlsx {
	const REL_SHEET = '/worksheet';
	const REL_STRINGS = '/sharedStrings';

	public static function read($file) {
		$zip = new KZip();

		if ($zip->open($file) !== true) {
			return false;
		}

		$workbookPath = self::officeDocument($zip);
		$workbook = $workbookPath !== false ? $zip->getFromName($workbookPath) : false;

		if ($workbook === false) {
			$zip->close();

			return false;
		}

		$rels = self::relationships($zip, $workbookPath);
		$strings = array();

		foreach ($rels as $rel) {
			if (substr($rel['type'], -strlen(self::REL_STRINGS)) === self::REL_STRINGS) {
				$strings = self::sharedStrings($zip->getFromName($rel['target']));
			}
		}

		$book = new KReaderBook();

		foreach (KReaderXml::tokens($workbook) as $token) {
			if ($token[0] !== 'open' || KReaderXml::local($token[1]) !== 'sheet') {
				continue;
			}

			$id = KReaderXml::attr($token[2], 'id');

			if (!isset($rels[$id])) {
				continue;
			}

			$xml = $zip->getFromName($rels[$id]['target']);
			$book->addSheet(new KReaderSheet(KReaderXml::attr($token[2], 'name', ''), $xml === false ? array() : self::sheet($xml, $strings)));
		}

		$zip->close();

		return $book->getSheetCount() ? $book : false;
	}

	// path of the workbook, from _rels/.rels
	private static function officeDocument($zip) {
		$rels = $zip->getFromName('_rels/.rels');

		if ($rels !== false) {
			foreach (KReaderXml::tokens($rels) as $token) {
				if ($token[0] === 'open' && KReaderXml::local($token[1]) === 'Relationship'
					&& substr(KReaderXml::attr($token[2], 'Type', ''), -15) === '/officeDocument') {
					return ltrim(KReaderXml::attr($token[2], 'Target', ''), '/');
				}
			}
		}

		return $zip->locateName('xl/workbook.xml') !== false ? 'xl/workbook.xml' : false;
	}

	// id => (type, target as a path in the archive)
	private static function relationships($zip, $part) {
		$dir = dirname($part);
		$dir = $dir === '.' ? '' : $dir . '/';
		$xml = $zip->getFromName($dir . '_rels/' . basename($part) . '.rels');
		$rels = array();

		if ($xml === false) {
			return $rels;
		}

		foreach (KReaderXml::tokens($xml) as $token) {
			if ($token[0] !== 'open' || KReaderXml::local($token[1]) !== 'Relationship') {
				continue;
			}

			$target = KReaderXml::attr($token[2], 'Target', '');
			$target = $target !== '' && $target[0] === '/' ? ltrim($target, '/') : self::normalize($dir . $target);
			$rels[KReaderXml::attr($token[2], 'Id', '')] = array('type' => KReaderXml::attr($token[2], 'Type', ''), 'target' => $target);
		}

		return $rels;
	}

	private static function normalize($path) {
		$parts = array();

		foreach (explode('/', $path) as $part) {
			if ($part === '..') {
				array_pop($parts);
			} elseif ($part !== '' && $part !== '.') {
				$parts[] = $part;
			}
		}

		return implode('/', $parts);
	}

	// the text of each <si>: its <t>, or the <t> of its runs (<r>); the
	// phonetic hints (<rPh>) aren't part of the text
	private static function sharedStrings($xml) {
		$strings = array();

		if ($xml === false) {
			return $strings;
		}

		$current = null;
		$inT = false;
		$skip = 0;

		foreach (KReaderXml::tokens($xml) as $token) {
			$name = isset($token[1]) && $token[0] !== 'text' ? KReaderXml::local($token[1]) : '';

			if ($token[0] === 'open') {
				if ($name === 'si') {
					$current = '';
				} elseif ($name === 'rPh') {
					$skip += $token[3] ? 0 : 1;
				} elseif ($name === 't' && !$token[3]) {
					$inT = true;
				}

				if ($name === 'si' && $token[3]) {
					$strings[] = '';
					$current = null;
				}
			} elseif ($token[0] === 'close') {
				if ($name === 'si') {
					$strings[] = self::unescape($current);
					$current = null;
				} elseif ($name === 'rPh') {
					$skip = max(0, $skip - 1);
				} elseif ($name === 't') {
					$inT = false;
				}
			} elseif ($inT && !$skip && $current !== null) {
				$current .= $token[1];
			}
		}

		return $strings;
	}

	private static function sheet($xml, $strings) {
		$rows = array();
		$row = 0;
		$column = -1;
		$cell = null;   // (type, column)
		$part = null;   // 'v', 'is-t' or 'f' while inside them
		$value = '';
		$inline = '';

		foreach (KReaderXml::tokens($xml) as $token) {
			if ($token[0] === 'text') {
				if ($part === 'v') {
					$value .= $token[1];
				} elseif ($part === 't') {
					$inline .= $token[1];
				}

				continue;
			}

			$name = KReaderXml::local($token[1]);

			if ($token[0] === 'open') {
				if ($name === 'row') {
					$r = KReaderXml::attr($token[2], 'r');
					$row = $r !== null ? (int) $r : $row + 1;
					$column = -1;
				} elseif ($name === 'c') {
					$ref = KReaderXml::attr($token[2], 'r');
					$column = ($ref !== null && preg_match('/^([A-Z]+)(\d*)$/i', $ref, $m)) ? KReaderSheet::columnIndex($m[1]) : $column + 1;

					if ($ref !== null && isset($m[2]) && $m[2] !== '') {
						$row = (int) $m[2];
					}

					$cell = array(KReaderXml::attr($token[2], 't', 'n'), $column);
					$value = '';
					$inline = '';

					if ($token[3]) {
						$cell = null;
					}
				} elseif ($cell !== null && ($name === 'v') && !$token[3]) {
					$part = 'v';
				} elseif ($cell !== null && $name === 't' && !$token[3]) {
					$part = 't';
				}
			} else {
				if ($name === 'v' || $name === 't') {
					$part = null;
				} elseif ($name === 'c' && $cell !== null) {
					$v = self::value($cell[0], $value, $inline, $strings);

					if ($v !== null) {
						$rows[$row][$cell[1]] = $v;
					}

					$cell = null;
				}
			}
		}

		return $rows;
	}

	private static function value($type, $value, $inline, $strings) {
		switch ($type) {
			case 's':
				return $value === '' ? null : (isset($strings[(int) $value]) ? $strings[(int) $value] : '');
			case 'inlineStr':
				return self::unescape($inline);
			case 'str':
			case 'e':
				return self::unescape($value);
			case 'b':
				return $value === '' ? null : (bool) (int) $value;
			case 'd':
				return $value;
			default:
				return $value === '' ? null : (float) $value;
		}
	}

	// characters XML can't hold are written as _xHHHH_
	private static function unescape($text) {
		if (strpos((string) $text, '_x') === false) {
			return (string) $text;
		}

		return preg_replace_callback('/_x([0-9A-Fa-f]{4})_/', function ($m) {
			return KReaderXml::utf8(hexdec($m[1]));
		}, $text);
	}
}
?>
