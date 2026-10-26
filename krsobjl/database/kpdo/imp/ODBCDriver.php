<?php
class ODBCDriver implements iDriverDB {
	
	private $driver;
	private $host;
	private $port;
	private $username;
	private $password;
	private $database;
	
	private $link;
	
	private $errorCode;
	private $errorInfo;
	
	private $inTransaction = FALSE;
	
	private $driver_options;
	private $lastInsertStatement;
	
	private $result;
		
	public function __construct($driver, $host, $port, $username, $password, $database = NULL) {
		$this->driver = $driver;
		$this->host = $host;
		$this->port = $port;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
		
		if ($port != '' && isset($port)) {
			$server = $host .":".$port;
		} else {
			$server = $host;
		}
		
		$this->link = new PDO('odbc:'.$server, $username, $password);

		if (!$this->link) {
			$this->errorCode = $this->link->errorCode();
			$this->errorInfo = $this->link->errorInfo();
			
			throw new KPDOException('Connect Error: ' . $this->errorInfo[2], $this->errorCode);
		} else {
			if ($database != "") {
				$this->selectSchema($database);
			}
		}
	}
	
	public function disconnect() {
		$this->link = null;
		return TRUE;
	}
	
	public function errorCode() {
		return $this->errorCode;
	}
	
	public function errorInfo() {
		return $this->errorInfo;
	}
	
	public function exec($statement) {
		$result = $this->link->query($statement);
		if(!$result){
			$this->errorCode = $this->link->errorCode();
			$this->errorInfo = $this->link->errorInfo();
			
 			throw new KPDOException('SQL Error: ' . $statement . "<br>" . $this->errorInfo[2], $this->errorCode);
		}
		
		return $result->rowCount();
	}
	
	public function query($statement) {
		$this->result = $this->link->query($statement);
		if(!$result){
			$this->errorCode = $this->link->errorCode();
			$this->errorInfo = $this->link->errorInfo();
			
 			throw new KPDOException('SQL Error: ' . $statement . "<br>" . $this->errorInfo[2], $this->errorCode);
		}
		
		return new ODBCStatement($statement, $this->result, $this->link);
	}
	
	public function prepare($statement, $driver_options = array()) {
		$stm = new ODBCStatement($statement, NULL, $this->link);
		
		$pos = stripos($statement, "insert");
		if ($pos !== FALSE && $pos == 0) {	
			$driver_options['pdo'] = $this;
			$this->driver_options = $driver_options;
			$stm->setDriverOption($this->driver_options);
		}
		
		return $stm;
	}
	
	public function beginTransaction() {
		/*if (!$this->link->beginTransaction()) {
			$this->errorCode = $this->link->errorCode();
			$this->errorInfo = $this->link->errorInfo();
			
 			throw new KPDOException('TRANSACTION Error: ' . $this->errorInfo[2], $this->errorCode);
		}
		return TRUE;*/
	}
	
	public function commit() {
		/*$this->link->commit();*/
	}
	
	public function rollBack() {
		/*$this->link->rollBack();*/
	}
	
	public function inTransaction() {
		return $this->inTransaction;
	}
	
	public function lastInsertId($name = NULL) {
		try {
			$table = $this->driver_options['table'];
			$query = 'SELECT MAX(ID) FROM "' . $table .'"';
			$result = $this->link->query($query);
			$row = $result->fetch(PDO::FETCH_NUM);
			return $row[0];
		} catch (PDOException $e) {
			echo $e;
			return FALSE;
		}
	}
	
	public function getDriverOption() {
		return $this->driver_options;
	}
	
	public function setDriverOption($driver_options) {
		$this->driver_options = $driver_options;
	}
	
	public function createTable($name, $fields, $drop = FALSE, $driver_options = array()){
		$query = "";
		$uq_group = "";

		$query .= "CREATE TABLE  \"$name\" (";
		foreach ($fields as $field) {
			$query .= $field[0];
			switch ($field[1][0]) {
				case KPDO::TYPE_VARCHAR:
					$query .= " VARCHAR(" . $field[1][1] . ") ";
					break;
				case KPDO::TYPE_INTEGER:
					$query .= " INTEGER ";
					break;
				case KPDO::TYPE_BIGINT:
					$query .= " BIGINT ";
					break;
				case KPDO::TYPE_BLOB:
					$query .= " BLOB ";
					break;
				case KPDO::TYPE_CHAR:
					$query .= " CHAR(" . $field[1][1] . ") ";
					break;
				case KPDO::TYPE_CLOB:
					$query .= " CLOB ";
					break;
				case KPDO::TYPE_DATE:
					$query .= " DATE ";
					break;
				case KPDO::TYPE_DECIMAL:
					$query .= " DECIMAL(" . $field[1][1] . "," . $field[1][2] . ") ";
					break;
				case KPDO::TYPE_DOUBLE:
					$query .= " DOUBLE ";
					break;
				case KPDO::TYPE_FLOAT:
					$query .= " FLOAT ";
					break;
				case KPDO::TYPE_REAL:
					$query .= " REAL ";
					break;
				case KPDO::TYPE_SMALLINT:
					$query .= " SMALLINT ";
					break;
				case KPDO::TYPE_TIME:
					$query .= " TIME ";
					break;
				case KPDO::TYPE_TIMESTAMP:
					$query .= " TIMESTAMP ";
					break;
				case KPDO::TYPE_BOOLEAN:
					$query .= " CHAR ";
					break;
				default: {
					$this->errorCode = $this->link->errorCode();
					$this->errorInfo = $this->link->errorInfo();
			
					throw new KPDOException("SQL Error: Tipo " . $field[1][0] . "non definito.");
				}
				break;
			}
				
			if (isset($field[2])) {
				if (($field[2] & KPDO::NOT_NULL) == KPDO::NOT_NULL) {
					$query .= " NOT NULL ";
				} 
				if (($field[2] & KPDO::AUTO_INCREMENT) == KPDO::AUTO_INCREMENT) {
					$query .= " GENERATED ALWAYS AS IDENTITY (START WITH 1, INCREMENT BY 1) ";
				}
				if (($field[2] & KPDO::PRIMARY_KEY) == KPDO::PRIMARY_KEY) {
					$pk[] = $field[0];
				}
				if (($field[2] & KPDO::UNIQUE) == KPDO::UNIQUE) {
					$uq[] = $field[0];
					$uq_group .= $field[0]."_";
				}
			}
			
			$query .= ",";
		}
		
		if(isset($pk) && (count($pk) > 0)) {
			$query .= "PRIMARY KEY( ";
			foreach ($pk as $field) {
				$query .= $field . ",";
			}
			
			$query = substr($query, 0, -1);
			
			$query .= "),";
		} 
		if(isset($uq) && (count($uq) > 0)) {
			$uq_group .= $name;
			$query .= " CONSTRAINT $uq_group UNIQUE ( ";
			foreach ($uq as $field) {
				$query .= $field . ",";
			}
			
			$query = substr($query, 0, -1);
			
			$query .= "),";
		} 
		
		$query = substr($query, 0, -1);
		
		
		$query .= ") ";
		
		return $this->exec($query);
	}

	public function createSchema($schema, $drop = FALSE) {
		
	}
	
	public function selectSchema($schema) {
		try {
			$this->exec("SET SCHEMA = " . $schema);
			$this->database = $schema;
		} catch (KPDOException $e) {
			echo $e;
		}
	}
	
	public function deleteTable($name) {
		/* TODO */
	}
	
	public function describeTable($name) {
		/* TODO */
	}
}
?>