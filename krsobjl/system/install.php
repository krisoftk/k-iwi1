<?php
abstract class Install {

	protected $registry;
	protected $module_id;
	protected $databases;
	protected $tables;
	protected $module;
	protected $menu_items;/*deprecato lasciato solo per compatibilità con vecchi installer*/
	protected $users;
	protected $users_id;
	protected $groups;
	protected $groups_id;
	protected $progsgroups;
	protected $progs;
	protected $menuItems;
	
	public function __construct($registry) {
		$this->registry = $registry;
	}
	
	public function __get($key) {
		return $this->registry->get($key);
	}
	
	public function __set($key, $value) {
		$this->registry->set($key, $value);
	}
	
	protected function getUserId($nome) {
		return $this->users_id[$nome];
	}
	
	// I nomi dei moduli sono unici: l'accesso all'admin, ad esempio, spetta ai
	// gruppi del modulo che si chiama "admin", quindi un secondo modulo con lo
	// stesso nome ne erediterebbe i poteri. Se il nome e' gia' preso
	// l'installazione si ferma.
	protected function createModule($name, $parent = 0) {
		$stmt = $this->db->prepare("SELECT ID FROM t:krs_modules WHERE NAME = :module_name");
		$stmt->bindTable("krs_modules", "krs_modules");
		$stmt->bindParam("module_name", $name, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		if ($stmt->fetch()) {
			trigger_error("Installazione interrotta: esiste gia' un modulo di nome '" . $name . "'", E_USER_WARNING);

			return false;
		}

		$stmt = $this->db->prepare("INSERT INTO t:krs_modules (PARENT_ID, NAME)	VALUES (:module_id, :module_name)");
		$stmt->bindTable("krs_modules", "krs_modules");
		$stmt->bindParam("module_id", $parent, KPDO::TYPE_INTEGER);
		$stmt->bindParam("module_name", $name, KPDO::TYPE_VARCHAR);
		$stmt->execute();
		$this->module_id = $this->db->lastInsertId();

		return true;
	}
	
	protected function deleteModule() {
		$stmt = $this->db->prepare("SELECT * FROM t:krs_modules WHERE NAME = :module_name");
		$stmt->bindTable("krs_modules", "krs_modules");
		$stmt->bindParam("module_name", $this->module, KPDO::TYPE_VARCHAR);
		$stmt->execute();
		$module = $stmt->fetch();
		$this->module_id = $module['ID'];
	
		$stmt = $this->db->prepare("DELETE FROM t:krs_modules WHERE NAME = :module");
		$stmt->bindTable("krs_modules", "krs_modules");
		$stmt->bindParam("module", $this->module, KPDO::TYPE_VARCHAR);
		$stmt->execute();
	}
	
//Funzione modificata per permettere di indicare un database differente alle tabelle.
//nuova sintassi del nome tabella : nome,database 
//In questo modo il sistema aggiungerà la tabella anome di database scelto
	protected function createTable($tables) {
	
		foreach ($tables as $table => $instructions) {
			
			$campi = explode("/", $table);
			if (isset($campi[1])) {
		  		$database = $campi[1];
		  		$modulo_id = $this->module_id;
			} else {
				$database = $this->config->get('database.name');
				$modulo_id = 1;
				if ($database == '') {
					$database = "Krs_framework";
				}
			}
			
			$table_name = $campi[0];				
			$stmt = $this->db->prepare("SELECT * FROM t:krs_databases WHERE NAME = :name AND MODULE_ID = :module_id");
			$stmt->bindTable("krs_databases", "krs_databases");
			$stmt->bindParam("name", $database, KPDO::TYPE_VARCHAR);
			$stmt->bindParam("module_id", $modulo_id, KPDO::TYPE_INTEGER);
			$stmt->execute();
			
			$rows = $stmt->rowCount();
			if ($rows > 0) {
				$row = $stmt->fetch();
				$database_id = $row['ID'];
				$driver = $row['TYPE'];
				$host = $row['IP'];
				$port = $row['PORT'];
				$username = $row['USERNAME'];
				$password = $row['PASSWORD'];
			} else {
				$database_id = '';
				$driver = '';
				$host = '';
				$port = '';
				$username = '';
				$password = '';
			}
			
			$db = new KPDO($driver, $host, $port, $username, $password);
			$db->selectSchema($database);
			
			$stmt = $this->db->prepare("INSERT INTO t:krs_tables (NAME, DATABASE_ID, MODULE_ID) VALUES (:name, :database_id, :module_id)");
			$stmt->bindTable("krs_tables", "krs_tables");
			$stmt->bindParam("name", $table_name, KPDO::TYPE_VARCHAR);
			$stmt->bindParam("database_id", $database_id, KPDO::TYPE_INTEGER);
			$stmt->bindParam("module_id", $this->module_id, KPDO::TYPE_INTEGER);
		
			if (isset($instructions)) {
				$db->createTable($table_name,  $instructions);
			}
			$stmt->execute();
		}
	}
	
	protected function createDB($dbs) {
	
		foreach ($dbs as $dbf) {
			$driver 	= $dbf[0];
			$host 		= $dbf[1];
			$port 		= $dbf[2];
			$username 	= $dbf[3];
			$password 	= $dbf[4];
			$database 	= $dbf[5];
	
			$stmt = $this->db->prepare("INSERT INTO t:krs_databases (TYPE, NAME, IP, PORT, USERNAME, PASSWORD, MODULE_ID) VALUES (:type, :name, :ip, :port, :username, :password, :modulo_id)");
			$stmt->bindTable("krs_databases", "krs_databases");
			$stmt->bindParam("type", $driver, KPDO::TYPE_VARCHAR);
			$stmt->bindParam("name", $database, KPDO::TYPE_VARCHAR);
			$stmt->bindParam("ip", $host, KPDO::TYPE_VARCHAR);
			$stmt->bindParam("port", $port, KPDO::TYPE_VARCHAR);
			$stmt->bindParam("username", $username, KPDO::TYPE_VARCHAR);
			$stmt->bindParam("password", $password, KPDO::TYPE_VARCHAR);
			$stmt->bindParam("modulo_id", $this->module_id, KPDO::TYPE_INTEGER);
			$stmt->execute();
		}
	}
	
	protected function deleteTable($drop = true) {
		$table_name = "";
		$stmt_delete = $this->db->prepare("DELETE FROM t:krs_tables WHERE NAME = :name AND MODULE_ID = :module_id");
		$stmt_delete->bindTable("krs_tables", "krs_tables");
		$stmt_delete->bindParam("name", $table_name, KPDO::TYPE_VARCHAR);
		$stmt_delete->bindParam("module_id", $this->module_id, KPDO::TYPE_INTEGER);
		
		$stmt = $this->db->prepare("SELECT * FROM t:krs_tables WHERE MODULE_ID = :module_id");
		$stmt->bindTable("krs_tables", "krs_tables");
		$stmt->bindParam("module_id", $this->module_id, KPDO::TYPE_VARCHAR);
		$stmt->execute();
		
		while ($row = $stmt->fetch()) {
			$table_name = $row['NAME'];
			$db = UtilityDB::getDBfromTable($table_name);
			if ($drop) {
				$db->deleteTable($table_name);
			}
			
			$stmt_delete->execute();
		}
	}

	protected function deleteDB($drop = true) {

		$stmt_delete = $this->db->prepare("DELETE FROM t:krs_databases WHERE MODULE_ID = :module_id");
		$stmt_delete->bindTable("krs_databases", "krs_databases");
		$stmt_delete->bindParam("module_id", $this->module_id, KPDO::TYPE_INTEGER);
	
		$stmt_delete->execute();
	
	}	
	
	protected function createProg($progs) {
	
		$title_prog = "";
		$prog = "";
		$pattern = "";
		$stmt_prog = $this->db->prepare("INSERT INTO t:krs_progs
			(TITLE, PROG, PUBLISHED, ORDERING, PATTERN) 
			VALUES 
			(:title_prog, :prog, 1, 1, :pattern)");
		$stmt_prog->bindTable("krs_progs", "krs_progs");
		$stmt_prog->bindParam("title_prog", $title_prog, KPDO::TYPE_VARCHAR);
		$stmt_prog->bindParam("prog", $prog, KPDO::TYPE_VARCHAR);
		$stmt_prog->bindParam("pattern", $pattern, KPDO::TYPE_VARCHAR);
	
		$prog_id='';
		$stmt_progs_modules = $this->db->prepare("INSERT INTO t:krs_progs_modules_map
			(MODULE_ID, PROG_ID) 
			VALUES 
			(:module_id, :prog_id)");
		$stmt_progs_modules->bindTable("krs_progs_modules_map", "krs_progs_modules_map");
		$stmt_progs_modules->bindParam("module_id", $this->module_id, KPDO::TYPE_INTEGER);
		$stmt_progs_modules->bindParam("prog_id", $prog_id, KPDO::TYPE_INTEGER);
		
		foreach ($progs as $pg) {
			$title_prog = $pg[0];
			$prog 		= $pg[1];
			$pattern	= $pg[2];
			
			$stmt_prog->execute();
			$prog_id = $this->db->lastInsertId();
			
			$stmt_progs_modules->execute();
		}
	}

	protected function createMenuItem($menuItems) {
		$stmt = $this->db->prepare("SELECT * FROM t:krs_menu_type WHERE MENUTYPE = 'standard'");
		$stmt->bindTable("krs_menu_type", "krs_menu_type");
		$stmt->execute();
		$menu_type = $stmt->fetch();
		$menu_type_id = $menu_type['ID'];
	
		$title_prog = "";
		$stmt_menu = $this->db->prepare("INSERT INTO t:krs_menuitems
			(MENUTYPE_ID, PARENT_ID, TITLE, PUBLISHED, ORDERING)
			VALUES
			(1, :parent_id, :title_prog, 1, 1)");
		$stmt_menu->bindTable("krs_menuitems", "krs_menuitems");
		$stmt_menu->bindParam("title_prog", $title_prog, KPDO::TYPE_VARCHAR);
		$stmt_menu->bindParam("parent_id", $parent_id, KPDO::TYPE_INTEGER);
	
		$prog_prog='';
		$stmt_prog = $this->db->prepare("SELECT * FROM t:krs_progs WHERE PROG = :krs_prog_prog and ID IN (SELECT PROG_ID FROM t:krs_progs_modules_map WHERE MODULE_ID = :module )");
		$stmt_prog->bindTable("krs_progs", "krs_progs");
		$stmt_prog->bindParam("krs_prog_prog", $prog_prog, KPDO::TYPE_VARCHAR);
		$stmt_prog->bindTable("krs_progs_modules_map", "krs_progs_modules_map");
		$stmt_prog->bindParam("module", $this->module_id, KPDO::TYPE_VARCHAR);

		$prog_id='';
		$menuitems_id='';
		$stmt_prog_menu = $this->db->prepare("INSERT INTO t:krs_progs_menuitems_map
			(PROG_ID, MENUITEMS_ID)
			VALUES
			(:prog_id, :menuitems_id)");
		$stmt_prog_menu->bindTable("krs_progs_menuitems_map", "krs_progs_menuitems_map");
		$stmt_prog_menu->bindParam("prog_id", $prog_id, KPDO::TYPE_INTEGER);
		$stmt_prog_menu->bindParam("menuitems_id", $menuitems_id, KPDO::TYPE_INTEGER);
	
		foreach ($menuItems as $item) {
			$title_prog = $item[0];
			$parent = $item[2];

			if (isset($parents[$parent])) {
				$parent_id = $parents[$parent];
			} else {
				$parent_id = 0;
			}
			$stmt_menu->execute();
			$menuitems_id = $this->db->lastInsertId();
	
			$prog_prog = $item[1];
			
			$parents[$prog_prog] = $menuitems_id;
			
			$stmt_prog->execute();
			$prog_id='';
			while ($row = $stmt_prog->fetch()) { $prog_id = $row['ID']; }
			if (($prog_id!='')&&($menuitems_id!='')) { $stmt_prog_menu->execute(); }

		}
	}
		
/*FUNZIONE crateMenu DEPRECATA lasciata solo perchè utilizzati in alcuni install di moduli*/	
	protected function createMenu($menu_items) {
		$stmt = $this->db->prepare("SELECT * FROM t:krs_menu_type WHERE MENUTYPE = 'standard'");
		$stmt->bindTable("krs_menu_type", "krs_menu_type");
		$stmt->execute();
		$menu_type = $stmt->fetch();
		$menu_type_id = $menu_type['ID'];
	
		$menu_item = "";
		$stmt_menu = $this->db->prepare("INSERT INTO t:krs_menuitems
			(MENUTYPE_ID, PARENT_ID, TITLE, PUBLISHED, ORDERING)
			VALUES
			(1, :parent_id, :menu_item, 1, 1)");
		$stmt_menu->bindTable("krs_menuitems", "krs_menuitems");
		$stmt_menu->bindParam("menu_item", $menu_item, KPDO::TYPE_VARCHAR);
		$stmt_menu->bindParam("parent_id", $parent_id, KPDO::TYPE_INTEGER);
	
		$title_prog = "";
		$prog = "";
		$pattern = "";
		$stmt_prog = $this->db->prepare("INSERT INTO t:krs_progs
			(TITLE, PROG, PUBLISHED, ORDERING, PATTERN)
			VALUES
			(:title_prog, :prog, 1, 1, :pattern)");
		$stmt_prog->bindTable("krs_progs", "krs_progs");
		$stmt_prog->bindParam("title_prog", $title_prog, KPDO::TYPE_VARCHAR);
		$stmt_prog->bindParam("prog", $prog, KPDO::TYPE_VARCHAR);
		$stmt_prog->bindParam("pattern", $pattern, KPDO::TYPE_VARCHAR);
	
		$stmt_prog_menu = $this->db->prepare("INSERT INTO t:krs_progs_menuitems_map
			(PROG_ID, MENUITEMS_ID)
			VALUES
			(:prog_id, :menuitems_id)");
		$stmt_prog_menu->bindTable("krs_progs_menuitems_map", "krs_progs_menuitems_map");
		$stmt_prog_menu->bindParam("prog_id", $prog_id, KPDO::TYPE_INTEGER);
		$stmt_prog_menu->bindParam("menuitems_id", $menuitems_id, KPDO::TYPE_INTEGER);
	
		$stmt_progs_modules = $this->db->prepare("INSERT INTO t:krs_progs_modules_map
			(MODULE_ID, PROG_ID)
			VALUES
			(:module_id, :prog_id)");
		$stmt_progs_modules->bindTable("krs_progs_modules_map", "krs_progs_modules_map");
		$stmt_progs_modules->bindParam("module_id", $this->module_id, KPDO::TYPE_INTEGER);
		$stmt_progs_modules->bindParam("prog_id", $prog_id, KPDO::TYPE_INTEGER);
	
		foreach ($menu_items as $item) {
			$menu_item = $item[0];
			$title_prog = $item[1];
			$prog = $item[2];
			if (isset($item[3])) {
				$parent = $item[3];
			} else {
				$parent = 0;
			}
			if (isset($item[4])) {
				$pattern = $item[4];
			} else {
				$pattern = '';
			}
				
			if (isset($parents[$parent])) {
				$parent_id = $parents[$parent];
			} else {
				$parent_id = 0;
			}
				
			$stmt_menu->execute();
			$menuitems_id = $this->db->lastInsertId();
				
			$parents[$menu_item] = $menuitems_id;
				
			$stmt_prog->execute();
			$prog_id = $this->db->lastInsertId();
				
			//if (count($item)==6) {$level_id=$item[5];} else {$level_id=1;}
				
			$stmt_prog_menu->execute();
			$stmt_progs_modules->execute();
		}
	}
/*FUNZIONE crateMenu DEPRECATA lasciata solo perchè utilizzati in alcuni install di moduli*/
	
	protected function deleteMenu() {
		$stmt = $this->db->prepare("DELETE FROM t:krs_menuitems WHERE ID IN (SELECT MENUITEMS_ID FROM t:krs_progs_menuitems_map as pm, t:krs_progs_modules_map as pmm WHERE pm.PROG_ID = pmm.PROG_ID AND pmm.MODULE_ID = :module )");
		$stmt->bindTable("krs_menuitems", "krs_menuitems");
		$stmt->bindTable("krs_progs_menuitems_map", "krs_progs_menuitems_map");
		$stmt->bindTable("krs_progs_modules_map", "krs_progs_modules_map");
		$stmt->bindParam("module", $this->module_id, KPDO::TYPE_VARCHAR);
		$stmt->execute();
		
		$stmt = $this->db->prepare("DELETE FROM t:krs_progs_menuitems_map WHERE PROG_ID IN (SELECT PROG_ID FROM t:krs_progs_modules_map as pmm WHERE pmm.MODULE_ID = :module )");
		$stmt->bindTable("krs_progs_menuitems_map", "krs_progs_menuitems_map");
		$stmt->bindTable("krs_progs_modules_map", "krs_progs_modules_map");
		$stmt->bindParam("module", $this->module_id, KPDO::TYPE_VARCHAR);
		$stmt->execute();
		
		// permessi dei gruppi sui programmi del modulo: senza questo restavano
		// righe orfane che puntavano a programmi non piu' esistenti
		$stmt = $this->db->prepare("DELETE FROM t:krs_progs_usergroups_map WHERE PROG_ID IN (SELECT PROG_ID FROM t:krs_progs_modules_map as pmm WHERE pmm.MODULE_ID = :module )");
		$stmt->bindTable("krs_progs_usergroups_map", "krs_progs_usergroups_map");
		$stmt->bindTable("krs_progs_modules_map", "krs_progs_modules_map");
		$stmt->bindParam("module", $this->module_id, KPDO::TYPE_VARCHAR);
		$stmt->execute();
		
		$stmt = $this->db->prepare("DELETE FROM t:krs_progs_t WHERE ID IN (SELECT PROG_ID FROM t:krs_progs_modules_map as pmm WHERE pmm.MODULE_ID = :module )");
		$stmt->bindTable("krs_progs_t", "krs_progs");
		$stmt->bindTable("krs_progs_modules_map", "krs_progs_modules_map");
		$stmt->bindParam("module", $this->module_id, KPDO::TYPE_VARCHAR);
		$stmt->execute();
		
		$stmt = $this->db->prepare("DELETE FROM t:krs_progs_modules_map WHERE MODULE_ID = :module");
		$stmt->bindTable("krs_progs_modules_map", "krs_progs_modules_map");
		$stmt->bindParam("module", $this->module_id, KPDO::TYPE_VARCHAR);
		$stmt->execute();
	}
	
	protected function insertGroups($groups) {
		$namegroup='';
		$stmt = $this->db->prepare("INSERT INTO t:krs_usergroups (PARENT_ID, NAME) VALUES (:parent_id, :namegroup)");
		$stmt->bindTable("krs_usergroups", "krs_usergroups");
		$stmt->bindParam('parent_id', $parent_id, KPDO::TYPE_VARCHAR);
		$stmt->bindParam('namegroup', $namegroup, KPDO::TYPE_VARCHAR);
		
		$usergroups_id='';
		$stmt2 = $this->db->prepare("INSERT INTO t:krs_usergroups_modules_map (MODULE_ID, USERGROUP_ID) VALUES (:module_id, :usergroups_id)");
		$stmt2->bindTable("krs_usergroups_modules_map", "krs_usergroups_modules_map");
		$stmt2->bindParam('module_id', $this->module_id, KPDO::TYPE_VARCHAR);
		$stmt2->bindParam('usergroups_id', $usergroups_id, KPDO::TYPE_VARCHAR);
				
		foreach ($groups as $group) {
			$parent_id=$group[0];
			$namegroup=$group[1];
			$stmt->execute();
			$usergroups_id= $this->db->lastInsertId();
			$stmt2->execute();
			
			$this->groups_id[$group[1]] = $usergroups_id;			
		}
		
	}
	
	protected function insertprogsgroups($progsgroups) {
		
		$group_name='';
		$stmt_group = $this->db->prepare("SELECT * FROM t:krs_usergroups WHERE NAME = :krs_name and ID IN (SELECT USERGROUP_ID FROM t:krs_usergroups_modules_map WHERE MODULE_ID = :module )");
		$stmt_group->bindTable("krs_usergroups", "krs_usergroups");
		$stmt_group->bindParam("krs_name", $group_name, KPDO::TYPE_VARCHAR);
		$stmt_group->bindTable("krs_usergroups_modules_map", "krs_usergroups_modules_map");
		$stmt_group->bindParam("module", $this->module_id, KPDO::TYPE_VARCHAR);
		
		$prog_prog='';
		$stmt_prog = $this->db->prepare("SELECT * FROM t:krs_progs WHERE PROG = :krs_prog_prog and ID IN (SELECT PROG_ID FROM t:krs_progs_modules_map WHERE MODULE_ID = :module )");
		$stmt_prog->bindTable("krs_progs", "krs_progs");
		$stmt_prog->bindParam("krs_prog_prog", $prog_prog, KPDO::TYPE_VARCHAR);
		$stmt_prog->bindTable("krs_progs_modules_map", "krs_progs_modules_map");
		$stmt_prog->bindParam("module", $this->module_id, KPDO::TYPE_VARCHAR);
		
		
		$group_id='';
		$prog_id='';
		$read_enabled='';
		$write_enabled='';
		$execution_enabled='';
		$stmt = $this->db->prepare("INSERT INTO t:krs_progs_usergroups_map (PROG_ID, GROUP_ID, READ_ENABLED, WRITE_ENABLED, EXECUTION_ENABLED) VALUES (:prog_id, :group_id, :re, :we, :ee)");
		$stmt->bindTable("krs_progs_usergroups_map", "krs_progs_usergroups_map");
		$stmt->bindParam('prog_id', $prog_id, KPDO::TYPE_VARCHAR);
		$stmt->bindParam('group_id', $group_id, KPDO::TYPE_VARCHAR);
		$stmt->bindParam('re', $read_enabled, KPDO::TYPE_VARCHAR);
		$stmt->bindParam('we', $write_enabled, KPDO::TYPE_VARCHAR);
		$stmt->bindParam('ee', $execution_enabled, KPDO::TYPE_VARCHAR);
		
		foreach ($progsgroups as $pg) {
			// azzerati a ogni giro: un nome di gruppo o di programma
			// sbagliato riusava l'id della riga precedente e dava il permesso
			// al gruppo (o sul programma) sbagliato
			$group_id = null;
			$prog_id = null;

			$group_name=$pg[0];
			$stmt_group->execute();
				
			while ($row = $stmt_group->fetch()) {
				$group_id=$row['ID'];
			}
			
			$prog_prog=$pg[1];
			$stmt_prog->execute();
					
			while ($row = $stmt_prog->fetch()) {
				$prog_id=$row['ID'];
			}

			if ($group_id === null || $prog_id === null) {
				trigger_error("Permesso non assegnato: gruppo '" . $pg[0] . "' o programma '" . $pg[1] . "' inesistente nel modulo " . $this->module, E_USER_WARNING);

				continue;
			}

			$read_enabled=$pg[2];
			$write_enabled=$pg[3];
			$execution_enabled=$pg[4];
			$stmt->execute();
		}
	
	}

	protected function deleteGroups() {
		$stmt = $this->db->prepare("DELETE FROM t:krs_progs_usergroups_map WHERE GROUP_ID IN (SELECT USERGROUP_ID FROM t:krs_usergroups_modules_map WHERE MODULE_ID = :module )");
		$stmt->bindTable("krs_progs_usergroups_map", "krs_progs_usergroups_map");
		$stmt->bindTable("krs_usergroups_modules_map", "krs_usergroups_modules_map");
		$stmt->bindParam("module", $this->module_id, KPDO::TYPE_VARCHAR);
		$stmt->execute();
		
		$stmt = $this->db->prepare("DELETE FROM t:krs_usergroups WHERE ID IN (SELECT USERGROUP_ID FROM t:krs_usergroups_modules_map WHERE MODULE_ID = :module )");
		$stmt->bindTable("krs_usergroups", "krs_usergroups");
		$stmt->bindTable("krs_usergroups_modules_map", "krs_usergroups_modules_map");
		$stmt->bindParam("module", $this->module_id, KPDO::TYPE_VARCHAR);
		$stmt->execute();
			
		$stmt = $this->db->prepare("DELETE FROM t:krs_usergroups_modules_map WHERE MODULE_ID = :module ");
		$stmt->bindTable("krs_usergroups_modules_map", "krs_usergroups_modules_map");
		$stmt->bindParam("module", $this->module_id, KPDO::TYPE_VARCHAR);
		$stmt->execute();
	}
	
	protected function insertUser($users) {
		$fitstname 	= '';
		$lastname 	= '';
		$username 	= '';
		$password 	= '';
		$email 		= '';
		$salt 		= '';
		$user_id 	= '';
		$stmt_user = $this->db->prepare("INSERT INTO t:krs_users (FIRSTNAME, LASTNAME, USERNAME, EMAIL, SALT, PASSWORD, REGISTER_DATA, LAST_VISIT_DATA, ACTIVATION) VALUES (:firstname, :lastname, :username, :email, :salt, :password, '" . date("d-m-Y") . "', '" . date("d-m-Y") . "', 1)");
		$stmt_user->bindTable("krs_users", "krs_users");
		$stmt_user->bindParam('firstname', $firstname, KPDO::TYPE_VARCHAR);
		$stmt_user->bindParam('lastname', $lastname, KPDO::TYPE_VARCHAR);
		$stmt_user->bindParam('username', $username, KPDO::TYPE_VARCHAR);
		$stmt_user->bindParam('email', $email, KPDO::TYPE_VARCHAR);
		$stmt_user->bindParam('salt', $salt, KPDO::TYPE_VARCHAR);
		$stmt_user->bindParam('password', $password, KPDO::TYPE_VARCHAR);
		
		
		$stmt_group = $this->db->prepare("SELECT * FROM t:krs_usergroups WHERE NAME = :krs_name AND ID IN (SELECT USERGROUP_ID FROM t:krs_usergroups_modules_map WHERE MODULE_ID = :module )");
		$stmt_group->bindTable("krs_usergroups", "krs_usergroups");
		$stmt_group->bindTable("krs_usergroups_modules_map", "krs_usergroups_modules_map");
		$stmt_group->bindParam("krs_name", $krs_name, KPDO::TYPE_VARCHAR);
		$stmt_group->bindParam("module", $this->module_id, KPDO::TYPE_INTEGER);
		
		$stmt_user_group = $this->db->prepare("INSERT INTO t:krs_user_usergroup_map (USER_ID, GROUP_ID) VALUES (:user_id, :group_id)");
		$stmt_user_group->bindTable("krs_user_usergroup_map", "krs_user_usergroup_map");
		$stmt_user_group->bindParam("user_id", $user_id, KPDO::TYPE_INTEGER);
		$stmt_user_group->bindParam("group_id", $group_id, KPDO::TYPE_INTEGER);
		
		$stmt_user_module = $this->db->prepare("INSERT INTO t:krs_users_modules_map (MODULE_ID, USER_ID) VALUES (:module_id, :user_id)");
		$stmt_user_module->bindTable("krs_users_modules_map", "krs_users_modules_map");
		$stmt_user_module->bindParam("module_id", $this->module_id, KPDO::TYPE_INTEGER);
		$stmt_user_module->bindParam("user_id", $user_id, KPDO::TYPE_INTEGER);
		
		foreach ($users as $user) {
			$firstname = $user[0];
			$username = $user[1];
			$salt = '';
			$password = Auth::hash_password($user[2]);
			$email = $user[3];
			
			$stmt_user->execute();
			$user_id = $this->db->lastInsertId();
			$this->users_id[$username] = $user_id;
			
			// Il quinto elemento elenca i gruppi dell'utente. Senza, per
			// compatibilita' con i moduli scritti prima, si usa il gruppo
			// "Administrator" DEL MODULO stesso, se l'installer ne crea uno:
			// i gruppi si cercano solo tra quelli di questo modulo, quindi qui
			// non si puo' dare accesso all'admin del framework.
			$explicit = (count($user) == 5);
			$krs_ug_array = $explicit ? $user[4] : array('Administrator');
			
			foreach ($krs_ug_array as $krs_ug_name) {
				$krs_name=$krs_ug_name;
				$stmt_group->execute();
				$group = $stmt_group->fetch();

				// gruppo inesistente: prima si scriveva un'associazione senza
				// gruppo (e un avviso PHP)
				if (!$group) {
					if ($explicit) {
						trigger_error("Utente '" . $username . "': gruppo '" . $krs_ug_name . "' inesistente nel modulo " . $this->module, E_USER_WARNING);
					}

					continue;
				}

				$group_id = $group['ID'];
				$stmt_user_group->execute();
			}
						
			$stmt_user_module->execute();
		}
	}
	
	protected function deletetUser() {
		$stmt_user_group = $this->db->prepare("DELETE FROM t:krs_user_usergroup_map WHERE USER_ID IN (SELECT USER_ID FROM t:krs_users_modules_map WHERE MODULE_ID = :module_id)");
		$stmt_user_group->bindTable("krs_user_usergroup_map", "krs_user_usergroup_map");
		$stmt_user_group->bindTable("krs_users_modules_map", "krs_users_modules_map");
		$stmt_user_group->bindParam('module_id', $this->module_id, KPDO::TYPE_INTEGER);
		$stmt_user_group->execute();
		
		$stmt_user = $this->db->prepare("DELETE FROM t:krs_users_t WHERE ID IN (SELECT USER_ID FROM t:krs_users_modules_map WHERE MODULE_ID = :module_id)");
		$stmt_user->bindTable("krs_users_t", "krs_users");
		$stmt_user->bindTable("krs_users_modules_map", "krs_users_modules_map");
		$stmt_user->bindParam('module_id', $this->module_id, KPDO::TYPE_INTEGER);
		$stmt_user->execute();
		
		$stmt_user_module = $this->db->prepare("DELETE FROM t:krs_users_modules_map WHERE MODULE_ID = :module_id");
		$stmt_user_module->bindTable("krs_users_modules_map", "krs_users_modules_map");
		$stmt_user_module->bindParam("module_id", $this->module_id, KPDO::TYPE_INTEGER);
		$stmt_user_module->execute();
	}
	
	protected function startInstall() {
		
		if (file_exists(DIR_CONFIG . $this->module . '/config.ini')) {
			$this->config->load($this->module . '/config');	
		}
		
		if (isset($this->module) && !$this->createModule($this->module)) {
			return false;
		}

		if (isset($this->databases)) {
			$this->createDB($this->databases);
		}		
		
		if (isset($this->tables)) {
			$this->createTable($this->tables);
		}
		/*------------------------------------------------------*/
		/*Deprecata lasciata solo per retrocompatibilità--------*/
		if (isset($this->menu_items)) {
			$this->createMenu($this->menu_items);
		}
		/*------------------------------------------------------*/
		
		if (isset($this->progs)) {
			$this->createProg($this->progs);
		}
		
		if (isset($this->menuItems)) {
			$this->createMenuItem($this->menuItems);
		}
		
		if (isset($this->groups)) {
			$this->insertGroups($this->groups);
		}

		if (isset($this->progsgroups)) {
			$this->insertprogsgroups($this->progsgroups);
		}
		
		if (isset($this->users)) {
			$this->insertUser($this->users);
		}
	}
	
	protected function startUninstall($setting = array()) {
		if (!isset($setting['delete.module'])) {
			$this->deleteModule();
		}
	
		if (!isset($setting['delete.table'])) {
			$this->deleteTable();
		} else {
			$this->deleteTable($setting['delete.table']);
		}
		
		if (!isset($setting['delete.menu'])) {
			$this->deleteMenu();
		}
		
		if (!isset($setting['delete.user'])) {
			$this->deletetUser();
		}
		if (!isset($setting['delete.groups'])) {
			$this->deleteGroups();
		}
		if (!isset($setting['delete.databases'])) {
			$this->deleteDB();
		}
	}
}
?>