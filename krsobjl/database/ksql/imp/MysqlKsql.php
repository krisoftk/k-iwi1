<?php
class MysqlKsql extends Ksql
{	
	protected $params = array(
		"INT" => "INT",
		"FLOAT" => "DECIMAL",
		"STRING" => "VARCHAR",
		"BOOLEAN" => "BOOLEAN",
		"NN" => "NOT NULL",
		"AI" => "AUTO_INCREMENT"
	);
	
	public function connect() {
		try {
			$connstr = "host=$this->ip";
			if ($this->database != '') {
				$connstr .= ";dbname=$this->database";
			}
			if ($this->port != '') {
				$connstr .= ";port=$this->port";
			}
			$this->connection = new PDO("mysql:$connstr", $this->user, $this->pass);
		} catch (PDOException $e) {
			$this->err_conn = "Error: " . $e->getMessage();
			return false;
		}
		$this->err_conn='NO ERROR';
		return true;
	}

	public function disconnect() {
		$this->connection = null;
		return true;
	}

	public function request() {
		if ($this->err_conn == 'NO ERROR') {
			$this->numrow = 0;
			$this->result = $this->connection->query($this->sql);
			
			if ($this->result) {
				$this->error = '';
				$this->numrows = $this->result->rowCount();
			}
			else
			{
				$this->error = 'KSQL-QUERY ERROR:'.implode("; ",$this->connection->errorInfo());
			}
		}
		else
		{
			$this->error = 'KSQL-CONNECTION ERROR: '.$this->err_conn;
		}
		return $this->error;
	}

	public function fetch() {
		if ($this->result != '') {
			$this->row = $this->result->fetch(PDO::FETCH_ASSOC);
		}
		if ($this->row != false) {
			$this->numrow++ ;
		}
		return $this->row;
	}

	public function numrow() {
		return $this->numrow;
	}
	
	public function begintransaction() {
		$this->connection->beginTransaction();		
	}
	
	public function commit() {
		$this->connection->commit();		
	}
	
	public function rollback() {
		$this->connection->rollBack();		
	}
	
	public function numrows() {
		return $this->numrows;
	}
	
	protected function select() {
		echo strpos($this->field4,'DISTINCT');
		$distinct='';
		$this->sql = "SELECT " . $distinct . $this->field1 . " FROM " . $this->database . "." . $this->file;
		if ($this->field2 != '') {
			$this->sql = $this->sql." WHERE ".$this->field2;
		}
		if ($this->field3 != '') {
			$this->sql = $this->sql." ORDER BY  ".$this->field3;
		}
	}
	
	protected function update() {
		$this->sql = "UPDATE " . $this->database ."." . $this->file;
		if ($this->field1 != '') {
			$this->sql = $this->sql . " SET " . $this->field1;
		}
		if ($this->field2 != '') {
			$this->sql = $this->sql . " WHERE " . $this->field2;
		}
	}
	
	protected function insert() {
		$this->sql = "INSERT INTO " . $this->database . "." . $this->file;
		if ($this->field1 != '') {
			$this->sql = $this->sql." (".$this->field1.")";
		}
		if ($this->field2 != '') {
			$this->sql = $this->sql." VALUES (".$this->field2.")";
		}
		if ($this->field3 != '') {
			$this->sql = $this->sql." VALUES ".$this->field3;
		}
	}
	
	protected function delete() {
		$this->sql = "DELETE FROM " . $this->database . "." . $this->file;
		if ($this->field1 != '') {
			$this->sql = $this->sql." WHERE ".$this->field1;
		}
	}
	
	protected function add_column() {
		if ($this->field1 != '') {
			echo "dentro";
			$this->sql = "ALTER TABLE " . $this->database . "." . $this->file;		
			$this->sql .= " ADD COLUMN ";
			$this->sql .= $this->generatetable($this->field1);
		}
	}
	
	protected function add_unique() {
		if ($this->field1 != '' && $this->field2 != '') {
			$this->sql = "ALTER TABLE " . $this->database . "." . $this->file;		
			$this->sql .= " DROP INDEX " . $this->field1 . ",";
			$this->sql .= " ADD UNIQUE INDEX " . $this->field1 . "(" . $this->field2 . ")";
		}
	}
	
	protected function createtable() {
		$this->sql = "CREATE TABLE IF NOT EXISTS " . $this->database . "." . $this->file;
		if ($this->field1 != '') {
			$this->sql = $this->sql . " ( ".$this->generatetable($this->field1);
			
			if ($this->field2 != '') {
				$this->sql = $this->sql. ", PRIMARY KEY ( ".$this->field2." )";					
			}

			if ($this->field3 != '') {
				$this->sql = $this->sql. ", UNIQUE INDEX ( ".$this->field3." )";					
			}
			
			$this->sql = $this->sql." )";
		}
		if ($this->field4 != '') {
			$this->sql = $this->sql." ENGINE = ".$this->field4;
		}
	}
	
	protected function createdatabase() {
		$this->sql = "CREATE DATABASE IF NOT EXISTS " . $this->database;
	}
}
?>
