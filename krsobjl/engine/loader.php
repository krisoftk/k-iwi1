<?php
/**
 * Single point from which a controller loads additional dependencies:
 * krsobjl libraries, application models (resolved with the same
 * PathResolver used for controllers), configuration files, language and
 * extensions.
 */
final class Loader {
	private $registry;

	public function __construct($registry) {
		$this->registry = $registry;
	}

	public function __get($service) {
		return $this->registry->get($service);
	}

	public function __set($service, $value) {
		$this->registry->set($service, $value);
	}

	public function library($name) {
		$file = DIR_LIBRARY . $name . '.php';

		if (!file_exists($file)) {
			trigger_error('Error: Could not load library ' . $name . '!');
			exit();
		}

		include_once($file);
	}

	public function model($model) {
		$resolved = PathResolver::resolve($model, 'model');
		$class    = 'Model' . $resolved['class'];

		if (!file_exists($resolved['file'])) {
			trigger_error('Error: Could not load model ' . $model . '!');
			exit();
		}

		include_once($resolved['file']);

		$key = 'model_' . str_replace('/', '_', $model);

		$this->registry->set($key, new $class($this->registry));
	}

	public function config($filename) {
		$this->config->load($filename);
	}

	public function language($filename) {
		return $this->language->load($filename);
	}

	public function database($name, $driver, $hostname, $port, $username, $password, $database) {
		$this->registry->set('database_' . $name, new KPDO($driver, $hostname, $port, $username, $password, $database));
	}

	public function ext($name) {
		$file = DIR_MODULE . $name . '.php';

		if (!file_exists($file)) {
			trigger_error('Error: Could not load extension ' . $name . '!');
			exit();
		}

		include_once($file);
	}
}
?>
