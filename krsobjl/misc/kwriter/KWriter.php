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
	
	// CVS setting
	private $delimiter = ',';
	private $enclosure = '"';
	private $lineEnding = '\r\n';
	
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
				if (isset($this->enclosure) || $this->enclosure != "") {
					fputcsv($this->handle, $row, $this->delimiter, $this->enclosure);
				} else {
					fwrite($this->handle, implode($row, $this->delimiter)."\n");
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
			$output .= "<" . $propname . ">" . $propval . "</" . $propname . ">\n";
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
	    
	    $output .= sprintf("<Worksheet ss:Name=\"%s\">\n", $name);
	    
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
        $item = str_replace('&#039;', '&apos;', htmlspecialchars($item, ENT_QUOTES));
        
        $output = sprintf("<Cell><Data ss:Type=\"%s\">%s</Data></Cell>\n", $type, trim($item));
        
        return $output;
    }
	
	private function createXlsFooter() {
		$output = "</Table>\n</Worksheet>\n";
		$output .= self::XlsFooter;
		
		return $output;
	}
}
?>