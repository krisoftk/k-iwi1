<?php

interface iDriverDB {
	
	/**
	* Constructor of the database access class
	*
	* @param string $server
	* @param string $username
	* @param string $password
	*
	*/
	public function __construct($driver, $host, $port, $username, $password, $database = NULL);
	
	/**
	* Disconnects from the database, releasing the connection
	*
	* @return boolean
	*/
	public function disconnect();
	
	/**
 	* Starts a transaction
 	*
 	* @return boolean
 	*
 	*/	
	public function beginTransaction();
	
	/**
 	* Commits the transaction
 	*
 	* @return boolean
 	*
 	*/	
	public function commit();
	
	/**
 	* Returns the error code
 	*
 	* @return mixed
 	*
 	*/	
	public function errorCode();
	
	/**
 	* Returns an array with the information about the error of the last
 	* operation performed on the database.
 	* The array contains these values:
 	* 0 SQLSTATE error code.
 	* 1	Driver-specific error code.
 	* 2 Driver-specific error message.
 	*
 	* @return array
 	*
 	*/	
	public function errorInfo();
	
	/**
 	* Executes an SQL statement in a single call, returning the number of rows affected by the operation.
 	*
 	* @return int
 	*
 	*/	
	public function exec($statement);
	
	/**
 	* Checks whether a transaction is in progress
 	*
 	* @param string statement
 	* @return boolean
 	*
 	*/	
	public function inTransaction();
	
	/**
 	* Returns the ID of the last inserted row
 	*
 	* @return String
 	*
 	*/	
	public function lastInsertId($name = NULL);
	
	/**
 	* Returns the statement with the query to prepare
 	*
 	* @return iStatement
 	*
 	*/
	public function prepare($statement, $driver_options = array());
	
	/**
 	* Executes an SQL statement in a single operation, returning the result (if any) through a KStatement statement
 	*
 	* @param string statement
 	* @param array driver_option [optional]
 	*
 	* @return iStatement
 	*
 	*/
	public function query($statement);
	
	/**
 	* Rolls back the transaction
 	*
 	* @return boolean
 	*
 	*/
	public function rollBack();
	
	public function setDriverOption($driver_option);
	public function getDriverOption();
	public function createTable($name, $fields, $drop, $driver_options = array());	
	public function createSchema($schema, $drop = NULL);
	public function selectSchema($schema);
	public function deleteTable($name);
	public function describeTable($name);
}