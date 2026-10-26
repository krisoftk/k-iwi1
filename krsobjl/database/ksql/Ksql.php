<?php
abstract class Ksql implements iKsql
{
	public $driver;				// driver
	public $ip;         		// serverip
	public $port;				// port
	public $user;       		// user
	public $pass;      			// password
	public $database;   		// database
	public $file;       		// file
	public $operator;   		// sql to perform to the DB written in standard sql ISO
	public $field1;     		// field 1
	public $field2;     		// field 2
	public $field3;     		// field 3
	public $field4;     		// field 4
	
	public $table;				// alias of file
	
	public $fields;				// alias of field1
	public $set;				// alias of field1
	public $deletecondition;	// alias of field1
	public $createfields;		// alias of field1
	public $column;				// alias of field1
	public $index;				// alias of field1
	
	public $where;				// alias of field2
	public $values;				// alias of field2
	public $primarykey;			// alias of field2
	public $new_index;			// alias of field2
	
	
	public $orderby;			// alias of field3
	public $uniqueindex;		// alias of field3
	public $multivalues;		// alias of field3
	
	public $engine;				// alias of field4
	
	protected $numrow;     		// number of row fetched from the object
	protected $numrows;        	
	protected $connection; 		// internal connection
	protected $err_conn;   		// error received from the server if connection fail.
	protected $result;     		// sql result from server
	protected $sql;        		// sql to perform to the server
	protected $row;        		// last fetched row
	protected $params;			// combining array general syntax to the specific db
	protected $error;			// description of error 
	
	abstract protected function select();
	abstract protected function update();
	abstract protected function insert();
	abstract protected function delete();
	abstract protected function add_column();
	abstract protected function add_unique();
	abstract protected function createtable();
	abstract protected function createdatabase();
	
	public function __construct() {
		/*alias of file*/
		$this->table =& $this->file;
		
		/*alias of field1*/
		$this->fields =& $this->field1;
		$this->set =& $this->field1;
		$this->deletecondition =& $this->field1;
		$this->createfields =& $this->field1;
		$this->column =& $this->field1;
		$this->index =& $this->field1;
		
		/*alias of field2*/
		$this->where =& $this->field2;
		$this->values =& $this->field2;
		$this->primarykey =& $this->field2;
		$this->new_index =& $this->field2;
		
		/*alias of field3*/
		$this->orderby =& $this->field3;
		$this->uniqueindex =& $this->field3;
		$this->multivalues =& $this->field3;
		
		/*alias of field4*/
		$this->engine =& $this->field4;
		$this->options =& $this->field4;
		
		if (!isset($this->params)) {
			die("Set params");
		}
	}
	
	protected function trasformparam($param) {
		$pos = strpos($param, "(");
		$key = $param;
		$details = NULL;
		if ($pos !== false) {
			$key = strstr($param, '(', true);
			$details = strstr($param, '('); 
		}
		if (array_key_exists($key, $this->params)) {
			return $this->params[$key].$details;
		} else {
			return $key;
		}
	}
	
	protected function generatetable($field1) {
		$block = '';
		$specific_field = str_getcsv($field1, ",");
		$count = count($specific_field) - 1;
		
		foreach ($specific_field as $index => $row) {
			$param = strtok($row, " ");
			$name = $param;
			while ($param !== false) {
				
				if ($param === "PR") {
					if ($this->field2 != "") {
						$this->field2 .= ",";
					}
					$this->field2 .= $name;
				} else if ($param === "UQ") {
					if ($this->field3 != "") {
						$this->field3 .= ",";
					}
					$this->field3 .= $name;  
				} else {
					$block .= " ".$this->trasformparam($param)." ";					
				}
				$param = strtok(" ");
			}
				
			if ($index < $count) {
				$block .= ",";
			}
		}
		return $block;
	}
	
	public function createsql() {
		switch (strtoupper($this->operator))
		{
			case "SELECT":
				$this->select();
				break;
			case "UPDATE":
				$this->update();
				break;
			case "INSERT":
				$this->insert();
				break;
			case "DELETE":
				$this->delete();
				break;
			case "ADD_COLUMN":
				$this->add_column();
				break;
			case "ADD_UNIQUE":
				$this->add_unique();
				break;
			case "CREATETABLE":
				$this->createtable();
				break;
			case "CREATEDB":
				$this->createdatabase();
				break;
		}
		return $this->sql;
	}
	
	public function resetfield() {
		$this->field1 = NULL;
		$this->field2 = NULL; 
		$this->field3 = NULL; 
		$this->field4 = NULL;
	}
	
	public function geterror() {
		return $this->error;
	}
	
	public function getconnection () {
		return $this->connection;
	}
	
	public function setconnection($connection) {
		$this->connection = $connection;
		$this->err_conn = 'NO ERROR';
	}
}
?>