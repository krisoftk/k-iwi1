<?php
/**
 * Base for models: exposes the registry's services as magic properties
 * (e.g. $this->db), so models don't have to receive/pass every dependency
 * by hand.
 */
abstract class Model {
	protected $registry;

	public function __construct($registry) {
		$this->registry = $registry;
	}

	public function __get($service) {
		return $this->registry->get($service);
	}

	public function __set($service, $value) {
		$this->registry->set($service, $value);
	}
}
?>
