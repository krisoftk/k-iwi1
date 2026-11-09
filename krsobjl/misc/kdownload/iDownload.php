<?php
interface iDownload {
	
	public function download($file, $baseDir, $mimeType = null, $name = null);
	
}