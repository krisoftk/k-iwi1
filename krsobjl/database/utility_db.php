<?php
class UtilityDB
{  
	const SELECT_MODULE_id = 'SELECT ID FROM t:krs_modules WHERE NAME = :name';
	const SELECT_TABLE_name = 'SELECT DATABASE_ID FROM t:krs_tables WHERE NAME = :name ';
	const SELECT_TABLE_name_module = 'SELECT DATABASE_ID FROM t:krs_tables WHERE NAME = :name AND MODULE_ID = :module_id ';
	const SELECT_DB_module = 'SELECT d.ID AS DATABASE_ID FROM t:krs_databases, t:krs_modules WHERE d.MODULE_ID = m.ID AND m.NAME = :name';
	const SELECT_DB_id = 'SELECT TYPE, IP, PORT, USERNAME, PASSWORD, NAME FROM t:krs_databases WHERE ID = :id';
	
	static function getDBfromModule($module) {
		try {
			global $db;
				
			$stmt = $db->prepare(UtilityDB::SELECT_DB_module);
			$stmt->bindTable("krs_databases", "krs_databases", "d");
			$stmt->bindTable("krs_modules", "krs_modules", "m");
			$stmt->bindParam(":name", $module, KPDO::TYPE_VARCHAR);
			$stmt->execute();
		
			if (($module != "") && ($stmt->rowCount() == 0)) {
				trigger_error("Module $module not found", E_USER_WARNING);
				return FALSE;
			} else {
				$table_info = $stmt->fetch();
				$conn_db = UtilityDB::get_connection_DB($table_info['DATABASE_ID']);
				return $conn_db;
			}
		} catch (KPDOException $e) {
			trigger_error((string) $e, E_USER_WARNING);
			return FALSE;
		}
	}
	
	static function getDBfromTable($table, $module="") {
		try {
			global $db;
			
			$stmt_mod= $db->prepare(UtilityDB::SELECT_MODULE_id);
			$stmt_mod->bindTable("krs_modules", "krs_modules");
			$stmt_mod->bindParam(":name", $module, KPDO::TYPE_VARCHAR);
			$stmt_mod->execute();

			if (($stmt_mod->rowCount() == 0) && ($module!="")) {
				trigger_error("Module $module not found", E_USER_WARNING);
				return FALSE;
			} else {
					if (!$module==""){
						$table_module = $stmt_mod->fetch();
						$stmt = $db->prepare(UtilityDB::SELECT_TABLE_name_module);
						$stmt->bindTable("krs_tables", "krs_tables");
						$stmt->bindParam(":name", $table, KPDO::TYPE_VARCHAR);
						$stmt->bindParam(":module_id", $table_module['ID'], KPDO::TYPE_VARCHAR);
						$stmt->execute();
					} else { 
						$stmt = $db->prepare(UtilityDB::SELECT_TABLE_name);
						$stmt->bindTable("krs_tables", "krs_tables");
						$stmt->bindParam(":name", $table, KPDO::TYPE_VARCHAR);
						$stmt->execute();
					}		
					if ($stmt->rowCount() == 0) {
						echo "Tabella $table non trovata";
						return FALSE;
					} else {
						$table_info = $stmt->fetch();
						$conn_db = UtilityDB::get_connection_DB($table_info['DATABASE_ID']);
						return $conn_db;	
					}
			}
		} catch (KPDOException $e) {
			trigger_error((string) $e, E_USER_WARNING);
			return FALSE;
		}
	}
	
	static function get_connection_DB($database_id) {
		try {
			global $db;
			
			$stmt = $db->prepare(UtilityDB::SELECT_DB_id);
			$stmt->bindTable("krs_databases", "krs_databases");
			$stmt->bindParam(":id", $database_id, KPDO::TYPE_INTEGER);

			$stmt->execute();
			
			$database_info = $stmt->fetch();
				
			$db2 = new KPDO($database_info['TYPE'], $database_info['IP'], $database_info['PORT'], $database_info['USERNAME'], $database_info['PASSWORD'], $database_info['NAME']); 
							
			return $db2;
		} catch (KPDOException $e) {
			trigger_error((string) $e, E_USER_WARNING);
			return FALSE;
		}
	}
	
	// Creates a framework table if it's missing, registering it in krs_tables
	// and krs_fields like the installer does: needed by installations made
	// before the table existed. $fields: array(NAME, KPDO type, type parameter
	// or null, KPDO attributes). Returns true if it created the table.
	static function ensureTable($db, $name, $fields) {
		$stmt = $db->prepare("SELECT ID FROM t:krs_tables WHERE NAME = :name");
		$stmt->bindTable("krs_tables", "krs_tables");
		$stmt->bindParam(":name", $name, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		if ($stmt->rowCount()) {
			return false;
		}

		$stmt = $db->prepare("INSERT INTO t:krs_tables (NAME, DATABASE_ID, MODULE_ID) VALUES (:name, 1, 1)");
		$stmt->bindTable("krs_tables", "krs_tables");
		$stmt->bindParam(":name", $name, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		$table_id = $db->lastInsertId();

		$definition = array();

		foreach ($fields as $field) {
			list($field_name, $type_name, $type_param, $attribute) = $field;

			$type = UtilityDB::findType($db, $type_name, $type_param);

			// no attributes = 0 (KPDO binds don't accept null)
			$attribute = (int) $attribute;

			$stmt = $db->prepare("INSERT INTO t:krs_fields (NAME, ATTRIBUTE, TYPE_ID, TABLE_ID) VALUES (:name, :attribute, :type_id, :table_id)");
			$stmt->bindTable("krs_fields", "krs_fields");
			$stmt->bindParam(":name", $field_name, KPDO::TYPE_VARCHAR);
			$stmt->bindParam(":attribute", $attribute, KPDO::TYPE_VARCHAR);
			$stmt->bindParam(":type_id", $type['ID'], KPDO::TYPE_INTEGER);
			$stmt->bindParam(":table_id", $table_id, KPDO::TYPE_INTEGER);
			$stmt->execute();

			$definition[] = array($field_name, array($type['NAME'], $type['PARAM']), $attribute);
		}

		$db->createTable($name, $definition);

		return true;
	}

	private static function findType($db, $name, $param) {
		$stmt = $db->prepare("SELECT * FROM t:krs_types WHERE NAME = :name");
		$stmt->bindTable("krs_types", "krs_types");
		$stmt->bindParam(":name", $name, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		while (($type = $stmt->fetch(KPDO::FETCH_ASSOC))) {
			if ($param === null || $type['PARAM'] == $param) {
				return $type;
			}
		}

		throw new KPDOException("Tipo $name non trovato in krs_types");
	}
}
?>