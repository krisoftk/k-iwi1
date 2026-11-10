<?php
require_once 'KReaderBook.php';
require_once 'KReaderXml.php';
require_once 'KReaderCsv.php';
require_once 'KReaderXlsx.php';
require_once 'KReaderXls.php';
require_once 'KReaderSpreadsheetMl.php';

/*
 * Reads spreadsheets: XLSX, XLS (Excel 97-2003), the XML Spreadsheet 2003
 * written by KWriter, and CSV. Written in plain PHP: it needs no extension
 * besides zlib and iconv. The format is recognised from the content, so
 * an upload's temporary file (no extension) is fine.
 *
 *   $reader = new KReader();
 *   $reader->setAvoidRows(array(1));     // e.g. the header
 *   $reader->setAction(function ($cells, $row, $worksheet) {
 *       $code = $worksheet->getCellByColumnAndRow(0, $row)->getCalculatedValue();
 *   });
 *   $book = $reader->read($file);
 *   $reader->close($book);
 *
 * The callback runs for each row from 1 to the last one with data (rows
 * listed in avoidRows excepted). Only the rows from startRow to the last
 * one minus endRow are read: the others come out empty.
 */
class KReader {
	private $startRow = 0;
	private $endRow = 0;
	private $avoidRows = array();
	private $action = null;
	private $iterateOnlyExistingCells = true;
	private $activeSheet = 0;
	private $chunkSize = 0;

	// CSV settings
	private $delimiter = ',';
	private $enclosure = '"';
	private $lineEnding = "\r\n";
	private $inputEncoding = 'UTF-8';

	public function getStartRow() {
		return $this->startRow;
	}

	public function setStartRow($row) {
		$this->startRow = $row;
	}

	public function getEndRow() {
		return $this->endRow;
	}

	public function setEndRow($row) {
		$this->endRow = $row;
	}

	public function getAvoidRows() {
		return $this->avoidRows;
	}

	public function setAvoidRows($rows) {
		$this->avoidRows = (array) $rows;
	}

	public function setAction($action) {
		$this->action = $action;
	}

	public function getChunkSize() {
		return $this->chunkSize;
	}

	public function getIterateOnlyExistingCells() {
		return $this->iterateOnlyExistingCells;
	}

	public function setIterateOnlyExistingCells($boolean) {
		$this->iterateOnlyExistingCells = $boolean;
	}

	public function getActiveSheet() {
		return $this->activeSheet;
	}

	public function setActiveSheet($sheet) {
		$this->activeSheet = $sheet;
	}

	public function getDelimiter() {
		return $this->delimiter;
	}

	public function setDelimiter($delimiter) {
		$this->delimiter = $delimiter;
	}

	public function getEnclosure() {
		return $this->enclosure;
	}

	public function setEnclosure($enclosure) {
		$this->enclosure = $enclosure;
	}

	// kept for compatibility: line endings are recognised by themselves
	public function getLineEnding() {
		return $this->lineEnding;
	}

	public function setLineEnding($lineEnding) {
		$this->lineEnding = $lineEnding;
	}

	public function getInputEncoding() {
		return $this->inputEncoding;
	}

	public function setInputEncoding($inputEncoding) {
		$this->inputEncoding = $inputEncoding;
	}

	// $type: 'xlsx', 'xls', 'xml' or 'csv'; null recognises it. Returns the
	// workbook (KReaderBook), false if the file can't be read.
	public function read($file, $type = null) {
		if (empty($file)) {
			trigger_error('KReader: no file specified.', E_USER_WARNING);

			return false;
		}

		if (!is_file($file) || !is_readable($file)) {
			trigger_error('KReader: could not open ' . $file . ' for reading.', E_USER_WARNING);

			return false;
		}

		if ($type === null || $type === '') {
			$type = self::detect($file);
		}

		switch (strtolower($type)) {
			case 'xlsx':
				$book = KReaderXlsx::read($file);
				break;
			case 'xls':
				// ".xls" files written by KWriter (and others) are XML
				$book = KReaderSpreadsheetMl::canRead((string) file_get_contents($file, false, null, 0, 4096)) ? KReaderSpreadsheetMl::read($file) : KReaderXls::read($file);
				break;
			case 'xml':
				$book = KReaderSpreadsheetMl::read($file);
				break;
			case 'csv':
				$book = KReaderCsv::read($file, $this->delimiter, $this->enclosure, $this->inputEncoding);
				break;
			default:
				$book = false;
		}

		if (!$book || !$book->getSheet($this->activeSheet)) {
			trigger_error('KReader: ' . $file . ' is not a spreadsheet that can be read.', E_USER_WARNING);

			return false;
		}

		$this->filter($book);
		$book->setActiveSheetIndex($this->activeSheet);

		if (isset($this->action)) {
			$this->run($book->getActiveSheet());
		}

		return $book;
	}

	public function readXLS($file) {
		return $this->read($file, 'xls');
	}

	public function readXLSX($file) {
		return $this->read($file, 'xlsx');
	}

	public function readCSV($file) {
		return $this->read($file, 'csv');
	}

	public function close($book) {
		if ($book instanceof KReaderBook) {
			$book->disconnectWorksheets();
		}
	}

	public static function detect($file) {
		$head = (string) file_get_contents($file, false, null, 0, 4096);

		if (strncmp($head, "PK\x03\x04", 4) === 0) {
			return 'xlsx';
		}

		if (strncmp($head, KReaderXls::SIGNATURE, 8) === 0) {
			return 'xls';
		}

		if (KReaderSpreadsheetMl::canRead($head)) {
			return 'xml';
		}

		return 'csv';
	}

	// keeps, in the active sheet, the rows from startRow to the last one
	// minus endRow
	private function filter($book) {
		$sheet = $book->getSheet($this->activeSheet);
		$last = $sheet->getHighestRow() - (int) $this->endRow;
		$rows = array();

		foreach ($sheet->toArray() as $r => $cells) {
			if ($r >= (int) $this->startRow && $r <= $last) {
				$rows[$r] = $cells;
			}
		}

		$book->setSheet($this->activeSheet, new KReaderSheet($sheet->getTitle(), $rows));
	}

	private function run($sheet) {
		$action = $this->action;
		$avoid = $this->avoidRows;

		for ($r = 1, $last = $sheet->getHighestRow(); $r <= $last; $r++) {
			if (in_array($r, $avoid)) {
				continue;
			}

			$action(new KReaderRow($sheet->getRowCells($r, $this->iterateOnlyExistingCells)), $r, $sheet);
		}
	}
}
?>
