<?php
class ODBCStatement implements iStatement {
//
//ODBC table select syntax:
//      Power IBM   : LIBRARY.TABLE (ex: QGPL.TABLE). In the ODBC.ini config leave blank database option
//      Mssql       : TABLE (ex: table). In the ODBC.ini config set database option to the database name
//
	private $queryString;
	private $queryStringCount;
	private $result;
	private $link;
	
	private $bindColumn = array();
	private $bindParam = array();
	private $bindTable = array();
	
	private $errorCode;
	private $errorInfo;
	
	private $driver_options;
	
	private $fetch_style = KPDO::FETCH_ASSOC;
	private $fetchAll_style = KPDO::FETCH_ASSOC;
	
	private $num_row;
	
	function __construct($queryString, $result = NULL, $link = NULL) {
		$this->queryString = $queryString;
		$this->result = $result;
		$this->link = $link;
	}
	
	function getDriverOption() {
		return $this->driver_options;
	}
	
	function setDriverOption($driver_options) {
		$this->driver_options = $driver_options;
	}
	
	function fetch($fetch_style = NULL) {
		if (!isset($fetch_style)) {
			 $fetch_style = $this->fetch_style;
		}
		
		switch ($fetch_style) {
			case KPDO::FETCH_NUM:
				$row = $this->result->fetch(PDO::FETCH_NUM);
			break;
			case KPDO::FETCH_ASSOC:
				$row = $this->result->fetch(PDO::FETCH_ASSOC);
			break;
			case KPDO::FETCH_BOTH:
				$row = $this->result->fetch(PDO::FETCH_BOTH);
			break;
			case KPDO::FETCH_OBJECT:
				$row = $this->result->fetch(PDO::FETCH_OBJ);
			break;
			default:
				throw new KPDOException("Fetch style non corretto");
			break;
		}
		
		if($row) {
			foreach ($this->bindColumn as $column => $bind) {
				if(isset($row[$column])) {
					$bind[1] = $row[$column];
				} else {
					throw new KPDOException("Bind Error: Colonna $column non esiste");
				}
			}
		}
		
		return $row;
	}
	
	function fetchAll($fetch_style = NULL) {
		if (!isset($fetch_style)) {
			$fetch_style = $this->fetchAll_style;
		}
		
		$rows = array();
		$style = false;
		
		switch ($fetch_style) {
			case KPDO::FETCH_NUM:
				$style = PDO::FETCH_NUM;
				break;
			case KPDO::FETCH_ASSOC:
				$style = PDO::FETCH_ASSOC;
				break;
			case KPDO::FETCH_BOTH:
				$style = PDO::FETCH_BOTH;
				break;
			default:
				throw new KPDOException("Fetch style non corretto");
				break;
		}
		
		if ($style) {
			if (method_exists($this->result, 'fetch_all')) {
				$rows = $this->result->fetch_all($style);
			} else {
				while ($row = $this->result->fetch(PDO::FETCH_ASSOC)) {
					$rows[] = $row;
				}
			}	
		}
		
		return $rows;
	}
	
	function bindColumn($column, &$param) {
		$temp = array();
		$temp[1] =& $param;
		$this->bindColumn[$column] = $temp;
	}
	
	function bindParam($parameter, &$variable, $data_type) {
		$temp = array();
		$temp["param"] = $parameter;
		$temp["value"] =& $variable;
		$temp["type"] = $data_type;
		$this->bindParam[$parameter] = $temp;
	}
	
	function bindTable($parameter, $variable, $as = NULL) {
		$temp = array();
		$temp["param"] = $parameter;
		$temp["value"] = preg_split('/[.]/', $variable);
		$temp["as"] = $as;
		$this->bindTable[$parameter] = $temp;
	}
	
	function errorCode() {
		return $this->errorCode;
	}
	
	function errorInfo() {
		return $this->errorInfo;
	}
	
