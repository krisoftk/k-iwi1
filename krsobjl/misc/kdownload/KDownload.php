<?php
class KDownload implements iDownload {
	
	const PDF = 'application/pdf';
	const STREAM = 'application/octet-stream';
	
	public function download($file, $mime_type = null) {
		// check that the file exists
		if (!file_exists($file)) {
			// if it doesn't exist, stop and print an error
			die("Il file non esiste!");
		} else {
			// If the file exists...
			// Set the page headers to force the download of the file
			header("Cache-Control: public");
			header("Content-Description: File Transfer");
			header("Content-Disposition: attachment; filename=" . $this->clean(basename($file)));
			header("Content-Transfer-Encoding: binary");
			
			if ($mime_type) {
				header('Content-Type: ' . $mime_type);
			}
			
			// Read the content of the file
			readfile($file);
		}
	}
	
	private function clean($string) {
		$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		$string = preg_replace('/[^A-Za-z0-9\-_.]/', '', $string); // Removes special chars.
	
		return trim(preg_replace('/-+/', '-', $string)); // Replaces multiple hyphens with single one.
	}
}
?>