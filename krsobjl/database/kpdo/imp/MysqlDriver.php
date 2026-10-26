<?php

class MysqlDriver implements iDriverDB {
	
	private $driver;
	private $host;
	private $port; 
	
	private $username;
	private $password;
	
	private $database;
	
	private $link = NULL;
	
	private $errorCode;
	private $errorInfo;
	
	private $inTransaction = FALSE;
	
	private $driver_option;
	
	private $result;
	
	public function __construct($driver, $host, $port, $username, $password, $database = NULL) {
		$this->driver = $driver;
		$this->host = $host;
		$this->port = $port;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
		
		// Da PHP 8.1 mysqli lancia mysqli_sql_exception a ogni errore invece di
		// restituire false: i controlli "=== FALSE" di questo driver non
		// scatterebbero piu' e ogni errore SQL diventerebbe fatale invece di una
		// KPDOException gestibile dai chiamanti. Si torna al comportamento per
		// cui il driver e' scritto.
		mysqli_report(MYSQLI_REPORT_OFF);

		if ($port != '' && isset($port)) {
			$this->link =  new mysqli($host, $username, $password, $database, (int)$port);
		} else {
			$this->link =  new mysqli($host, $username, $password, $database);
		}
		
		if (mysqli_connect_error()) {
			// l'oggetto mysqli di una connessione fallita non si puo' piu'
			// interrogare (PHP 8: "mysqli object is already closed"), che
			// nasconderebbe il motivo vero: si usano le funzioni connect_*
			$this->errorCode = mysqli_connect_errno();
			$this->errorInfo = array(0, mysqli_connect_errno(), mysqli_connect_error());
			
			throw new KPDOException('Connect Error: ' . mysqli_connect_error(), mysqli_connect_errno());
		} else {
			if ($database != "") {
				$this->selectSchema($database);
			}
			
			$this->link->set_charset("utf8");
		}
	}
	
	public function disconnect() {
		if (!$this->link->close()) {
			$this->errorCode = $this->link->errno;
			$this->errorInfo = array($this->link->sqlstate, $this->errorCode, $this->link->error);
			
			throw new KPDOException('Connect Error: ' . mysqli_connect_error(), mysqli_connect_errno());
		}
		return TRUE;
	}
	
	public function errorCode() {
		return $this->errorCode;
	}
	
	public function errorInfo() {
		return $this->errorInfo;
	}
	
	public function exec($statement) {
		if ($this->link->query($statement) === FALSE) {
			$this->errorCode = $this->link->errno;
			$this->errorInfo = array($this->link->sqlstate, $this->errorCode, $this->link->error);
			
			throw new KPDOException('SQL Error: ' . $statement . "<br>" . $this->link->error, $this->link->errno);
		}
		
		return  $this->link->affected_rows;
	}
	
	public function query($statement) {
		if (($this->result = $this->link->query($statement)) === FALSE) {
			$this->errorCode = $this->link->errno;
			$this->errorInfo = array($this->link->sqlstate, $this->errorCode, $this->link->error);
			
			throw new KPDOException('SQL Error: ' . $statement . "<br>" . $this->link->error, $this->link->errno);
		}
		
		return new MysqlStatement($statement, $this->result, $this->link);
	}
	
	public function prepare($statement, $driver_options = array()) {
		$stmt = new MysqlStatement($statement, NULL, $this->link);
		$stmt->setDriverOption($driver_options);
		return $stmt;
	}
	
	public function beginTransaction() {
		if (!$this->link->autocommit(FALSE)) {
			$this->inTransaction = FALSE;
			
			$this->errorCode = $this->link->errno;
			$this->errorInfo = array($this->link->sqlstate, $this->errorCode, $this->link->error);
			
			throw new KPDOException("Transaction Error" . $this->link->error, $this->link->errno);
		}
		$this->inTransaction = TRUE;
		return TRUE;
	}
	
	public function commit() {
		if (!$this->link->commit()) {
			$this->errorCode = $this->link->errno;
			$this->errorInfo = array($this->link->sqlstate, $this->errorCode, $this->link->error);
			
			throw new KPDOException("Transaction Error" . $this->link->error, $this->link->errno);
		}
		$this->inTransaction = FALSE;
		return TRUE;
	}
	
	public function rollBack() {
		if (!$this->link->rollback()) {
			$this->errorCode = $this->link->errno;
			$this->errorInfo = array($this->link->sqlstate, $this->errorCode, $this->link->error);
						
			throw new KPDOException("Transaction Error" . $this->link->error, $this->link->errno);
		}
		$this->inTransaction = FALSE;
		return TRUE;
	}
	
	public function inTransaction() {
		return $this->inTransaction;
	}
	
