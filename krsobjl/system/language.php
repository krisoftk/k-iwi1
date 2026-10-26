<?php
/**
 * Carica le stringhe di lingua per un dato "filename" logico, prima nella
 * lingua di default (fallback) poi in quella richiesta, cosi' i file e le
 * chiavi che mancano nella traduzione corrente restano coperti dal default.
 *
 * Le lingue sono le righe di krs_languages come le restituisce
 * ModelCommonLanguage::getLanguages() (chiavi code, directory, filename...).
 */
class Language {
	private $language;
	private $default;
	private $strings = array();

	public function __construct($language, $default = null) {
		$this->language = $language;
		$this->default = $default ? $default : $language;
	}

	// Sceglie la lingua dell'interfaccia: la prima attiva tra i codici dati,
	// in ordine (es. lingua dell'utente, poi setting.language). Se nessuno e'
	// valido, ad esempio config.ini punta a una lingua cancellata o
	// disattivata, ripiega sulla prima lingua attiva invece di rompere la
	// pagina. Il browser (Accept-Language) non conta mai.
	public static function choose($languages, $codes) {
		foreach ($codes as $code) {
			if ($code && isset($languages[$code]) && $languages[$code]['status']) {
				return $languages[$code];
			}
		}

		foreach ($languages as $language) {
			if ($language['status']) {
				return $language;
			}
		}

		throw new RuntimeException('Nessuna lingua attiva in krs_languages');
	}

	public function getCode() {
		return $this->language['code'];
	}

	public function get($key) {
		return isset($this->strings[$key]) ? $this->strings[$key] : $key;
	}

	public function load($filename) {
		$this->loadFile($filename, $this->default['directory']);

		if ($this->language['directory'] != $this->default['directory']) {
			$this->loadFile($filename, $this->language['directory']);
		}

		return $this->strings;
	}

	// File principale di un modulo (formati di data e numeri, direzione del
	// testo, testi comuni): si chiama come la lingua (es. admin/italian.php,
	// standard/english.php), quindi il nome cambia tra default e lingua scelta.
	public function loadMain($module) {
		$this->loadFile($module . '/' . $this->default['filename'], $this->default['directory']);

		if ($this->language['directory'] != $this->default['directory']) {
			$this->loadFile($module . '/' . $this->language['filename'], $this->language['directory']);
		}

		return $this->strings;
	}

	private function loadFile($filename, $languageDirectory) {
		$segments = explode('/', $filename);
		array_splice($segments, 1, 0, array('language', $languageDirectory));

		$file = DIR_MODULE . implode('/', $segments) . '.php';

		if (!file_exists($file)) {
			return;
		}

		$_ = array();

		require($file);

		$this->strings = array_merge($this->strings, $_);
	}
}
?>
