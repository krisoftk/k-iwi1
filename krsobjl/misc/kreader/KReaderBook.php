<?php
/*
 * What KReader gives back: a workbook with its sheets, each sheet with its
 * cells. The names of the methods are the ones of PHPExcel that the
 * applications use, so the import callbacks written for it keep working:
 *
 *   $worksheet->getCellByColumnAndRow(0, $row)->getCalculatedValue()
 *
 * Rows start from 1, columns from 0 (A). Numbers are floats, booleans are
 * booleans, dates are Excel serial numbers (as PHPExcel gives them), text is
 * UTF-8; a missing cell gives null.
 */
class KReaderBook {
	private $sheets = array();
	private $active = 0;

	public function addSheet(KReaderSheet $sheet) {
		$this->sheets[] = $sheet;
	}

	public function setSheet($index, KReaderSheet $sheet) {
		if (isset($this->sheets[$index])) {
			$this->sheets[$index] = $sheet;
		}
	}

	public function getSheetCount() {
		return count($this->sheets);
	}

	public function getSheetNames() {
		$names = array();

		foreach ($this->sheets as $sheet) {
			$names[] = $sheet->getTitle();
		}

		return $names;
	}

	public function getSheet($index) {
		return isset($this->sheets[$index]) ? $this->sheets[$index] : null;
	}

	public function setActiveSheetIndex($index) {
		if (isset($this->sheets[$index])) {
			$this->active = $index;
		}

		return $this->getActiveSheet();
	}

	public function getActiveSheet() {
		return $this->getSheet($this->active);
	}

	public function disconnectWorksheets() {
		$this->sheets = array();
	}
}

class KReaderSheet {
	private $title;
	private $rows;       // row => (column => value), only the cells read
	private $highestRow;
	private $highestColumn;

	public function __construct($title, $rows) {
		ksort($rows);

		$this->title = (string) $title;
		$this->rows = $rows;
		$this->highestRow = $rows ? max(array_keys($rows)) : 1;
		$this->highestColumn = 0;

		foreach ($rows as $cells) {
			if ($cells) {
				$this->highestColumn = max($this->highestColumn, max(array_keys($cells)));
			}
		}
	}

	public function getTitle() {
		return $this->title;
	}

	public function getHighestRow() {
		return $this->highestRow;
	}

	// as a letter, like PHPExcel
	public function getHighestColumn() {
		return KReaderSheet::columnName($this->highestColumn);
	}

	public function getHighestColumnIndex() {
		return $this->highestColumn;
	}

	public function getValue($column, $row) {
		return isset($this->rows[$row][$column]) ? $this->rows[$row][$column] : null;
	}

	public function getCellByColumnAndRow($column, $row) {
		return new KReaderCell($this->getValue((int) $column, (int) $row));
	}

	// "B3"
	public function getCell($coordinate) {
		if (!preg_match('/^\$?([A-Za-z]{1,3})\$?(\d+)$/', (string) $coordinate, $m)) {
			return new KReaderCell(null);
		}

		return $this->getCellByColumnAndRow(KReaderSheet::columnIndex($m[1]), (int) $m[2]);
	}

	// The cells of one row (column => value): only the ones in the file, or
	// all the columns up to the last one of the sheet.
	public function getRowCells($row, $onlyExisting = true) {
		$cells = isset($this->rows[$row]) ? $this->rows[$row] : array();

		if (!$onlyExisting) {
			$all = array();

			for ($c = 0; $c <= $this->highestColumn; $c++) {
				$all[$c] = isset($cells[$c]) ? $cells[$c] : null;
			}

			return $all;
		}

		ksort($cells);

		return $cells;
	}

	// row => (column => value)
	public function toArray() {
		return $this->rows;
	}

	public static function columnIndex($letters) {
		$index = 0;

		foreach (str_split(strtoupper($letters)) as $letter) {
			$index = $index * 26 + (ord($letter) - 64);
		}

		return $index - 1;
	}

	public static function columnName($index) {
		$name = '';

		for ($index++; $index > 0; $index = intdiv($index - 1, 26)) {
			$name = chr(65 + ($index - 1) % 26) . $name;
		}

		return $name;
	}
}

class KReaderCell {
	private $value;

	public function __construct($value) {
		$this->value = $value;
	}

	// Formulas come with the result saved in the file: there's no
	// calculation, the name stays for the callbacks written for PHPExcel.
	public function getCalculatedValue() {
		return $this->value;
	}

	public function getValue() {
		return $this->value;
	}
}

// The cells of a row, passed to the callback as first argument
class KReaderRow implements IteratorAggregate {
	private $cells;

	public function __construct($cells) {
		$this->cells = $cells;
	}

	public function getIterator(): Iterator {
		$cells = array();

		foreach ($this->cells as $column => $value) {
			$cells[$column] = new KReaderCell($value);
		}

		return new ArrayIterator($cells);
	}

	public function toArray() {
		return $this->cells;
	}
}
?>
