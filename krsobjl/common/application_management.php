<?php
function makePath($path) {
	global $config;
	global $request;

	if (isset($request->server['HTTPS']) && (($request->server['HTTPS'] == 'on') || ($request->server['HTTPS'] == '1'))) {
		$server = HTTPS_THEMES;
	} else {
		$server = HTTP_THEMES;
	}

	// DIR_THEMES/HTTP(S)_THEMES point to a different folder for the admin area
	// than for the standard site (".../themes/admin/" vs ".../themes/"), but
	// the theme name in framework.theme is always the standard site's: if it
	// doesn't exist in that folder, framework.theme_admin is used.
	$theme = $config->get('framework.theme');

	if (!is_dir(DIR_THEMES . $theme) && $config->has('framework.theme_admin')) {
		$theme = $config->get('framework.theme_admin');
	}

	$url = $server . $theme . '/' . $path;

	// cache-busting: appends the file's modification time, so the browser
	// downloads the new version of css/js when they change, without a
	// manual cache flush.
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