	public function lastInsertId($name = NULL) {
		$stmt = $this->query("SELECT LAST_INSERT_ID() AS \"ID\"");
		$row = $stmt->fetch(KPDO::FETCH_ASSOC);
		$id = $row['ID'];
		return $id;
	}
	
	public function getDriverOption() {
		return $this->driver_option;
	}
	
	public function setDriverOption($driver_option) {
		$this->driver_option = $driver_option;
	}
	
	public function createTable($name, $fields, $drop = FALSE, $driver_options = array()) {
		$query = "";
		
		$pk = array();
		$uq = array();
		$index = array();
		
		$uq_group = "";
		$index_group = "";
		
		if ($drop) {
			$query = "DROP TABLE IF EXISTS $name;";
		}
		$query .= "CREATE TABLE IF NOT EXISTS $name (";
		foreach ($fields as $field) {
		    $query .= '`' . $field[0] . '`';
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
					$query .= " TEXT ";
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
				case KPDO::TYPE_DATETIME:
					$query .= " DATETIME ";
					break;
				case KPDO::TYPE_BOOLEAN:
					$query .= " BOOLEAN ";
					break;
				case KPDO::TYPE_TEXT:
					$query .= " TEXT ";
					break;
				default: {
					$this->errorCode = $this->link->errno;
					$this->errorInfo = array($this->link->sqlstate, $this->errorCode, $this->link->error);
						
					throw new KPDOException("SQL Error: Tipo " . $field[1][0] . "non definito.");
				}
				break;
			}

			if (isset($field[2])) {
				if (($field[2] & KPDO::NOT_NULL) == KPDO::NOT_NULL) {
					$query .= " NOT NULL ";
				} 
				if (($field[2] & KPDO::AUTO_INCREMENT) == KPDO::AUTO_INCREMENT) {
					$query .= " AUTO_INCREMENT ";
				}
				if (($field[2] & KPDO::PRIMARY_KEY) == KPDO::PRIMARY_KEY) {
					$pk[] = $field[0];
				}
				if (($field[2] & KPDO::UNIQUE) == KPDO::UNIQUE) {
					$uq[] = $field[0];
					$uq_group .= $field[0]."_";
				}
				if (($field[2] & KPDO::INDEX) == KPDO::INDEX) {
					$index[] = $field[0];
					$index_group .= $field[0]."_";
				}
			}
			
			$query .= ",";
		}
		
		if(isset($pk) && (count($pk) > 0)) {
			$query .= "PRIMARY KEY( ";
			foreach ($pk as $field) {
			    $query .= '`' . $field . "`,";
			}
			
			$query = substr($query, 0, -1);
			
			$query .= "),";
		}
		if(isset($uq) && (count($uq) > 0)) {
			$query .= "UNIQUE KEY $uq_group ( ";
			foreach ($uq as $field) {
			    $query .= '`' . $field . "`,";
			}
			
			$query = substr($query, 0, -1);
			
			$query .= "),";
		} 
		if(isset($index) && (count($index) > 0)) {
			$query .= "INDEX $index_group ( ";
			foreach ($index as $field) {
			    $query .= '`' . $field . "`,";
			}
				
			$query = substr($query, 0, -1);
				
			$query .= "),";
		}
		
		$query = substr($query, 0, -1);
		
		$query .= ") ENGINE=InnoDB";
		
		return $this->exec($query);
	}
	
	public function createSchema($schema, $drop = FALSE) {
		$this->beginTransaction();
		try {
			if ($drop) {	
				$this->exec("DROP SCHEMA IF EXISTS $schema");
			}
			$this->exec("CREATE SCHEMA IF NOT EXISTS $schema");
		} catch (KPDOException $e) {
				 $this->rollBack();
				 return FALSE;
		}
		
		return $this->commit();
	}
	
	public function selectSchema($schema) {
		if (!$this->link->select_db($schema)) {
			$this->errorCode = $this->link->errno;
			$this->errorInfo = array($this->link->sqlstate, $this->errorCode, $this->link->error);
			
			throw new KPDOException("SQL Error: Impossibile selezionare il database " . $schema . ".<br>" . $this->link->error, $this->link->errno);
		}
		$this->database = $schema;
	}
	
	public function deleteTable($name) {
		$this->exec("DROP TABLE IF EXISTS $name;");
	}
	
	public function describeTable($name) {
		$stmt = $this->query("DESCRIBE " . $name);
		
		$fields = array();
		while ($result = $stmt->fetch()) {
			$fields[] = array('field' => $result['Field'], 'type' => $result['Type'], 'is_null' => $result['Null'], 'key' => $result['Key'], 'default_value' => ($result['Default'])? $result['Default'] : '', 'extra' => $result['Extra']);
		}
		
		return $fields;
	}
}
?>
