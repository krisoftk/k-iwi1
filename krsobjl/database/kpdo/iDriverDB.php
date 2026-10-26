<?php

interface iDriverDB {
	
	/**
	* Costruttore della classe per l'accesso al database
	* 
	* @param string $server
	* @param string $username
	* @param string $password
	* 
	*/
	public function __construct($driver, $host, $port, $username, $password, $database = NULL);
	
	/**
	* Disconnette dal database rilasciando la connessione
	* 
	* @return boolean
	*/
	public function disconnect();
	
	/**
 	* Avvia una transazione
 	* 
 	* @return boolean
 	*
 	*/	
	public function beginTransaction();
	
	/**
 	* Esegue il commit
 	* 
 	* @return boolean
 	*
 	*/	
	public function commit();
	
	/**
 	* Restituisce il codice errore
 	* 
 	* @return mixed
 	*
 	*/	
	public function errorCode();
	
	/**
 	* Restiusce un array contenente le informazioni sull'errore relativi 
 	* all'ultima operazione effettuata sul database.
 	* L'array contiene i seguenti valori:
 	* 0 SQLSTATE error code.
 	* 1	Driver-specific error code.
 	* 2 Driver-specific error message.
 	* 
 	* @return array
 	*
 	*/	
	public function errorInfo();
	
	/** 
 	* Esegue un SQL statement con una singola chiamata, ritornando il numero di righe coinvolte nell'operazione.
 	* 
 	* @return int
 	*
 	*/	
	public function exec($statement);
	
	/**  
 	* Controlla se si è all'interno di una transazione
 	* 
 	* @param string statement
 	* @return boolean
 	*
 	*/	
	public function inTransaction();
	
	/**   
 	* Restituisce l'ID dell'ultima riga inserita
 	* 
 	* @return String
 	*
 	*/	
	public function lastInsertId($name = NULL);
	
	/**   
 	* Restituisce lo statement con la query da prepare
 	* 
 	* @return iStatement
 	*
 	*/
	public function prepare($statement, $driver_options = array());
	
	/**    
 	* Esegue un SQL statement in una singola operazione, ritornado il risultato (se c'è) attraverso un statement KStatement 
 	* 
 	* @param string statement
 	* @param array driver_option [optional]
 	* 
 	* @return iStatement
 	*
 	*/
	public function query($statement);
	
	/**    
 	* Effettua il rollback 
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