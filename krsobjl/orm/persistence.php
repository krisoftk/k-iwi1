<?php
class Persistence {
	
	protected $registry;
	
	public function __construct($registry) {
		$this->registry = $registry;
	}
	
	public function __get($key) {
		return $this->registry->get($key);
	}
	
	public function __set($key, $value) {
		$this->registry->set($key, $value);
	}
	
	public function insert($object) {
		if (!$this->registry->has("query_insert_".$object->getModule()."_".$object->getTable())) {
			$statement = array();
			$statement['MODULE'] = $object->getModule();
			$statement['TABLE'] = $object->getTable();
			
			$query = 'INSERT INTO t:table (';
			
			foreach ($object->getFields() as $field => $value) {
				$query .= $field . ',';
			}
			$query = substr($query, 0, -1);
			
			$query .= ') VALUES (';
			
			foreach ($object->getFields() as $field => $value) {
				$query .= ':' . $field . ',';
			}
			$query = substr($query, 0, -1);
			
			$query .= ')';
			
			$statement['QUERY'] = $query;
			
			$statement['TABLES'][] = array(
				'TOKEN' => 'table',
				'NAME'	=> $object->getTable()
			);
			
			foreach ($object->getFields() as $field => $value) {
				$statement['PARAMS'][] = array(
					'TOKEN' => $field,
					'VALUE'	=> $value,
					'TYPE'	=> $object->getInstanceOf($field)
				);
			}
				
			$this->namedQuery("insert_".$object->getModule()."_".$object->getTable(), $statement);
			trigger_error("1");
		} 
		trigger_error("2");
		return $this->{"query_insert_".$object->getModule()."_".$object->getTable()}->execute();
	}
	
	public function update($object) {
		$statement = array();
		$statement['MODULE'] = $object->getModule();
		$statement['TABLE'] = $object->getTable();
		
		$query = 'UPDATE t:table SET ';
		
		foreach ($object->getData() as $field => $value) {
			if ($field != $object->getKey()) {
				$query .= $field . ' = :' . $field . ',';
			}
		}
		$query = substr($query, 0, -1);
		
		$query .= ' WHERE ' . $object->getKey() . ' = :' . $object->getKey();
		
		$statement['QUERY'] = $query;
	
		$statement['TABLES'][] = array(
			'TOKEN' => 'table',
			'NAME'	=> $object->getTable()
		);
		
		foreach ($object->getData() as $field => $value) {
			$statement['PARAMS'][] = array(
				'TOKEN' => $field,
				'VALUE'	=> $value,
				'TYPE'	=> $object->getInstanceOf($field)
			);
		}
			
		return $this->query($statement);
	}
	
	public function delete($object) {
		$statement = array();
		$statement['MODULE'] = $object->getModule();
		$statement['TABLE'] = $object->getTable();
		
		$query = 'DELETE FROM t:table';
		$query .= ' WHERE ' . $object->getKey() . ' = :' . $object->getKey();
		
		$statement['QUERY'] = $query;
	
		$statement['TABLES'][] = array(
			'TOKEN' => 'table',
			'NAME'	=> $object->getTable()
		);
		
		foreach ($object->getData() as $field => $value) {
			$statement['PARAMS'][] = array(
				'TOKEN' => $field,
				'VALUE'	=> $value,
				'TYPE'	=> $object->getInstanceOf($field)
			);
		}
			
		return $this->query($statement);
	}
	
	public function LogicalDelete($object) {
		/* TODO */
	}
	
	public function entity($entity) {
		$path = explode('/', $entity);
		
		if (count($path) == 1) {
			array_splice($path, 0, 0, 'orm');
		}
		
		array_splice($path, 1, 0, 'model');
		$path = implode('/', $path);
	
		$file  = DIR_MODULE . $path . '.php';
		$class = "entity" . preg_replace('/[^a-zA-Z0-9]/', '', $entity);
	
		if (file_exists($file)) {
			include_once($file);
			
			$this->registry->set('entity_' . str_replace('/', '_', $entity), new $class());
		} else {
			trigger_error('Error: Could not load entity ' . $entity . '!');
			exit();
		}
	}
	
	public function query($statement) {
		$stmt = $this->constructStatement($statement);
		
		$stmt->execute();
		
		return $stmt;
	}
	
	public function namedQuery($name, $statement) {
		if (!$this->registry->has('query_' . $name)) {
			$this->registry->set('query_' . $name, $this->constructStatement($statement));
		}
		return $this->{'query_' . $name};
	}
	
	private function checkStatement($statement) {
		/*case-sensitive check*/
		$new_statement = array();
		foreach ($statement as $key => $value) {
			$new_statement[strtoupper($key)] = $value;
		}
		
		return $new_statement;
	}
	
	private function constructStatement($statement) {
		$statement = $this->checkStatement($statement);
		
		/*database selection*/
		if (!$this->registry->has('database_' . $statement['MODULE'] . '_' . $statement['TABLE'])) {
			$stmt_database = $this->db->prepare("
				SELECT d.TYPE, d.NAME, d.IP, d.PORT, d.USERNAME, d.PASSWORD
				FROM t:databases d LEFT JOIN t:modules m ON (d.MODULE_ID = m.ID) LEFT JOIN t:tables t ON (t.MODULE_ID = m.ID)
				WHERE t.NAME = :table AND m.NAME = :module
			");
				
			$stmt_database->bindTable("databases", "krs_databases");
			$stmt_database->bindTable("modules", "krs_modules");
			$stmt_database->bindTable("tables", "krs_tables");
			$stmt_database->bindParam("table", $statement['TABLE'], KPDO::TYPE_VARCHAR); 
			$stmt_database->bindParam("module", $statement['MODULE'], KPDO::TYPE_VARCHAR);
				
			$stmt_database->execute();
				
			$database = $stmt_database->fetch();
				
			/*load the connection*/
			$this->load->database($statement['MODULE'] . '_' . $statement['TABLE'], $database['TYPE'], $database['IP'], $database['PORT'], $database['USERNAME'], $database['PASSWORD'], $database['NAME']);
		}
		
		/*build the statement*/
		$stmt = $this->{'database_' . $statement['MODULE'] . '_' . $statement['TABLE']}->prepare($statement['QUERY']);
		
		foreach ($statement['TABLES'] as $table) {
			$stmt->bindTable($table['TOKEN'], $table['NAME']);
		}
		
		foreach ($statement['PARAMS'] as $param) {
			$stmt->bindParam($param['TOKEN'], $param['VALUE'], $param['TYPE']);
		}
		
		return $stmt;
	}
}