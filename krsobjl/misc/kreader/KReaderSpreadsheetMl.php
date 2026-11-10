<?php
/*
 * XML Spreadsheet 2003 (SpreadsheetML) for KReader: the format KWriter
 * writes for its ".xls" files, so an export can be imported back.
 * Rows and cells can skip positions with ss:Index; ss:MergeAcross takes
 * the following columns. DateTime values become Excel serial numbers.
 */
class KReaderSpreadsheetMl {
	public static function canRead($head) {
		return strpos($head, 'urn:schemas-microsoft-com:office:spreadsheet') !== false && stripos($head, '<Workbook') !== false;
	}

	public static function read($file) {
		$xml = file_get_contents($file);

		if ($xml === false) {
			return false;
		}

		$book = new KReaderBook();
		$name = null;
		$rows = array();
		$row = 0;
		$column = 0;
		$type = null;     // type of the <Data> being read
		$value = '';
		$skipText = 0;    // inside <Comment>: its text isn't the value

		foreach (KReaderXml::tokens($xml) as $token) {
			if ($token[0] === 'text') {
				if ($type !== null && !$skipText) {
					$value .= $token[1];
				}

				continue;
			}

			$local = KReaderXml::local($token[1]);

			if ($token[0] === 'open') {
				$a = $token[2];

				switch ($local) {
					case 'Worksheet':
						$name = KReaderXml::attr($a, 'Name', '');
						$rows = array();
						$row = 0;
						break;
					case 'Row':
						$index = KReaderXml::attr($a, 'Index');
						$row = $index !== null ? (int) $index : $row + 1;
						$column = 0;
						break;
					case 'Cell':
						$index = KReaderXml::attr($a, 'Index');
						$column = $index !== null ? (int) $index : $column + 1;
						$merge = (int) KReaderXml::attr($a, 'MergeAcross', 0);
						$current = array($row, $column);
						// the next cell starts after the merged ones
						$column += $merge;
						break;
					case 'Data':
						if (!$token[3]) {
							$type = KReaderXml::attr($a, 'Type', 'String');
							$value = '';
						}
						break;
					case 'Comment':
						$skipText += $token[3] ? 0 : 1;
						break;
				}
			} else {
				if ($local === 'Data' && $type !== null) {
					$rows[$current[0]][$current[1] - 1] = self::value($type, $value);
					$type = null;
				} elseif ($local === 'Comment') {
					$skipText = max(0, $skipText - 1);
				} elseif ($local === 'Worksheet') {
					$book->addSheet(new KReaderSheet($name, $rows));
				}
			}
		}

		return $book->getSheetCount() ? $book : false;
	}

	private static function value($type, $value) {
		switch ($type) {
			case 'Number':
				return (float) $value;
			case 'Boolean':
				return (bool) (int) $value;
			case 'DateTime':
				$time = strtotime(str_replace('T', ' ', $value) . ' UTC');

				// days since 30/12/1899, as Excel counts them
				return $time === false ? $value : ($time / 86400) + 25569;
			case 'Error':
			default:
				return $value;
		}
	}
}
?>
