<?php
interface iStatement {
	
	
	/**
	 * 
	 */
	function __construct($queryString, $result = NULL, $link = NULL);
	function columnCount();
	function errorCode();
	function errorInfo();
	function execute($input_parameters = NULL);
	function fetch($fetch_style);
	function fetchAll($fetch_style);
	function rowCount();
	function setDriverOption($driver_option);
	function getDriverOption();
	
	//function bindValue (mixed $parameter, mixed $value, int $data_type);
	//function closeCursor();
	
	//function debugDumpParams();
	
	//function fetchAll(int $fetch_style, mixed $fetch_argument, array $ctor_args);
	//function fetchColumn(int $column_number);
	//function fetchObject(string $class_name, array $ctor_args);
	//function getAttribute(int $attribute);
	//function getColumnMeta(int $column);
	//function nextRowset();
	
	//function setAttribute(int $attribute, mixed $value);
	//function setFetchMode(int $mode);
	
	
	
	
	
	//-----------------------------------
	/**
	 * Bind a column to a PHP variable
	 * @param mixed $column
	 * @param mixed $param
	 * @param int $type
	 * 
	 * @return boolean
	 */
	function bindColumn($column, &$param);
	/**
	 * Binds a parameter to the specified variable name 
	 * @param mixed $parameter
	 * @param mixed $variable
	 * @param int $data_type
	 * 
	 * @return boolean
	 */
	function bindParam($parameter, &$variable, $data_type);
	
	function limit($n, $m);
}