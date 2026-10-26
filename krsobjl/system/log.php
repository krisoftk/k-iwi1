<?php
/**
 * File logger: every line written is prefixed with date and time.
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
