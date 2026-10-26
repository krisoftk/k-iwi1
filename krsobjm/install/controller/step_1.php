<?php
class ControllerInstallStep1 extends Controller {
	private $error = array();
	
	public function index() {
		$this->install();
		
		$this->template = 'install/view/themes/default/step_1.tpl';

		$this->response->setOutput($this->render());
	}

	private function install() {
		$tables = array();
		$tables['krs_databases'] = array(
		array("ID", array(KPDO::TYPE_INTEGER), KPDO::NOT_NULL|KPDO::AUTO_INCREMENT|KPDO::PRIMARY_KEY),
		array("TYPE", array(KPDO::TYPE_VARCHAR, 45), KPDO::NOT_NULL),
		array("NAME", array(KPDO::TYPE_VARCHAR, 45), KPDO::NOT_NULL|KPDO::UNIQUE),
		array("IP", array(KPDO::TYPE_VARCHAR, 45), KPDO::NOT_NULL|KPDO::UNIQUE),
		array("PORT", array(KPDO::TYPE_VARCHAR, 5), KPDO::NOT_NULL|KPDO::UNIQUE),
		array("USERNAME", array(KPDO::TYPE_VARCHAR, 45), KPDO::NOT_NULL),
		array("PASSWORD", array(KPDO::TYPE_VARCHAR, 45), KPDO::NOT_NULL),
		array("MODULE_ID", array(KPDO::TYPE_INTEGER), KPDO::NOT_NULL|KPDO::UNIQUE)
		);
		
		$tables['krs_tables'] = array(
		array("ID", array(KPDO::TYPE_INTEGER), KPDO::NOT_NULL|KPDO::AUTO_INCREMENT|KPDO::PRIMARY_KEY),
		array("NAME", array(KPDO::TYPE_VARCHAR, 45), KPDO::NOT_NULL|KPDO::UNIQUE),
		array("DATABASE_ID", array(KPDO::TYPE_INTEGER), KPDO::NOT_NULL),
		array("MODULE_ID", array(KPDO::TYPE_INTEGER), KPDO::NOT_NULL|KPDO::UNIQUE),
		);
		
		$tables['krs_types'] = array(
		array("ID", array(KPDO::TYPE_INTEGER), KPDO::NOT_NULL|KPDO::AUTO_INCREMENT|KPDO::PRIMARY_KEY),
		array("NAME", array(KPDO::TYPE_VARCHAR, 45), KPDO::NOT_NULL),
		array("PARAM", array(KPDO::TYPE_VARCHAR, 255))
		);
		
		$tables['krs_fields'] = array(
		array("ID", array(KPDO::TYPE_INTEGER), KPDO::NOT_NULL|KPDO::AUTO_INCREMENT|KPDO::PRIMARY_KEY),
		array("NAME", array(KPDO::TYPE_VARCHAR, 45), KPDO::NOT_NULL|KPDO::UNIQUE),
		array("ATTRIBUTE", array(KPDO::TYPE_VARCHAR, 255)),
		array("TYPE_ID", array(KPDO::TYPE_INTEGER), KPDO::NOT_NULL),
		array("TABLE_ID", array(KPDO::TYPE_INTEGER), KPDO::NOT_NULL|KPDO::UNIQUE)
		);
		
		$tables['krs_modules'] = array(
		array("ID", array(KPDO::TYPE_INTEGER), KPDO::NOT_NULL|KPDO::AUTO_INCREMENT|KPDO::PRIMARY_KEY),
		array("PARENT_ID", array(KPDO::TYPE_INTEGER), KPDO::NOT_NULL),
		array("NAME", array(KPDO::TYPE_VARCHAR, 100), KPDO::NOT_NULL|KPDO::UNIQUE)
		);
		
		foreach ($tables as $table_name => $instructions) {
			if (isset($instructions)) {
				$this->db->createTable($table_name,  $instructions);
			}
		}
		
		//echo "Start of krs_databases table data<br>";
		$driver = $this->config->get('database.driver');
		$host = $this->config->get('database.host');
		$port = $this->config->get('database.port');
		$username = $this->config->get('database.user');
		$password = $this->config->get('database.password');
        $modulo_id = 1;
		
		$database = $this->config->get('database.name');
		if ($database == '') {
			$database = "krs_framework";
		}
		
		$stmt = $this->db->prepare("INSERT INTO t:krs_databases (TYPE, NAME, IP, PORT, USERNAME, PASSWORD, MODULE_ID) VALUES ('" . $driver . "', '" . $database . "', '" . $host . "', '" . $port . "', '" . $username . "', '" . $password . "', '" . $modulo_id . "')");
		$stmt->bindTable("krs_databases", "krs_databases");
		$stmt->execute();
		//echo "End of krs_databases table data<br>";
		
		//echo "Start of krs_tables table data<br>";
		$stmt = $this->db->prepare("INSERT INTO t:krs_tables (NAME, DATABASE_ID, MODULE_ID) VALUES
			('krs_users_modules_map', 1, 1),
			('krs_usergroups_modules_map', 1, 1),
			('krs_progs_modules_map', 1, 1),
			('krs_menutype_modules_map', 1, 1),
			('krs_progs_usergroups_map', 1, 1),
			('krs_users', 1, 1),
			('krs_session', 1, 1),
			('krs_user_usergroup_map', 1, 1),
			('krs_usergroups', 1, 1),
			('krs_menu_type', 1, 1),
			('krs_menuitems', 1, 1),
			('krs_progs_menuitems_map', 1, 1),
			('krs_progs', 1, 1),
			('krs_languages', 1, 1),
			('krs_scheduler', 1, 1),
			('krs_users_auth', 1, 1),
			('krs_rpt_report', 1, 1),
			('krs_rpt_token', 1, 1),
			('krs_api_keys', 1, 1),
			('krs_login_attempts', 1, 1)
		");
		$stmt->bindTable("krs_tables", "krs_tables");
		$stmt->execute();
		//echo "End of krs_tables table data<br>";
		
		//echo "Start of krs_types table data<br>";
		$stmt = $this->db->prepare("INSERT INTO t:krs_types (NAME, PARAM) VALUES
			('" . KPDO::TYPE_INTEGER . "', null),
			('" . KPDO::TYPE_VARCHAR . "', '255'),
			('" . KPDO::TYPE_SMALLINT . "', null),
			('" . KPDO::TYPE_VARCHAR . "', '2048'),
			('" . KPDO::TYPE_CLOB . "', 'null'),
			('" . KPDO::TYPE_TIMESTAMP . "', null)
		");
		$stmt->bindTable("krs_types", "krs_types");
		$stmt->execute();
		//echo "End of krs_types table data<br>";
		
		//echo "Start of krs_fields table data<br>";
		$stmt = $this->db->prepare("INSERT INTO t:krs_fields (NAME, ATTRIBUTE, TYPE_ID, TABLE_ID) VALUES
			('MODULE_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 1),
			('USER_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 1),
			('MODULE_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 2),
			('USERGROUP_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 2),
			('MODULE_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 3),
			('PROG_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 3),
			('MODULE_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 4),
			('MENUTYPE_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 4),
			('PROG_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 5),
			('GROUP_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 5),
			('READ_ENABLED', '" . (KPDO::NOT_NULL) . "', 2, 5),	
			('WRITE_ENABLED', '" . (KPDO::NOT_NULL) . "', 2, 5),
			('EXECUTION_ENABLED', '" . (KPDO::NOT_NULL) . "', 2, 5),
			('ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL|KPDO::AUTO_INCREMENT) . "', 1, 6),
			('FIRSTNAME', '" . (KPDO::NOT_NULL) . "', 2, 6),
			('LASTNAME', '" . (KPDO::NOT_NULL) . "', 2, 6),
			('USERNAME', '" . (KPDO::NOT_NULL) . "', 2, 6),
			('EMAIL', '" . (KPDO::NOT_NULL) . "', 2, 6),
			('SALT', '" . (KPDO::NOT_NULL) . "', 2, 6),
			('PASSWORD', '" . (KPDO::NOT_NULL) . "', 2, 6),
			('REGISTER_DATA', '" . (KPDO::NOT_NULL) . "', 2, 6),
			('LAST_VISIT_DATA', '" . (KPDO::NOT_NULL) . "', 2, 6),
			('ACTIVATION', '" . (KPDO::NOT_NULL) . "', 2, 6),
			('LANGUAGE', null, 2, 6),
			('SESSION_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 2, 7),
			('USER_ID', null, 1,7),
			('CLIENT_ID', null, 3, 7),
			('GUEST', null, 3,7),
			('TIME', null, 2,7),
			('DATA', null, 5,7),
			('USERNAME', null, 2,7),
			('USERTYPE', null, 2,7),
			('USER_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 8),
			('GROUP_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 8),
			('ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL|KPDO::AUTO_INCREMENT) . "', 1, 9),
			('PARENT_ID', '" . (KPDO::NOT_NULL) . "', 1, 9),
			('NAME', '" . (KPDO::NOT_NULL) . "', 2, 9),
			('ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL|KPDO::AUTO_INCREMENT) . "', 1, 10),
			('MENUTYPE', '" . (KPDO::NOT_NULL) . "', 2, 10),
			('TITLE', '" . (KPDO::NOT_NULL) . "', 2, 10), 
			('DESCRIPTION', '" . (KPDO::NOT_NULL) . "', 2, 10),
			('ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL|KPDO::AUTO_INCREMENT) . "', 1, 11),
			('MENUTYPE_ID', '" . (KPDO::NOT_NULL) . "', 1, 11),
			('PARENT_ID', '" . (KPDO::NOT_NULL) . "', 1, 11),
			('TITLE', '" . (KPDO::NOT_NULL) . "', 2, 11),
			('NOTE', null, 2, 11),
			('PUBLISHED', '" . (KPDO::NOT_NULL) . "', 3, 11),
			('ORDERING', '" . (KPDO::NOT_NULL) . "', 1, 11),
			('PROG_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 12),
			('MENUITEMS_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 12),
			('ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL|KPDO::AUTO_INCREMENT) . "', 1, 13), 
			('TITLE', '" . (KPDO::NOT_NULL) . "', 2, 13),
			('PROG', '" . (KPDO::NOT_NULL) . "', 2, 13),
			('NOTE', null, 2, 13), 
			('ORDERING', '" . (KPDO::NOT_NULL) . "', 1, 13),
			('PUBLISHED', '" . (KPDO::NOT_NULL) . "', 3, 13),
			('PATTERN', '" . (KPDO::NOT_NULL) . "', 2, 13),
			('ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL|KPDO::AUTO_INCREMENT) . "', 1, 14), 
			('NAME', '" . (KPDO::NOT_NULL) . "', 2, 14),
			('CODE', '" . (KPDO::NOT_NULL) . "', 2, 14),
			('LOCALE', '" . (KPDO::NOT_NULL) . "', 2, 14),
			('IMAGE', null, 2, 14),	
			('DIRECTORY', '" . (KPDO::NOT_NULL) . "', 2, 14),
			('FILENAME', '" . (KPDO::NOT_NULL) . "', 2, 14),
			('SORT_ORDER', null, 1, 14),
			('STATUS', '" . (KPDO::NOT_NULL) . "', 2, 14),
			('ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL|KPDO::AUTO_INCREMENT) . "', 1, 15),
			('CONTROLLER', '" . (KPDO::NOT_NULL) . "', 2, 15),
			('DATE_RUN', '" . (KPDO::NOT_NULL) . "', 1, 15),
			('DAY_OF_WEEK_RUN', '" . (KPDO::NOT_NULL) . "', 2, 15),
			('TIME_RUN', '" . (KPDO::NOT_NULL) . "', 2, 15),
			('LAST_RUN', '" . (KPDO::NOT_NULL) . "', 1, 15),
			('ATTIVO', '" . (KPDO::NOT_NULL) . "', 3, 15),
			('USER_ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 1, 16),
			('PIN', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 2, 16),
			('TAG', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 2, 16),
			('ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL|KPDO::AUTO_INCREMENT) . "', 1, 17),
			('PROG_ID', '" . (KPDO::NOT_NULL) . "', 1, 17),
			('NAME', '" . (KPDO::NOT_NULL) . "', 2, 17),
			('DESCRIPTION', null, 2, 17),
			('URL', '" . (KPDO::NOT_NULL) . "', 2, 17),
			('TOKEN', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL) . "', 2, 18),
			('REPORT_ID', '" . (KPDO::NOT_NULL) . "', 1, 18),
			('IP', '" . (KPDO::NOT_NULL) . "', 2, 18),
			('EXPIRE', '" . (KPDO::NOT_NULL) . "', 6, 18),
			('ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL|KPDO::AUTO_INCREMENT) . "', 1, 19),
			('USER_ID', '" . (KPDO::NOT_NULL) . "', 1, 19),
			('KEY_HASH', '" . (KPDO::NOT_NULL|KPDO::UNIQUE) . "', 2, 19),
			('KEY_PREFIX', '" . (KPDO::NOT_NULL) . "', 2, 19),
			('DESCRIPTION', null, 2, 19),
			('ACTIVE', '" . (KPDO::NOT_NULL) . "', 3, 19),
			('CREATED', '" . (KPDO::NOT_NULL) . "', 2, 19),
			('LAST_USED', null, 2, 19),
			('ID', '" . (KPDO::PRIMARY_KEY|KPDO::NOT_NULL|KPDO::AUTO_INCREMENT) . "', 1, 20),
			('USERNAME', '" . (KPDO::NOT_NULL) . "', 2, 20),
			('IP', '" . (KPDO::NOT_NULL) . "', 2, 20),
			('TIME', '" . (KPDO::NOT_NULL) . "', 1, 20)
		");
		$stmt->bindTable("krs_fields", "krs_fields");
		$stmt->execute();
		//echo "End of krs_fields table data<br>";
		
		$stmt_tables = $this->db->prepare('SELECT t.ID AS "TABLE_ID", t.NAME AS "TABLE", db.TYPE AS "DRIVER", db.NAME AS "DB", db.IP, db.PORT, db.USERNAME, db.PASSWORD FROM t:krs_tables as t, t:krs_databases as db WHERE t.DATABASE_ID = db.id');
		$stmt_tables->bindTable("krs_tables", "krs_tables");
		$stmt_tables->bindTable("krs_databases", "krs_databases");
		$stmt_tables->execute();
		
		$stmt_fields = $this->db->prepare('SELECT f.NAME AS "FIELD", f.ATTRIBUTE, t.NAME AS "TYPE", t.PARAM  AS "PARAM" FROM t:krs_fields as f, t:krs_types as t WHERE f.type_id = t.id AND f.TABLE_ID = :table');
		$stmt_fields->bindTable("krs_fields", "krs_fields");
		$stmt_fields->bindTable("krs_types", "krs_types");
		while(($table = $stmt_tables->fetch(KPDO::FETCH_ASSOC))) {
			$stmt_fields->bindParam(":table", $table['TABLE_ID'], KPDO::TYPE_INTEGER);
		
			$stmt_fields->execute();
			$fields_of_table = array();
			while ($field = $stmt_fields->fetch(KPDO::FETCH_ASSOC)) {
				$fields_of_table[] = array($field['FIELD'], array($field['TYPE'], $field['PARAM']), $field['ATTRIBUTE']);
			}
			$this->db->createTable($table['TABLE'], $fields_of_table);
			//echo "Table " . $table['TABLE'] . " created<br>";
		}
		
		// start create user admin
		$password = Auth::hash_password('admin');
		$stmt = $this->db->prepare("INSERT INTO t:krs_users (FIRSTNAME, LASTNAME, USERNAME, EMAIL, SALT, PASSWORD, REGISTER_DATA, LAST_VISIT_DATA, ACTIVATION) VALUES ('Administrator', 'Krisoft', 'admin', 'admin@admin.tlc', '', :password, '" . date("d-m-Y") . "', '" . date("d-m-Y") . "', 1)");
		$stmt->bindTable("krs_users", "krs_users");
		$stmt->bindParam("password", $password, KPDO::TYPE_VARCHAR);
		$stmt->execute();
		
		$user_id = $this->db->lastInsertId();
		// end create user admin
		
		// start create group administrator
		$stmt = $this->db->prepare("INSERT INTO t:krs_usergroups (PARENT_ID, NAME) VALUES (0, 'Administrator')");
		$stmt->bindTable("krs_usergroups", "krs_usergroups");
		$stmt->execute();
		
		$group_id = $this->db->lastInsertId();
		// end create group administrator
		
		// start relationship user -> usergroup
		$stmt = $this->db->prepare("INSERT INTO t:krs_user_usergroup_map (USER_ID, GROUP_ID) VALUES (:user_id, :group_id)");
		$stmt->bindTable("krs_user_usergroup_map", "krs_user_usergroup_map");
		$stmt->bindParam("user_id", $user_id, KPDO::TYPE_INTEGER);
		$stmt->bindParam("group_id", $group_id, KPDO::TYPE_INTEGER);
		$stmt->execute();
		// end relationship user -> usergroup
		
		// start create module admin
		$stmt = $this->db->prepare("INSERT INTO t:krs_modules (PARENT_ID, NAME) VALUES (0, 'admin')");
		$stmt->bindTable("krs_modules", "krs_modules");
		$stmt->execute();
		
		$module_id = $this->db->lastInsertId();
		// end create module admin
		
		// start relationship user -> module
		$stmt = $this->db->prepare("INSERT INTO t:krs_users_modules_map (MODULE_ID, USER_ID) VALUES (:module_id, :user_id)");
		$stmt->bindTable("krs_users_modules_map", "krs_users_modules_map");
		$stmt->bindParam("module_id", $module_id, KPDO::TYPE_INTEGER);
		$stmt->bindParam("user_id", $user_id, KPDO::TYPE_INTEGER);
		$stmt->execute();
		// end relationship user -> module
		
		// start relationship group -> module
		$stmt = $this->db->prepare("INSERT INTO t:krs_usergroups_modules_map (MODULE_ID, USERGROUP_ID) VALUES (:module_id, :group_id)");
		$stmt->bindTable("krs_usergroups_modules_map", "krs_usergroups_modules_map");
		$stmt->bindParam("module_id", $module_id, KPDO::TYPE_INTEGER);
		$stmt->bindParam("group_id", $group_id, KPDO::TYPE_INTEGER);
		$stmt->execute();
		// end relationship group -> module
		
		// start create menu standard
		$stmt = $this->db->prepare("INSERT INTO t:krs_menu_type (MENUTYPE, TITLE, DESCRIPTION) VALUES ('standard', 'Principale', 'Menu standard')");
		$stmt->bindTable("krs_menu_type", "krs_menu_type");
		$stmt->execute();
		// end create menu standard
		
		// start insert language default
		$stmt = $this->db->prepare("INSERT INTO t:krs_languages (NAME, CODE, LOCALE, IMAGE, DIRECTORY, FILENAME, SORT_ORDER, STATUS) VALUES ('Italiano', 'it_IT', 'it_IT.UTF-8,it_IT,italian', 'ita.png', 'italian', 'italian', '0', '1')");
		$stmt->bindTable("krs_languages", "krs_languages");
		$stmt->execute();
		// end insert language default
		
		$stmt = $this->db->prepare("INSERT INTO t:krs_tables (NAME, DATABASE_ID, MODULE_ID) VALUES
			('krs_databases', 1, 0),
			('krs_tables', 1, 0),
			('krs_types', 1, 0),
			('krs_fields', 1, 0),
			('krs_modules', 1, 0)
		");
		$stmt->bindTable("krs_tables", "krs_tables");
		$stmt->execute();
		
		/**** WRITING OF THE CONFIGURATION FILES ****/
		/* ADMIN */
		$output  = '<?php' . "\n";
		$output .= '// HTTP' . "\n";
		$output .= 'define(\'HTTP_SERVER\', \'' . HTTP_SERVER . '\');' . "\n";
		$output .= 'define(\'HTTP_PUBLIC\', \'' . HTTP_SERVER . '\');' . "\n";
		$output .= 'define(\'HTTP_IMAGE\', \'' . HTTP_SERVER . 'image/\');' . "\n";
		$output .= 'define(\'HTTP_THEMES\', \'' . HTTP_SERVER . 'themes/admin/\');' . "\n\n";

		$output .= '// HTTPS' . "\n";
		$output .= 'define(\'HTTPS_SERVER\', \'' . HTTPS_SERVER . '\');' . "\n";
		$output .= 'define(\'HTTPS_PUBLIC\', \'' . HTTPS_SERVER . '\');' . "\n";
		$output .= 'define(\'HTTPS_IMAGE\', \'' . HTTPS_SERVER . 'image/\');' . "\n";
		$output .= 'define(\'HTTPS_THEMES\', \'' . HTTPS_SERVER . 'themes/admin/\');' . "\n\n";
		
		$output .= '//INDEX' . "\n";
		$output .= 'define(\'INDEX\', \'admin/index.php\');' . "\n\n";
		
		$output .= '// DIR' . "\n";
		$output .= 'define(\'DIR_LIBRARY\', \'' . DIR_BASE . 'krsobjl/\');' . "\n";
		$output .= 'define(\'DIR_MODULE\', \'' . DIR_BASE . 'krsobjm/\');' . "\n";
		$output .= 'define(\'DIR_TEMPLATE\', \'' . DIR_BASE . 'krsobjm/admin/view/themes/\');' . "\n";
		$output .= 'define(\'DIR_PUBLIC\', \'' . DIR_BASE . 'krsobjp/\');' . "\n";
		$output .= 'define(\'DIR_JAVASCRIPT\', \'' . DIR_BASE . 'krsobjp/js/\');' . "\n";
		$output .= 'define(\'DIR_CSS\', \'' . DIR_BASE . 'krsobjp/css/\');' . "\n";
		$output .= 'define(\'DIR_IMAGE\', \'' . DIR_BASE . 'krsobjp/image/\');' . "\n";
		$output .= 'define(\'DIR_THEMES\', \'' . DIR_BASE . 'krsobjp/themes/admin/\');' . "\n";
		$output .= 'define(\'DIR_LOGS\', \'' . DIR_BASE . 'logs/\');' . "\n";
		$output .= 'define(\'DIR_CONFIG\', \'' . DIR_BASE . 'config/\');' . "\n";
		$output .= 'define(\'DIR_SESSIONS\', \'' . DIR_BASE . 'sessions/\');' . "\n";
		$output .= '?>';
		
		$file = fopen(DIR_BASE . 'krsobjp/admin/globals.php', 'w');
		
		fwrite($file, $output);
		
		fclose($file);
		
		/* PUBLIC */
		$output  = '<?php' . "\n";
		$output .= '// HTTP' . "\n";
		$output .= 'define(\'HTTP_SERVER\', \'' . HTTP_SERVER . '\');' . "\n";
		$output .= 'define(\'HTTP_PUBLIC\', \'' . HTTP_SERVER . '\');' . "\n";
		$output .= 'define(\'HTTP_IMAGE\', \'' . HTTP_SERVER . 'image/\');' . "\n";
		$output .= 'define(\'HTTP_THEMES\', \'' . HTTP_SERVER . 'themes/\');' . "\n\n";
		
		$output .= '// HTTPS' . "\n";
		$output .= 'define(\'HTTPS_SERVER\', \'' . HTTPS_SERVER . '\');' . "\n";
		$output .= 'define(\'HTTPS_PUBLIC\', \'' . HTTPS_SERVER . '\');' . "\n";
		$output .= 'define(\'HTTPS_IMAGE\', \'' . HTTPS_SERVER . 'image/\');' . "\n";
		$output .= 'define(\'HTTPS_THEMES\', \'' . HTTPS_SERVER . 'themes/\');' . "\n\n";
		
		$output .= '//INDEX' . "\n";
		$output .= 'define(\'INDEX\', \'index.php\');' . "\n\n";
		
		$output .= '// DIR' . "\n";
		$output .= 'define(\'DIR_LIBRARY\', \'' . DIR_BASE . 'krsobjl/\');' . "\n";
		$output .= 'define(\'DIR_MODULE\', \'' . DIR_BASE . 'krsobjm/\');' . "\n";
		$output .= 'define(\'DIR_TEMPLATE\', \'' . DIR_BASE . 'krsobjm/standard/view/themes/\');' . "\n";
		$output .= 'define(\'DIR_PUBLIC\', \'' . DIR_BASE . 'krsobjp/\');' . "\n";
		$output .= 'define(\'DIR_JAVASCRIPT\', \'' . DIR_BASE . 'krsobjp/js/\');' . "\n";
		$output .= 'define(\'DIR_CSS\', \'' . DIR_BASE . 'krsobjp/css/\');' . "\n";
		$output .= 'define(\'DIR_IMAGE\', \'' . DIR_BASE . 'krsobjp/image/\');' . "\n";
		$output .= 'define(\'DIR_THEMES\', \'' . DIR_BASE . 'krsobjp/themes/\');' . "\n";
		$output .= 'define(\'DIR_LOGS\', \'' . DIR_BASE . 'logs/\');' . "\n";
		$output .= 'define(\'DIR_CONFIG\', \'' . DIR_BASE . 'config/\');' . "\n";
		$output .= 'define(\'DIR_SESSIONS\', \'' . DIR_BASE . 'sessions/\');' . "\n";
		$output .= '?>';
		
		$file = fopen(DIR_BASE . 'krsobjp/globals.php', 'w');
		
		fwrite($file, $output);
		
		fclose($file);
	}
}
?>