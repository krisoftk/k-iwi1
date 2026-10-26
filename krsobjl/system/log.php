<?php
/**
 * Logger su file: ogni riga scritta viene prefissata con data e ora.
 */
class Log {
	private $filename;

	public function __construct($filename) {
		$this->filename = $filename;
	}

	public function write($message) {
		$handle = fopen(DIR_LOGS . $this->filename, 'a+');

		fwrite($handle, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL);

		fclose($handle);
	}
}
?>
