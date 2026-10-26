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


// Already installed? The installer starts with a simple GET and asks for no
// confirmation: on an existing installation it would insert the base data
// again and rewrite the globals.php files. Same criterion as
// krsobjp/index.php: it's installed when globals.php defines DIR_PUBLIC.
$installed_globals = dirname(__DIR__) . '/globals.php';

if (is_file($installed_globals) && strpos(file_get_contents($installed_globals), "define('DIR_PUBLIC'") !== false) {
	http_response_code(403);
	header('Content-Type: text/plain; charset=utf-8');
	echo "K-iwi risulta gia' installato.\n\nPer reinstallare da zero cancella krsobjp/globals.php e krsobjp/admin/globals.php e svuota il database.";
	exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

// HTTP
// URL of the public folder (krsobjp), which is the recommended DocumentRoot:
// "/install/index.php" -> "/", but it also works if the installation is
// reachable as "/something/krsobjp/install/index.php".
define('PUBLIC_PATH', rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['PHP_SELF']))), '/') . '/');
define('HTTP_SERVER', 'http://' . $_SERVER['HTTP_HOST'] . PUBLIC_PATH);
define('HTTPS_SERVER', 'https://' . $_SERVER['HTTP_HOST'] . PUBLIC_PATH);

// DIR
define('DIR_BASE', str_replace('\\', '/', realpath(dirname(__FILE__) . '/../..')) . '/');
define('DIR_PUBLIC', str_replace('\\', '/', DIR_BASE . 'krsobjp/'));
define('DIR_LIBRARY', str_replace('\\', '/', DIR_BASE . 'krsobjl/'));
define('DIR_MODULE', str_replace('\\', '/', DIR_BASE . '/krsobjm/'));
define('DIR_JAVASCRIPT', str_replace('\\', '/', DIR_LIBRARY . 'javascript/'));
define('DIR_TEMPLATE', DIR_MODULE . 'install/view/themes/');
define('DIR_LOGS', DIR_BASE . 'logs/');
define('DIR_CONFIG', DIR_BASE . 'config/');

//INDEX
define('INDEX', 'install/index.php');

// Application Management
require_once(DIR_LIBRARY . 'common/application_management.php');

// Startup
require_once(DIR_LIBRARY . 'common/bootstrap.php');

// Registry
$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

$loader->library('system/install');

// Config
$config = new Config();
$registry->set('config', $config);

$config->load('config');

// Database
$db = new KPDO($config->get('database.driver'), $config->get('database.host'), $config->get('database.port'), $config->get('database.user'), $config->get('database.password'), $config->get('database.name'));
$registry->set('db', $db);

// Request
$request = new Request();
$registry->set('request', $request);

// Response
$response = new Response();
$response->addHeader('Content-Type: text/html; charset=UTF-8');
$registry->set('response', $response);

// Document
$document = new Document();
$registry->set('document', $document);

// Front Controller
$controller = new Front($registry);

// Router
if (isset($request->get['route'])) {
	$action = new Action($request->get['route']);
} else {
	$action = new Action('install/step_1');
}

// Dispatch
$controller->dispatch($action, new Action('not_found'));

// Output
$response->output();
?>