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
	header('Location: installation/index.php');
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

//Theme
$config->set('framework.theme', $config->get('framework.theme_admin'));

// Database
$db = new KPDO($config->get('database.driver'), $config->get('database.host'), $config->get('database.port'), $config->get('database.user'), $config->get('database.password'), $config->get('database.name'));
$registry->set('db', $db);

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

// Security headers, sent right away so they also apply to redirects
// (which exit before Response::output): no MIME sniffing, pages can't be
// framed by other sites (clickjacking) and the Referer only towards this
// site, because the admin URLs contain the session token.
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

// Language: the one chosen by the user (krs_users.LANGUAGE), otherwise
// setting.language from config.ini. The config.ini language is also the
// fallback for the files and phrases the user's language lacks (in this
// module or in another one). The browser doesn't count: the language belongs to the user.
$loader->model('common/language');

$languages = $registry->get('model_common_language')->getLanguages();

$default_language = Language::choose($languages, array($config->get('setting.language')));
$user_language = Language::choose($languages, array($user->getLanguage(), $default_language['code']));

$config->set('config_language_id', $user_language['id']);
$config->set('config_language', $user_language['code']);

$language = new Language($user_language, $default_language);
$language->loadMain('admin');
$registry->set('language', $language);

// Front Controller
$controller = new Front($registry);

// Login
$controller->addPreAction(new Action('admin/common/home/login'));

// Router
if (isset($request->get['route'])) {
	$action = new Action($request->get['route']);
} else {
	$action = new Action('admin/common/home');
}

// Dispatch
$controller->dispatch($action, new Action('error/not_found'));

// Output
$response->output();