	function execute($input_parameters = NULL, $debug = false) {
		$query = $this->queryString;
		
		uasort($this->bindTable, function($a, $b) {
			return strlen($b["param"]) - strlen($a["param"]);
		});
		
		foreach ($this->bindTable as $table) {
			$param = $table["param"];
			if(substr($param, 0, 2) != "t:") {
				$param = "t:".$param;
			}
	
			$database = '';
			$tabella = '';
			$db_tb = '';
	
			if (count($table["value"]) == 2) {
				$database = $table["value"][0];
				$tabella = $table["value"][1];
				$db_tb = $database . ".\"" . $tabella . "\"";
			} else {
				$tabella = $table["value"][0];
				$db_tb = "\"" . $tabella . "\"";
			}
			
			if (!is_null($table["as"])) {
				$value = $db_tb . " AS " . $table["as"];
			} else {
				$value = $db_tb;
			}
				
			$query = str_replace($param, $value, $query, $count);
	
			if ($count == 0) {
				throw new KPDOException("Bind Error: Paramentro $param non presente");
			}
				
			$pos = stripos($query, "insert");
			if ($pos !== FALSE && $pos == 0) {
				$this->driver_options['table'] = $tabella;
				$pdo = $this->driver_options['pdo'];
				$pdo->setDriverOption($this->driver_options);
			}
		}
			
		$values = array();

		foreach ($this->bindParam as $parameter) {
			$param = $parameter["param"];
			if(substr($param, 0, 1) != ":") {
				$param = ":".$param;
			}

			switch ($parameter["type"]) {
				case KPDO::TYPE_VARCHAR:
					$value = "'".str_replace("'", "''", (string) $parameter["value"])."'";
				break;
				case KPDO::TYPE_TIME:
					$value = "'".addslashes((string) $parameter["value"])."'";
				break;
				case KPDO::TYPE_TIMESTAMP:
					$value = "'".addslashes((string) $parameter["value"])."'";
				break;
				default:
					$value = $this->numericLiteral($param, $parameter["value"]);
				break;
			}
	
			$values[$param] = $value;
		}

		$query = $this->replaceParams($query, $values);
		
		if ($debug) {
			trigger_error($query);
		}
		
		$this->result = $this->link->query($query);
		$this->queryStringCount = $query;
		if($this->result === FALSE){
			$this->errorCode = $this->link->errorCode();
			$this->errorInfo = $this->link->errorInfo();		
 			throw new KPDOException('SQL Error: ' . $query. 'Error Code: '. print_r($this->errorCode,true). 'Error Info: '.print_r($this->errorInfo,true));
		}
		
		return TRUE;
	}
	
	function columnCount() {
		return $this->result->columnCount();
	}
	
	function rowCount() {
		$pos = stripos($this->queryStringCount, "select");
		if ($pos !== FALSE && $pos == 0) {
			$query = 'SELECT COUNT(*) AS "cnt" FROM (' . $this->queryStringCount . ') AS "temp"';
			$result = $this->link->query($query);
		
			$r = $result->fetch(PDO::FETCH_ASSOC);
			return $r['cnt'];
		} else {
			return $this->result->rowCount();
		}
	}
	
	function limit($n, $m) {
		/* TODO */
	}

	// Sostituisce tutti i parametri in un solo passaggio sulla query
	// originale. Sostituendoli uno alla volta, un valore che conteneva il
	// nome di un altro parametro (es. il testo ":id") veniva a sua volta
	// sostituito, rompendo le virgolette. L'alternanza e' ordinata dal nome
	// piu' lungo, cosi' ":id_user" non viene preso per ":id".
	private function replaceParams($query, $values) {
		if (!$values) {
			return $query;
		}

		$names = array_keys($values);

		usort($names, function ($a, $b) {
			return strlen($b) - strlen($a);
		});

		$pattern = '/' . implode('|', array_map(function ($name) {
			return preg_quote($name, '/');
		}, $names)) . '/';

		$found = array();

		$query = preg_replace_callback($pattern, function ($match) use ($values, &$found) {
			$found[$match[0]] = true;

			return $values[$match[0]];
		}, $query);

		foreach ($names as $name) {
			if (!isset($found[$name])) {
				throw new KPDOException("Bind Error: Paramentro $name non presente");
			}
		}

		return $query;
	}

	// Valore per un parametro di tipo numerico (o comunque non testuale):
	// finisce nella query senza virgolette, quindi deve essere davvero un
	// numero. null o stringa vuota diventano NULL (prima producevano una
	// query non valida); qualunque altra cosa e' rifiutata invece di essere
	// inserita grezza nella query (SQL injection).
	private function numericLiteral($param, $value) {
		if (is_bool($value)) {
			return $value ? '1' : '0';
		}

		if ($value === null || (is_string($value) && trim($value) === '')) {
			return 'NULL';
		}

		if (is_int($value) || is_float($value) || (is_string($value) && is_numeric(trim($value)))) {
			return trim((string) $value);
		}

		throw new KPDOException("Bind Error: valore non numerico per il parametro $param");
	}
}