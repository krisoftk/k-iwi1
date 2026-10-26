<?php
require_once 'KChunkReadFilter.php';
require_once 'KReadFilter.php';

class KReader {
	
	private $startRow = 0;
	private $endRow = 0;
	private $avoidRows = array();
	private $action = null;
	private $iterateOnlyExistingCells = true;
	private $activeSheet = 0;
	
	//CVS setting
	private $delimiter = ',';
	private $enclosure = '"';
	private $lineEnding = '\r\n';
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
		$this->avoidRows = $rows;
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
	
	/*CVS get e set */
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
	
	public function read($file, $type = null) {
		if (empty($file)) {
			trigger_error("No file specified.");
			return false;
		}
		
		if (!file_exists($file)) {
			trigger_error("Could not open " . $file . " for reading! File does not exist.");
			return false;
		}
		
		try {
			$objReader = null;
			
			switch ($type) {
				case "xls":
					$objReader = new PHPExcel_Reader_Excel5();
				break;
				
				case "xlsx":
					$objReader = new PHPExcel_Reader_Excel2007();
				break;
				
				case "csv":
					$objReader = new PHPExcel_Reader_CSV();
				break;
					
				default:
					$objReader = PHPExcel_IOFactory::createReaderForFile($file);
				break;
			}
			
			/** If CSV reader **/
			if ($objReader instanceof PHPExcel_Reader_CSV) {
				$objReader->setDelimiter($this->getDelimiter());
				$objReader->setEnclosure($this->getEnclosure());
				$objReader->setLineEnding($this->getLineEnding());
				$objReader->setInputEncoding($this->getInputEncoding());
			}
			
			/** Get total row **/
			$worksheetData = $objReader->listWorksheetInfo($file);
			$worksheet = $worksheetData[$this->getActiveSheet()];
			
			$totalRows = $worksheet['totalRows'];
			
			/** Create filter**/
			$filter = new KReadFilter();
			$filter->setRows($this->getStartRow(), $totalRows - $this->getEndRow());
			
			/** Set filter to reader **/
			$objReader->setReadFilter($filter);
			
			/** Load $file to a PHPExcel Object  **/
			$objPHPExcel = $objReader->load($file);
			
			$this->setWorksheet($objPHPExcel, $this->getActiveSheet());
			
			if (isset($this->action)) {
				$this->action($objPHPExcel);
			} 
			
			return $objPHPExcel;
		} catch(PHPExcel_Reader_Exception $e) {
			trigger_error('Error loading file: '.$e->getMessage());
		}
		
		return false;
	}
	
	public function readXLS($file) {
		/** Load xls to a PHPExcel Object  **/
		$this->read($file, "xls");
	}
	
	public function readXLSX($file) {
		/** Load xlsx to a PHPExcel Object  **/
		$this->read($file, "xlsx");
	}
	
	public function readCSV($file) {
		/** Load cvs to a PHPExcel Object  **/
		$this->read($file, "csv");	
	}
	
	public function close($objPHPExcel) {
		$objPHPExcel->disconnectWorksheets();
		unset($objPHPExcel);
	}
	
	private function action($objPHPExcel) {
		$objWorksheet = $this->getWorksheet($objPHPExcel);
		
		$i = 0;
		foreach ($objWorksheet->getRowIterator() as $row) {
			$i++;
			
			if (in_array($i, $this->getAvoidRows())) {
				continue;
			}
			
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells($this->getIterateOnlyExistingCells());
	
			$action = $this->action;
			$action($cellIterator, $i, $objWorksheet);
		} // end row getter	
	}
	
	private function getWorksheet($objPHPExcel) {
		return $objPHPExcel->getActiveSheet();
	}
	
	private function setWorksheet($objPHPExcel, $sheet) {
		$objPHPExcel->setActiveSheetIndex($sheet);
	}
}
?>