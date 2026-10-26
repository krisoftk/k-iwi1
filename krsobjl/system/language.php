<?php
/**
 * Loads the language strings for a given logical "filename", first in the
 * default language (fallback) and then in the requested one, so the files
 * and keys missing from the current translation stay covered by the default.
 *
 * Languages are the rows of krs_languages as returned by
 * ModelCommonLanguage::getLanguages() (keys code, directory, filename...).
 */
class Language {
	private $language;
	private $default;
	private $strings = array();

	public function __construct($language, $default = null) {
		$this->language = $language;
		$this->default = $default ? $default : $language;
	}

	// Picks the interface language: the first active one among the given
	// codes, in order (e.g. the user's language, then setting.language). If
	// none is valid, for example config.ini points to a deleted or disabled
	// language, it falls back to the first active language instead of
	// breaking the page. The browser (Accept-Language) never counts.
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

	// Main file of a module (date and number formats, text direction, common
	// texts): it's named after the language (e.g. admin/italian.php,
	// standard/english.php), so the name differs between default and chosen language.
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
