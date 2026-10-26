<?php
function makePath($path) {
	global $config;
	global $request;

	if (isset($request->server['HTTPS']) && (($request->server['HTTPS'] == 'on') || ($request->server['HTTPS'] == '1'))) {
		$server = HTTPS_THEMES;
	} else {
		$server = HTTP_THEMES;
	}

	// DIR_THEMES/HTTP(S)_THEMES puntano a una cartella diversa per l'area
	// admin rispetto al sito standard (".../themes/admin/" vs ".../themes/"),
	// ma il nome del tema in framework.theme e' sempre quello del sito
	// standard: se in quella cartella non esiste, si usa framework.theme_admin.
	$theme = $config->get('framework.theme');

	if (!is_dir(DIR_THEMES . $theme) && $config->has('framework.theme_admin')) {
		$theme = $config->get('framework.theme_admin');
	}

	$url = $server . $theme . '/' . $path;

	// cache-busting: aggiunge la data di modifica del file, cosi' il
	// browser scarica la versione nuova di css/js quando cambiano senza
	// bisogno di uno svuota-cache manuale.
	$file = DIR_THEMES . $theme . '/' . $path;

	if (is_file($file)) {
		$url .= '?v=' . filemtime($file);
	}

	return $url;
}

function getCurrentAction() {
	if (!isset($_SESSION['prog'])) {
		$prog = '"' . _HOMEPAGE . '"';
	} else {
		$prog = $_SESSION['prog'];
	}
	echo $prog;
}

function action($action) {
	switch ($action) {
		case 'print':
			echo "javascript:window.print()";
		break;
		default:
			echo "";
		break;
	};
}

function initializeKAJAX($body, $info, $loader) {
	$body 	= ($body == null)? 'null' : '\'' . $body . '\''; 
	$info 	= ($info == null)? 'null' : '\'' . $info . '\'';
	$loader = ($loader == null)? 'null' : '\'' . $loader . '\'';
	
	return '
	<script type="text/javascript" src="js/ajax/kajax.js"></script>
	<script type="text/javascript">
		initializeKAJAX(' . $body . ', ' . $info . ', ' . $loader . ');
	</script>
	'; 
}

function save_history($page) {
	$_SESSION['prog'] = "\"" . $page . "\"";
}

function load_library($libraries = NULL) {
	require_once 'all_libraries.php';
	
	if(isset($libraries)) { 
		foreach ($libraries as $library) {
			require_once $allLibraries[$library];
		}
	} else {
		foreach ($allLibraries as $library) {
			require_once $library;
		}
	}
}
?>
