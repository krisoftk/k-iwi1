<?php
require_once 'session_db.php';
require_once 'session_cookie.php';

// Settings shared by SessionCookie and SessionDB, to call before
// session_start(): PHP rejects session IDs it didn't issue itself (session
// fixation), and the cookie can't be read by JavaScript, isn't sent over
// plain http under https and isn't sent with requests from other sites
// (Lax still lets normal incoming links work).
function session_harden() {
	ini_set('session.use_strict_mode', '1');
	ini_set('session.use_only_cookies', '1');

	// Pages of a session (the admin's hold its token in every link) are
	// never cached by the browser or a proxy: it's PHP's default, made
	// explicit so it doesn't depend on the server's php.ini.
	ini_set('session.cache_limiter', 'nocache');

	session_set_cookie_params(array(
		'lifetime' => 0,
		'path' => '/',
		'secure' => !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off',
		'httponly' => true,
		'samesite' => 'Lax',
	));
}
?>