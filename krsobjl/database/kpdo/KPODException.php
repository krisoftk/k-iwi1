<?php
class KPDOException extends Exception {
	
	function __toString() {
		return __CLASS__ . ": [{$this->code}] {$this->message}\n";		
	}
	 
}