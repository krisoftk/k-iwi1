<?php
/**
 * Base per i model: espone i servizi del registry come proprieta' magiche
 * (es. $this->db) cosi' i model non devono ricevere/passare ogni dipendenza
 * a mano.
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
