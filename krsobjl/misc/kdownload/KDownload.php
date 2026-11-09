<?php
/**
 * Sends a file to the browser as a download, for the modules (a report, an
 * export...). The file must be inside $baseDir once resolved with realpath
 * (so also following ".." and symbolic links): a name coming from the
 * request can't reach config.ini or other files of the server. Anything
 * else, a missing file included, answers 404.
 *
 *   $download = new KDownload();
 *   $download->download($this->request->get['file'], DIR_MODULE . 'mymodule/exports', KDownload::PDF);
 */
class KDownload implements iDownload {
	
	const PDF = 'application/pdf';
	const STREAM = 'application/octet-stream';
	
	// $file: path relative to $baseDir (or absolute, inside it); $name: the
	// name the browser saves it as, by default the file's own
	public function download($file, $baseDir, $mimeType = null, $name = null) {
		$path = $this->resolve($file, $baseDir);

		if ($path === false) {
			http_response_code(404);
			exit;
		}

		if ($name === null || $name === '') {
			$name = basename($path);
		}

		while (ob_get_level()) {
			ob_end_clean();
		}

		// private: a proxy must not keep documents meant for one user
		header('Content-Type: ' . ($mimeType ? str_replace(array("\r", "\n"), '', $mimeType) : self::STREAM));
		header('Content-Disposition: attachment; filename="' . $this->clean($name) . '"; filename*=UTF-8\'\'' . rawurlencode($name));
		header('Content-Length: ' . filesize($path));
		header('Cache-Control: private, no-store');
		header('X-Content-Type-Options: nosniff');

		readfile($path);
		exit;
	}

	// absolute path of the file, or false if it doesn't exist or is outside $baseDir
	private function resolve($file, $baseDir) {
		$base = is_string($baseDir) && $baseDir !== '' && strpos($baseDir, "\0") === false ? realpath($baseDir) : false;

		if ($base === false || !is_dir($base)) {
			trigger_error('KDownload: the base folder ' . $baseDir . ' does not exist', E_USER_WARNING);

			return false;
		}

		$file = (string) $file;

		// realpath() with a null byte throws a ValueError
		if ($file === '' || strpos($file, "\0") !== false) {
			return false;
		}

		$path = realpath(($file[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\/]/', $file)) ? $file : $base . DIRECTORY_SEPARATOR . $file);

		if ($path === false || !is_file($path) || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0) {
			return false;
		}

		return $path;
	}
	
	// plain ASCII name for the old filename= parameter (filename* has the real one)
	private function clean($string) {
		$string = str_replace(' ', '-', $string);
		$string = preg_replace('/[^A-Za-z0-9\-_.]/', '', $string);
	
		return trim(preg_replace('/-+/', '-', $string), '-');
	}
}
?>