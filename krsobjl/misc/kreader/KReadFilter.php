<?php
/**  Define a Read Filter class implementing PHPExcel_Reader_IReadFilter  */
class KReadFilter implements PHPExcel_Reader_IReadFilter
{
	private $_startRow = 0;
	private $_endRow   = 0;

	/**  Set the list of rows that we want to read  */
	public function setRows($startRow, $endRow) {
		$this->_startRow = $startRow;
		$this->_endRow   = $endRow;
	}

	public function readCell($column, $row, $worksheetName = '') {
		//  Reads the rows between start and end that aren't in the array of rows to skip
		if ($row >= $this->_startRow && $row <= $this->_endRow) {
			return true;
		}
		return false;
	}
}
?>