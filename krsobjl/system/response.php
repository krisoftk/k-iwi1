<?php
/**
 * Accumula header e corpo della risposta HTTP e li invia in output(),
 * applicando compressione gzip quando richiesta e supportata dal client.
 */
class Response {
	private $headers = array();
	private $compressionLevel = 0;
	private $output;

	public function addHeader($header) {
		$this->headers[] = $header;
	}

	public function redirect($url) {
		header('Location: ' . $url);
		exit();
	}

	public function setCompression($level) {
		$this->compressionLevel = $level;
	}

	public function setOutput($output) {
		$this->output = $output;
	}

	private function acceptsEncoding($encoding) {
		return isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], $encoding) !== false);
	}

	private function compress($data, $level) {
		if ($this->acceptsEncoding('x-gzip')) {
			$encoding = 'x-gzip';
		} elseif ($this->acceptsEncoding('gzip')) {
			$encoding = 'gzip';
		} else {
			return $data;
		}

		if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
			return $data;
		}

		if (headers_sent() || connection_status()) {
			return $data;
		}

		$this->addHeader('Content-Encoding: ' . $encoding);

		return gzencode($data, (int) $level);
	}

	public function output() {
		if (!$this->output) {
			return;
		}

		$body = $this->compressionLevel ? $this->compress($this->output, $this->compressionLevel) : $this->output;

		if (!headers_sent()) {
			foreach ($this->headers as $header) {
				header($header, true);
			}
		}

		echo $body;
	}
}
?>
