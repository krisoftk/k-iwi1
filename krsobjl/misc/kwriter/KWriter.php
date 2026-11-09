<?php
class KWriter {
	// constants for the column types in the xls
	const COLUMN_STRING = 'String';
	const COLUMN_NUMBER = 'Number';
	
	// constants for the writer types
	/**
	 * File CSV
	 */
	const CSV = "csv";
	
	/**
	 * File XLS
	 */
	const XLS = "xls";
	
	/**
	 * MicrosoftXML Header for Excel
	 */
	const XlsHeader = "<?xml version=\"1.0\" encoding=\"%s\"?\>\n<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:html=\"http://www.w3.org/TR/REC-html40\">";
	
	/**
	 * MicrosoftXML Footer for Excel
	 */
	const XlsFooter = "</Workbook>";
	
	// private variables
	private $filename = null;
	private $handle = null;
	private $type = null;
	private $row = 1;
	private $encoding = "UTF-8";
	private $workbookDefault = true;
	
	private $workbookIsOpen = false;
	
	// XLS setting
	private $doc_prop = array(
		'Author' => 'KriFrame',
		'Company' => 'Krisoft S.r.l',
		'Keywords' => '',
		'Version' => '1.0.0',
		'Title' => '',
		'Subject' => '',
		'Comments' => ''
	);
	
	private $columns_type = array();
	
	// CVS setting
	private $delimiter = ',';
	private $enclosure = '"';
	private $lineEnding = "\r\n";
	
	// CSV injection: see setFormulaGuard()
	private $formulaGuard = true;
	
	public function __construct($type = null, $workbookDefault = null) {
		if (isset($type)) {
			$this->type = $type;
		} else {
			$this->type = KWriter::CSV;
		}
		
		if (isset($workbookDefault)) {
		    $this->workbookDefault = $workbookDefault;
		} else {
		    $this->workbookDefault = true;
		}
	}
	
	/* XLS get e set */
	/**
	 * Sets the properties of the XLS file
	 *
	 * @param string $prop
	 * @param string $value
	 */
	public function setProp($prop, $value) {
		if (isset($this->doc_prop[$prop])) {
			$this->doc_prop[$prop] = $value;
		}
	}
	
	public function setColumnsType($types) {
		$this->columns_type = $types;
	}
	
	/* CVS get e set */
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
	
	// A CSV cell starting with = + - @ (or a tab / carriage return) is run as
	// a formula by Excel and LibreOffice: exported data typed by someone
	// (e.g. a customer called =HYPERLINK(...)) would run on the computer of
	// whoever opens the file. Such cells get a leading apostrophe, which the
	// spreadsheet shows as text; numbers (e.g. -5) are left alone. On by
	// default; turn it off only for files read by programs, not people.
	public function setFormulaGuard($on) {
		$this->formulaGuard = (bool) $on;
	}
	
	public function create($filename) {
		$this->filename = $filename;
		
		switch ($this->type) {
			case self::CSV:
				$this->handle = fopen($filename, 'w');
			break;
			
			case self::XLS:
				$this->handle = fopen($filename, 'w');
				fwrite($this->handle, $this->createXlsHeader());
			break;
		}
	}
	
	public function insertWorksheet($name) {
	    fwrite($this->handle, $this->createXlsWorksheet($name));
	}
	
	public function insertRow($row, $columns = null) {
		switch ($this->type) {
			case self::CSV:
				$row = array_map(array($this, 'csvValue'), $row);
				
				if ($this->enclosure !== null && $this->enclosure !== '') {
					fputcsv($this->handle, $row, $this->delimiter, $this->enclosure, '', $this->lineEnding);
				} else {
					fwrite($this->handle, implode($this->delimiter, $row) . $this->lineEnding);
				}
			break;
			
			case self::XLS:
				fwrite($this->handle, $this->createXlsRow($row, $columns));
			break;
		}
		
		$this->row++;
	}
	
	public function save() {
		switch ($this->type) {
			case self::CSV:
				fclose($this->handle);
			break;
					
			case self::XLS:
				fwrite($this->handle, $this->createXlsFooter());
				fclose($this->handle);
			break;
		}
	}
	
	private function createXlsHeader() {
		// create the header
		$output = stripslashes(sprintf(self::XlsHeader, $this->encoding)) . "\n";
		$output .= "<DocumentProperties xmlns=\"urn:schemas-microsoft-com:office:office\">\n";
		$output .= "<Generator><![CDATA[KriFramework by Krisoft S.r.l - http://www.krisoft.it]]></Generator>\n";
		
		foreach ($this->doc_prop as $propname => $propval) {
			$output .= "<" . $propname . ">" . $this->xml($propval) . "</" . $propname . ">\n";
		}
		$output .= "<Created>" . gmdate("Y-m-d\TH:i:s\Z") . "</Created>\n";
		
		$output .= "</DocumentProperties>\n";
		
		// create the worksheet
		if ($this->workbookDefault) {
		    
		    $this->workbookIsOpen = true;
		    
		    $output .= sprintf("<Worksheet ss:Name=\"%s\">\n", "Sheet 1");
		    
		    $output .= "<Table>\n";
		}
		
		return $output;
	}
	
	private function createXlsWorksheet($name) {
	    if ($this->workbookIsOpen) {
	        $output = "</Table>\n</Worksheet>\n";
	    } else {
	        $output = "";
	    }
	    
	    $this->workbookIsOpen = true;
	    
	    $output .= sprintf("<Worksheet ss:Name=\"%s\">\n", $this->xml($this->sheetName($name)));
	    
	    $output .= "<Table>\n";
	    
	    return $output;
	}
	
	private function createXlsRow($row, $columns) {
		$output = "<Row>\n";
		
		foreach ($row as $k => $v) {
			if (isset($columns[$k])) {
				$output .= $this->createXlsCell($v, $columns[$k]);
			} else {
				$output .= $this->createXlsCell($v);
			}
		}
		
		$output .= "</Row>\n";
		
		return $output;
	}
	
	private function createXlsCell($item, $type = 'String') {
        $type = ($type === self::COLUMN_NUMBER && is_numeric(trim((string) $item))) ? self::COLUMN_NUMBER : self::COLUMN_STRING;
        
        $output = sprintf("<Cell><Data ss:Type=\"%s\">%s</Data></Cell>\n", $type, $this->xml(trim((string) $item)));
        
        return $output;
    }
	
	// Values often come from Request (already escaped) or from the database
	// escaped the same way: the entities are decoded first, so the file has
	// "Rossi & Figli" and not "Rossi &amp; Figli" (as esc() does for pages).
	private function decode($value) {
		return html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
	
	private function xml($value) {
		return htmlspecialchars($this->decode($value), ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
	}
	
	private function csvValue($value) {
		$value = $this->decode($value);
		
		if ($this->formulaGuard && $value !== '' && !is_numeric($value) && strpbrk($value[0], "=+-@\t\r") !== false) {
			$value = "'" . $value;
		}
		
		return $value;
	}
	
	// Excel: at most 31 characters, none of []:*?/\
	private function sheetName($name) {
		$name = trim(str_replace(array('[', ']', ':', '*', '?', '/', '\\'), ' ', $this->decode($name)));
		
		return $name === '' ? 'Sheet' : preg_replace('/^(.{31}).+$/us', '$1', $name);
	}
	
	private function createXlsFooter() {
		$output = "</Table>\n</Worksheet>\n";
		$output .= self::XlsFooter;
		
		return $output;
	}
}
?>