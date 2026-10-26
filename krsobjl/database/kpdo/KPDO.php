<?php
class KPDO implements iKPDO {
	
	private $driver;
	
	public function __construct($driver, $host, $port, $username, $password, $database = NULL) {
		switch (strtolower($driver)) {
			case "mysql":
				$this->driver = new MysqlDriver($driver, $host, $port, $username, $password, $database);
			break;
			case "odbc":
				$this->driver = new ODBCDriver($driver, $host, $port, $username, $password, $database);
			break;
			default:
				throw new KPDOException("Driver Error: Il driver \"$driver\" specificato non è installato");
			break;
		}
	}
	
	public function beginTransaction() {
		return $this->driver->beginTransaction();
	}
	
	public function commit() {
		return $this->driver->commit();
	}
	
	public function disconnect() {
		return $this->driver->disconnect();
	}
	
	public function errorCode() {
		return $this->driver->errorCode();
	}
	
	public function errorInfo() {
		return $this->driver->errorInfo();
	}
	
	public function exec($statement) {
		return $this->driver->exec($statement);
	}
	
	public function getDriverOption() {
		return $this->driver->getDriverOption();
	}
	
	public function inTransaction() {
		return $this->driver->inTransaction();
	}
	
	public function lastInsertId($name = NULL) {
		return $this->driver->lastInsertId($name);
	}
	
	public function prepare($statement, $driver_options = array()) {
		return $this->driver->prepare($statement, $driver_options);
	}
	
	public function query($statement) {
		return $this->driver->query($statement);
	}
	
	public function rollBack() {
		return $this->driver->rollBack();
	}
	
	public function setDriverOption($driver_option) {
		$this->driver->setDriverOption($driver_option);
	}
	
	public function createTable($name, $fields, $drop = FALSE, $driver_options = array()) {
		$this->driver->createTable($name, $fields, $drop, $driver_options);
	} 
	
	public function createSchema($schema, $drop = FALSE) {
		$this->driver->createSchema($schema, $drop);
	}
	
	public function selectSchema($schema) {
		$this->driver->selectSchema($schema);
	}
	
	public function deleteTable($name) {
		$this->driver->deleteTable($name);
	}
	
	public function describeTable($name) {
		return $this->driver->describeTable($name);
	}
}
