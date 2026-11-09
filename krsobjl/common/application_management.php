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

// $csrf: the session's token (csrf_token()), which kajax.js adds to every
// request towards this site
function initializeKAJAX($body, $info, $loader, $csrf = null) {
	$body 	= ($body == null)? 'null' : '\'' . $body . '\''; 
	$info 	= ($info == null)? 'null' : '\'' . $info . '\'';
	$loader = ($loader == null)? 'null' : '\'' . $loader . '\'';
	
	return '
	<script type="text/javascript" src="js/ajax/kajax.js"></script>
	<script type="text/javascript">
		window.KAJAX_CSRF = ' . json_encode($csrf) . ';
		initializeKAJAX(' . $body . ', ' . $info . ', ' . $loader . ');
	</script>
	'; 
}

// CSRF protection of the site: a random token per session, which every
// POST made with the session cookie must send back, in the X-CSRF-Token
// header (KAJAX and jQuery do it on their own, see kajax.js) or in a
// csrf_token field (plain forms, iframe uploads). Another site can make the
// browser send the cookie, but it can't read the token. The admin has its
// own token in the URL; API calls authenticate with their key instead.
function csrf_token($session) {
	if (empty($session->data['csrf_token'])) {
		$session->data['csrf_token'] = bin2hex(random_bytes(32));
	}

	return $session->data['csrf_token'];
}

function csrf_valid($session, $request) {
	if (empty($session->data['csrf_token'])) {
		return false;
	}

	if (isset($request->server['HTTP_X_CSRF_TOKEN'])) {
		$sent = $request->server['HTTP_X_CSRF_TOKEN'];
	} elseif (isset($request->post['csrf_token'])) {
		$sent = $request->post['csrf_token'];
	} else {
		return false;
	}

	return is_string($sent) && hash_equals($session->data['csrf_token'], $sent);
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

// Uncaught exceptions and fatal errors don't go through error_handler
// (krsobjp/index.php): PHP 8 turns most fatal errors (undefined function,
// TypeError, ArgumentCountError...) into exceptions, and without these
// handlers they would only reach the web server's log, which often can't be
// read (shared hosting). Registered by both index.php right after this file,
// so they also cover the startup (e.g. the database connection).
function exception_handler($exception) {
	report_fatal_error('Uncaught ' . get_class($exception) . ': ' . $exception->getMessage(), $exception->getFile(), $exception->getLine());
}

function shutdown_handler() {
	$error = error_get_last();

	if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {
		report_fatal_error($error['message'], $error['file'], $error['line']);
	}
}

// Same settings as error_handler: setting.error_log writes to the
// framework's log, setting.error_display shows the error in the page. Before
// the configuration is loaded only PHP's own log is left.
function report_fatal_error($message, $file, $line) {
	global $config, $log;

	$text = 'PHP Fatal Error:  ' . $message . ' in ' . $file . ' on line ' . $line;

	if (!headers_sent()) {
		http_response_code(500);
	}

	if (!($config instanceof Config)) {
		error_log($text);

		return;
	}

	if ($config->get('setting.error_log')) {
		if (!($log instanceof Log) && $config->get('setting.file_log')) {
			$log = new Log($config->get('setting.file_log'));
		}

		if ($log instanceof Log) {
			$log->write($text);
		} else {
			error_log($text);
		}
	}

	if ($config->get('setting.error_display')) {
		echo '<b>Fatal Error</b>: ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . ' in <b>' . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . '</b> on line <b>' . (int) $line . '</b>';
	}
}
?>
