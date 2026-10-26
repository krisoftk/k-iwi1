<?php
/**
 * Copyright (c) 2014 Krisoft S.r.l..
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the GNU Public License v3.0
 * which accompanies this distribution, and is available at
 * http://www.gnu.org/licenses/gpl.html
 * 
 * Contributors:
 *     Krisoft S.r.l. - initial API and implementation
 */


// Version
define('VERSION', '1.0');

// Configuration
require_once('globals.php');

// Install
if (!defined('DIR_PUBLIC')) {
	header('Location: install/index.php');
	exit;
}

// Application Management
require_once(DIR_LIBRARY . 'common/application_management.php');

// Startup
require_once(DIR_LIBRARY . 'common/bootstrap.php');

// Registry
$registry = new Registry();

// KReport
$kreport = new KReport($registry);
$registry->set('kreport', $kreport);

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Config
$config = new Config();
$registry->set('config', $config);

$config->load('config');

// Database
$db = new KPDO($config->get('database.driver'), $config->get('database.host'), $config->get('database.port'), $config->get('database.user'), $config->get('database.password'), $config->get('database.name'));
$registry->set('db', $db);

// Persistence
$persistence = new Persistence($registry);
$registry->set('persistence', $persistence);

// Settings
$config->set('config_url', HTTP_SERVER);
$config->set('config_ssl', HTTPS_SERVER);

// Url
$url = new Url($config->get('config_url'), $config->get('setting.use_ssl') ? $config->get('config_ssl') : $config->get('config_url'));
$registry->set('url', $url);

// Log
$log = new Log($config->get('setting.file_log'));
$registry->set('log', $log);

function error_handler($errno, $errstr, $errfile, $errline) {
	if (!error_reporting()) {
		return true;
	}
	
	global $log, $config;

	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$error = 'Notice';
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$error = 'Warning';
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$error = 'Fatal Error';
			break;
		default:
			$error = 'Unknown';
		break;
	}

	if ($config->get('setting.error_display')) {
		echo '<b>' . $error . '</b>: ' . $errstr . ' in <b>' . $errfile . '</b> on line <b>' . $errline . '</b>';
	}

	if ($config->get('setting.error_log')) {
		$log->write('PHP ' . $error . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
	}

	return true;
}

// Error Handler
set_error_handler('error_handler');

// Request
$request = new Request();
$registry->set('request', $request);

// Response
$response = new Response();
$response->addHeader('Content-Type: text/html; charset=utf-8');

// Header di sicurezza, inviati subito cosi' valgono anche per i redirect
// (che escono prima di Response::output): niente MIME sniffing, pagine non
// incorniciabili da altri siti (clickjacking) e Referer solo verso questo
// sito, perche' gli URL dell'admin contengono il token di sessione.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
$response->setCompression($config->get(('setting.compression')));
$registry->set('response', $response);

// Session
if ($config->get('setting.use_session_db') == '1') {
	$session = new SessionDB();
} else {
	$session = new SessionCookie($config->get('setting.timeout'));
}
$registry->set('session', $session);

// Auth
$auth = new Auth($registry);
$registry->set('auth', $auth);

// Document
$registry->set('document', new Document());

// User
$user = new User($registry);
$registry->set('user', $user);

// API key (chiamate machine-to-machine: API dei moduli, cron dello scheduler).
// Autentica solo questa richiesta, senza toccare la sessione.
if (isset($request->server['HTTP_X_API_KEY'])) {
	$user->loginByApiKey($request->server['HTTP_X_API_KEY']);
} elseif (isset($request->post['api_key'])) {
	$user->loginByApiKey($request->post['api_key']);
} elseif (isset($request->get['api_key'])) {
	$user->loginByApiKey($request->get['api_key']);
}

// Language: quella scelta dall'utente (krs_users.LANGUAGE), altrimenti
// setting.language di config.ini. La lingua di config.ini fa anche da
// riserva per i file e le frasi che la lingua dell'utente non ha (in questo
// modulo o in un altro). Il browser non conta: la lingua e' dell'utente.
$loader->model('common/language');

$languages = $registry->get('model_common_language')->getLanguages();

$default_language = Language::choose($languages, array($config->get('setting.language')));
$user_language = Language::choose($languages, array($user->getLanguage(), $default_language['code']));

$config->set('config_language_id', $user_language['id']);
$config->set('config_language', $user_language['code']);

$language = new Language($user_language, $default_language);
$language->loadMain('standard');
$registry->set('language', $language);

// Front Controller
$controller = new Front($registry);

// Login obbligatorio su tutto tranne le pagine che portano al login
$controller->addPreAction(new Action('standard/home/login'));

// Check session status
if (!$auth->auth_check_user_and_timeout()) {
	
	if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		$action = new Action('error/timeout/kajax');
	} else {
		$action = new Action('error/timeout');
	}

} else {
	$session->data['time_start'] = time();
	
	// Router
	if (isset($request->get['route'])) {
		if (isset($request->get['pattern'])) {
			// Programmi "diretti" del menu (krs_progs.PATTERN): file inclusi
			// cosi' come sono. La route arriva dall'esterno, quindi si include
			// solo un file dentro DIR_MODULE che corrisponda a un programma
			// diretto registrato su cui l'utente ha permesso, come nel menu:
			// senza questi controlli si potrebbe leggere qualunque file del
			// server (es. ../config/config.ini) o eseguire programmi altrui.
			$direct = realpath(DIR_MODULE . $request->get['route']);

			if ($user->isLogged() && $direct && strpos($direct, realpath(DIR_MODULE) . DIRECTORY_SEPARATOR) === 0) {
				$loader->model('standard/menu');

				$prog = $registry->get('model_standard_menu')->find_direct_prog($request->get['route']);

				if ($prog && $user->hasPermission($prog['ID'])) {
					include $direct;
					exit;
				}
			}

			// altrimenti segue il percorso normale: il pre-action manda al
			// login chi non e' autenticato, il resto finisce in not_found
			$action = new Action($request->get['route']);
		} else {
			// Action
			$action = new Action($request->get['route']);
		}
	} else {
		// Action
		$action = new Action('standard/home');
	}
}

// Dispatch
$controller->dispatch($action, new Action('error/not_found'));

// Output
$response->output();
?>