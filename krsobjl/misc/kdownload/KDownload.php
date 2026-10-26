<?php
class KDownload implements iDownload {
	
	const PDF = 'application/pdf';
	const STREAM = 'application/octet-stream';
	
	public function download($file, $mime_type = null) {
		// verifico che il file esista
		if (!file_exists($file)) {
			// se non esiste chiudo e stampo un errore
			die("Il file non esiste!");
		} else {
			// Se il file esiste...
			// Imposto gli header della pagina per forzare il download del file
			header("Cache-Control: public");
			header("Content-Description: File Transfer");
			header("Content-Disposition: attachment; filename=" . $this->clean(basename($file)));
			header("Content-Transfer-Encoding: binary");
			
			if ($mime_type) {
				header('Content-Type: ' . $mime_type);
			}
			
			// Leggo il contenuto del file
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