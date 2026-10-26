<?php
class MysqlStatement implements iStatement {
	
	private $queryString;
	private $result;
	private $link;
	
	private $bindColumn = array();
	private $bindParam = array();
	private $bindTable = array();
	
	private $limit = false;
	private $n;
	private $m;
	
	private $errorCode;
	private $errorInfo;
	
	private $driver_option;
	
	private $fetch_style = KPDO::FETCH_ASSOC;
	private $fetchAll_style = KPDO::FETCH_ASSOC;
	
	function __construct($queryString, $result = NULL, $link = NULL) {
		$this->queryString = $queryString;
		$this->result = $result;
		$this->link = $link;
	}
	
	function getDriverOption() {
		return $this->driver_option;
	}
	
	function setDriverOption($driver_option) {
		$this->driver_option = $driver_option;
	}
	
	function fetch($fetch_style = NULL) {
		if (!isset($fetch_style)) {
			 $fetch_style = $this->fetch_style;
		}
		
		switch ($fetch_style) {
			case KPDO::FETCH_NUM:
				$style = MYSQLI_NUM;
				$row = $this->result->fetch_array($style); 
			break;
			case KPDO::FETCH_ASSOC:
				$style = MYSQLI_ASSOC;
				$row = $this->result->fetch_array($style);
			break;
			case KPDO::FETCH_BOTH:
				$style = MYSQLI_BOTH;
				$row = $this->result->fetch_array($style);
			break;
			case KPDO::FETCH_OBJECT:
				$row = $this->result->fetch_object();
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
				$style = MYSQLI_NUM;
				break;
			case KPDO::FETCH_ASSOC:
				$style = MYSQLI_ASSOC;
				break;
			case KPDO::FETCH_BOTH:
				$style = MYSQLI_BOTH;
				break;
			default:
				throw new KPDOException("Fetch style non corretto");
				break;
		}
		
		if ($style) {
			if (method_exists($this->result, 'fetch_all')) {
				$rows = $this->result->fetch_all($style);
			} else {
				while ($row = $this->result->fetch_array($style)) {
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
	
	function bindTable($parameter, $variable, $as = NULL, $limit = 1) {
		$temp = array();
		$temp["param"] = $parameter;
		$temp["value"] = $variable;
		$temp["as"] = $as;
		$temp["limit"] = $limit;
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
	
			if (!is_null($table["as"])) {
				$value = $table["value"] . " AS " . $table["as"];
			} else {
				$value = $table["value"];
			}
				
			$limit = $table['limit'];
				
			$query = preg_replace("/$param/", " $value ", $query, $limit, $count);
	
			if ($count == 0) {
				throw new KPDOException("Bind Error: Paramentro $param non presente");
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
					$value = '"'.addslashes((string) $parameter["value"]).'"';
				break;
				case KPDO::TYPE_CHAR:
					$value = '"'.addslashes((string) $parameter["value"]).'"';
				break;
				case KPDO::TYPE_TEXT:
					$value = '"'.addslashes((string) $parameter["value"]).'"';
				break;
				case KPDO::TYPE_DATE:
					$value = '"'.addslashes((string) $parameter["value"]).'"';
				break;
				case KPDO::TYPE_DATETIME:
					$value = '"'.addslashes((string) $parameter["value"]).'"';
				break;
				case KPDO::TYPE_TIME:
					$value = '"'.addslashes((string) $parameter["value"]).'"';
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
		
		if ($this->limit) {
			$query = $query . " LIMIT $this->n, $this->m";
		}

		if ($debug) {
			trigger_error($query);
		}
		
		if (($this->result = $this->link->query($query)) === FALSE) {
			$this->errorCode = $this->link->errno;
			$this->errorInfo = array($this->link->sqlstate, $this->errorCode, $this->link->error);
			
			throw new KPDOException('SQL Error: ' . $query . "<br>" . $this->link->error, $this->link->errno);
		}
		
		return TRUE;
	}
	
	function columnCount() {
		return $this->result->field_count;
	}
	
	function rowCount() {
		// For SELECT queries $this->result is a mysqli_result (num_rows);
		// for INSERT/UPDATE/DELETE mysqli::query() returns true instead, and the
		// count has to come from the connection (affected_rows).
		if ($this->result instanceof mysqli_result) {
			return $this->result->num_rows;
		}

		return $this->link->affected_rows;
	}
	
	// they end up in the query text: always integers
	function limit($n, $m) {
		$this->limit = true;
		$this->n = max(0, (int) $n);
		$this->m = max(0, (int) $m);
	}

	// Replaces all the parameters in a single pass over the original query.
	// Replacing them one at a time, a value containing the name of another
	// parameter (e.g. the text ":id") was replaced in turn, breaking the
	// quotes. The alternation is sorted by the longest name, so ":id_user"
	// isn't taken for ":id".
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

	// Value for a numeric (or anyway non-text) parameter: it goes into the
	// query without quotes, so it must really be a number. null or an empty
	// string become NULL (they used to produce an invalid query); anything
	// else is rejected instead of being put raw into the query (SQL injection).
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