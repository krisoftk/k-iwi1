<?php
/**
 * Pacchetto di lingua: tutti i testi di una lingua, di tutti i moduli, in un
 * solo file JSON da scaricare, tradurre e ricaricare.
 *
 *   {"format": "k-iwi-language", "version": 1,
 *    "language": {"name": "Italiano", "code": "it_IT", "directory": "italian"},
 *    "files": {"admin": {...}, "admin/common/header": {"text_logout": "Disconnetti", ...}}}
 *
 * Ogni voce di "files" e' un file di krsobjm/<modulo>/language/<cartella>/:
 * "admin/common/header" e' admin/language/<cartella>/common/header.php, mentre
 * il solo nome del modulo ("admin") indica il file principale, che su disco
 * si chiama come la lingua (italian.php, english.php...). Cosi' un pacchetto
 * scaricato dall'italiano si ricarica cosi' com'e' su un'altra lingua.
 *
 * Al caricamento non si esegue nulla di quanto ricevuto: il JSON viene
 * validato (solo testi) e i file PHP li riscrive il server con var_export.
 */
class ModelAdminLocalisationLanguagepack extends Model {
	const FORMAT = 'k-iwi-language';
	const MAX_ERRORS = 10;

	public function export($language) {
		$files = array();

		foreach ($this->modules() as $module) {
			$base = $this->languageDir($module, $language);

			if (!is_dir($base)) {
				continue;
			}

			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));

			foreach ($iterator as $file) {
				if ($file->getExtension() !== 'php') {
					continue;
				}

				$relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($base), -4));
				$key = ($relative === $language['FILENAME']) ? $module : $module . '/' . $relative;

				$files[$key] = $this->readStrings($file->getPathname());
			}
		}

		ksort($files);

		return $files;
	}

	public function toJson($language) {
		$pack = array(
			'format'   => self::FORMAT,
			'version'  => 1,
			'language' => array(
				'name'      => html_entity_decode($language['NAME'], ENT_QUOTES, 'UTF-8'),
				'code'      => $language['CODE'],
				'directory' => $language['DIRECTORY'],
			),
			'files'    => $this->export($language),
		);

		return json_encode($pack, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
	}

	// Restituisce i file validati, oppure false con i motivi in $errors.
	public function parse($json, &$errors) {
		$errors = array();
		$pack = json_decode($json, true);

		if (!is_array($pack) || !isset($pack['format']) || $pack['format'] !== self::FORMAT || !isset($pack['files']) || !is_array($pack['files'])) {
			$errors[] = 'format';

			return false;
		}

		$modules = $this->modules();
		$files = array();

		foreach ($pack['files'] as $key => $strings) {
			if (count($errors) >= self::MAX_ERRORS) {
				break;
			}

			$key = (string) $key;

			if (!preg_match('#^[A-Za-z0-9_]+(/[A-Za-z0-9_]+){0,5}$#', $key) || !in_array(strtok($key, '/'), $modules, true)) {
				$errors[] = array('file', $key);

				continue;
			}

			if (!is_array($strings) || !$strings) {
				$errors[] = array('strings', $key);

				continue;
			}

			$clean = array();

			foreach ($strings as $name => $text) {
				if (!is_string($name) || !preg_match('/^[^\x00-\x1f\x7f]{1,128}$/u', $name) || !(is_string($text) || is_int($text) || is_float($text))) {
					$errors[] = array('string', $key . ' / ' . $name);

					continue 2;
				}

				$clean[$name] = (string) $text;
			}

			$files[$key] = $clean;
		}

		if (!$files && !$errors) {
			$errors[] = 'empty';
		}

		return $errors ? false : $files;
	}

	// Prima controlla che si possa scrivere ogni file, poi scrive: un
	// pacchetto non finisce mai caricato a meta' per un problema di permessi.
	// Restituisce le cartelle non scrivibili (vuoto se e' andato tutto bene).
	public function import($language, $files) {
		$targets = array();
		$blocked = array();

		foreach ($files as $key => $strings) {
			$path = $this->targetPath($language, $key);
			$dir = $this->firstExistingDir(dirname($path));

			if (!is_writable($dir) || (file_exists($path) && !is_writable($path))) {
				$blocked[$dir] = $dir;
			}

			$targets[$path] = $strings;
		}

		if ($blocked) {
			return array_values($blocked);
		}

		foreach ($targets as $path => $strings) {
			if (!is_dir(dirname($path))) {
				mkdir(dirname($path), 0775, true);
			}

			$tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';

			file_put_contents($tmp, $this->toPhp($strings));
			rename($tmp, $path);
		}

		return array();
	}

	// numero di file (testi) presenti per la lingua, per il confronto con il default
	public function countFiles($language) {
		return count($this->export($language));
	}

	private function toPhp($strings) {
		$php = "<?php\n// Generato da Admin > Lingue: per modificarlo scarica il pacchetto, cambialo e ricaricalo.\n";

		foreach ($strings as $name => $text) {
			$php .= '$_[' . var_export($name, true) . '] = ' . var_export($text, true) . ";\n";
		}

		return $php . "?>\n";
	}

	private function targetPath($language, $key) {
		$segments = explode('/', $key);
		$module = array_shift($segments);
		$relative = $segments ? implode('/', $segments) : $language['FILENAME'];

		return $this->languageDir($module, $language) . $relative . '.php';
	}

	private function languageDir($module, $language) {
		return DIR_MODULE . $module . '/language/' . $language['DIRECTORY'] . '/';
	}

	private function firstExistingDir($dir) {
		while (!is_dir($dir) && dirname($dir) !== $dir) {
			$dir = dirname($dir);
		}

		return $dir;
	}

	// i moduli sono le cartelle di krsobjm (admin, standard, common, ...)
	private function modules() {
		$modules = array();

		foreach (glob(DIR_MODULE . '*', GLOB_ONLYDIR) as $dir) {
			if (preg_match('/^[A-Za-z0-9_]+$/', basename($dir))) {
				$modules[] = basename($dir);
			}
		}

		return $modules;
	}

	// file di lingua gia' su disco (scritti dagli sviluppatori o da import())
	private function readStrings($file) {
		$_ = array();

		include $file;

		return $_;
	}
}
?>
