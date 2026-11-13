<?php
/**
 * Upgrades the database of a K-iwi installation made before 1.0 to the 1.0
 * schema, in place.
 *
 *   php tools/upgrade.php --database=<name>            report only
 *   php tools/upgrade.php --database=<name> --apply    report and upgrade
 *
 * Options:
 *   --repoint=<name>  the krs_databases rows naming database <name> are made
 *                     to name --database. A copy of a database keeps the
 *                     name of the original there, so the framework running
 *                     on the copy would read and write the original.
 *   --all-tables      converts to utf8mb4 the tables of the modules too, not
 *                     only the krs_ ones.
 *
 * The IDs of users, groups, programs and menus stay the same, so the tables
 * of the modules that refer to them keep working, and so do the passwords:
 * the 1.0 login recognises the old salted sha1 hashes and turns them into
 * bcrypt. The tables of the modules are not touched, except for the charset
 * with --all-tables.
 *
 * Host, user and password come from config/config.ini; database.name is not
 * used, the database is always the one given. Run it on a copy first, and
 * take a backup before running it on the real one. Every step checks before
 * acting, so it can be run again.
 *
 * The report never shows what the tables hold: only counts and the names of
 * tables, columns, modules and programs.
 */

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

require_once dirname(__DIR__) . '/krsobjp/globals.php';
require_once DIR_LIBRARY . 'common/application_management.php';
require_once DIR_LIBRARY . 'common/bootstrap.php';

$options = getopt('', array('database:', 'apply', 'repoint:', 'all-tables', 'help'));

if (isset($options['help']) || empty($options['database'])) {
	fwrite(STDERR, "Usage:\n  php tools/upgrade.php --database=<name> [--apply] [--repoint=<name>] [--all-tables]\n");
	exit(1);
}

$schema = (string) $options['database'];
$apply = isset($options['apply']);
$repoint = isset($options['repoint']) ? (string) $options['repoint'] : null;
$allTables = isset($options['all-tables']);

if (!preg_match('/^[A-Za-z0-9_$]+$/', $schema) || ($repoint !== null && !preg_match('/^[A-Za-z0-9_$]+$/', $repoint))) {
	fwrite(STDERR, "Database names may contain only letters, digits, _ and \$.\n");
	exit(1);
}

$config = new Config();
$config->load('config');

KPDO::setSqlMode($config->get('database.sql_mode'));

$db = new KPDO($config->get('database.driver'), $config->get('database.host'), $config->get('database.port'), $config->get('database.user'), $config->get('database.password'), $schema);

$warnings = 0;
$errors = 0;

function section($title) {
	echo "\n== $title\n";
}

function ok($message) {
	echo "  ok    $message\n";
}

function warn($message) {
	global $warnings;

	$warnings++;
	echo "  WARN  $message\n";
}

function fail($message) {
	global $errors;

	$errors++;
	echo "  ERROR $message\n";
}

// what --apply does, or has just done
function change($message) {
	global $apply;

	echo ($apply ? "  done  " : "  todo  ") . $message . "\n";
}

// a literal for the statements that can't take parameters (ALTER TABLE);
// the driver never runs with NO_BACKSLASH_ESCAPES
function sql_string($value) {
	return "'" . str_replace(array('\\', "'"), array('\\\\', "''"), $value) . "'";
}

function q($name) {
	return '`' . str_replace('`', '``', $name) . '`';
}

function rows($db, $sql, $params = array()) {
	$stmt = $db->prepare($sql);

	foreach ($params as $name => $value) {
		$params[$name] = $value;
		$stmt->bindParam($name, $params[$name], is_int($value) ? KPDO::TYPE_INTEGER : KPDO::TYPE_VARCHAR);
	}

	$stmt->execute();

	return $stmt->fetchAll(KPDO::FETCH_ASSOC) ?: array();
}

function value($db, $sql, $params = array()) {
	$rows = rows($db, $sql, $params);

	return $rows ? reset($rows[0]) : null;
}

function table_exists($db, $table) {
	global $schema;

	return (bool) value($db, "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table", array(':schema' => $schema, ':table' => $table));
}

function column_exists($db, $table, $column) {
	global $schema;

	return (bool) value($db, "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND COLUMN_NAME = :column", array(':schema' => $schema, ':table' => $table, ':column' => $column));
}

function count_rows($db, $table, $where = '1') {
	return (int) value($db, "SELECT COUNT(*) FROM " . q($table) . " WHERE $where");
}

echo "K-iwi upgrade to 1.0 - database $schema - " . ($apply ? "APPLY" : "report only, nothing is written") . "\n";

foreach (array('krs_users', 'krs_usergroups', 'krs_modules', 'krs_progs', 'krs_tables', 'krs_databases', 'krs_fields', 'krs_types') as $table) {
	if (!table_exists($db, $table)) {
		fwrite(STDERR, "Table $table not found: $schema is not a K-iwi database.\n");
		exit(1);
	}
}

// ---------------------------------------------------------------------------
section('Connections (krs_databases)');

// Every table is opened through the connection krs_tables points to: rows
// naming another K-iwi database (a copy keeps the original's name) would
// make the upgraded framework work on that one.
if ($repoint !== null) {
	$n = (int) value($db, "SELECT COUNT(*) FROM krs_databases WHERE NAME = :name", array(':name' => $repoint));

	if ($n) {
		if ($apply) {
			$stmt = $db->prepare("UPDATE krs_databases SET NAME = :schema WHERE NAME = :old");
			$stmt->bindParam(':schema', $schema, KPDO::TYPE_VARCHAR);
			$stmt->bindParam(':old', $repoint, KPDO::TYPE_VARCHAR);
			$stmt->execute();
		}

		change("$n connection(s) from $repoint to $schema");
	} else {
		ok("no connection names $repoint");
	}
}

$blocking = false;

foreach (rows($db, "SELECT d.ID, d.TYPE, d.NAME, d.IP, d.PORT, d.USERNAME, d.PASSWORD, COUNT(t.ID) AS TABLES FROM krs_databases d LEFT JOIN krs_tables t ON t.DATABASE_ID = d.ID GROUP BY d.ID, d.TYPE, d.NAME, d.IP, d.PORT, d.USERNAME, d.PASSWORD ORDER BY d.ID") as $conn) {
	$name = $conn['NAME'];

	// in report mode the rows --repoint is going to change count as done
	if ($name === $schema || ($repoint !== null && $name === $repoint && !$apply)) {
		ok("connection {$conn['ID']} ({$conn['TYPE']}): this database, {$conn['TABLES']} table(s)");

		// the framework opens the tables with the credentials of the row,
		// not with those of config.ini (they are never printed)
		// the driver's warning would print the user name
		set_error_handler(function () {
			return true;
		});

		try {
			new KPDO($conn['TYPE'], $conn['IP'], $conn['PORT'], $conn['USERNAME'], $conn['PASSWORD'], $schema);
			ok("connection {$conn['ID']}: its credentials open $schema");
		} catch (Throwable $e) {
			warn("connection {$conn['ID']}: its credentials (user and password in krs_databases) can't open $schema on {$conn['IP']}: give that user access, or change the row");
		}

		restore_error_handler();
	} elseif ($conn['TYPE'] === 'mysql' && preg_match('/^[A-Za-z0-9_$]+$/', $name) && value($db, "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :name AND TABLE_NAME = 'krs_users'", array(':name' => $name))) {
		fail("connection {$conn['ID']} names $name, another K-iwi database ({$conn['TABLES']} table(s)): if $schema is a copy of it, run with --repoint=$name");
		$blocking = true;
	} else {
		warn("connection {$conn['ID']} ({$conn['TYPE']}) names $name: its {$conn['TABLES']} table(s) are not upgraded by this run");
	}
}

if ($blocking && $apply) {
	echo "\nNothing was changed: fix the connections first.\n";
	exit(1);
}

// ---------------------------------------------------------------------------
section('Framework tables');

if (!column_exists($db, 'krs_users', 'LANGUAGE')) {
	if ($apply) {
		$db->exec("ALTER TABLE krs_users ADD COLUMN LANGUAGE varchar(255) DEFAULT NULL");
	}

	change("krs_users: new column LANGUAGE (the language the user chose)");
} else {
	ok("krs_users.LANGUAGE");
}

if (table_exists($db, 'krs_users_auth')) {
	$key = value($db, "SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = 'krs_users_auth' AND INDEX_NAME = 'PRIMARY'", array(':schema' => $schema));

	if ($key !== 'USER_ID,PIN,TAG') {
		if ($apply) {
			$db->exec("ALTER TABLE krs_users_auth " . ($key ? "DROP PRIMARY KEY, " : "") . "ADD PRIMARY KEY (USER_ID, PIN, TAG)");
		}

		change("krs_users_auth: primary key (" . ($key ?: 'none') . ") -> (USER_ID, PIN, TAG)");
	} else {
		ok("krs_users_auth primary key");
	}
}

if (!value($db, "SELECT COUNT(*) FROM krs_types WHERE NAME = :name", array(':name' => KPDO::TYPE_TIMESTAMP))) {
	if ($apply) {
		$type = KPDO::TYPE_TIMESTAMP;
		$stmt = $db->prepare("INSERT INTO krs_types (NAME, PARAM) VALUES (:name, NULL)");
		$stmt->bindParam(':name', $type, KPDO::TYPE_VARCHAR);
		$stmt->execute();
	}

	change("krs_types: type " . KPDO::TYPE_TIMESTAMP);
} else {
	ok("krs_types " . KPDO::TYPE_TIMESTAMP);
}

// krs_fields describes the krs_ tables (the installer creates them from it)
$usersTable = value($db, "SELECT ID FROM krs_tables WHERE NAME = 'krs_users'");

if ($usersTable && !value($db, "SELECT COUNT(*) FROM krs_fields WHERE TABLE_ID = :id AND NAME = 'LANGUAGE'", array(':id' => (int) $usersTable))) {
	if ($apply) {
		$typeId = (int) value($db, "SELECT ID FROM krs_types WHERE NAME = :name AND PARAM = '255'", array(':name' => KPDO::TYPE_VARCHAR));
		$stmt = $db->prepare("INSERT INTO krs_fields (NAME, ATTRIBUTE, TYPE_ID, TABLE_ID) VALUES ('LANGUAGE', NULL, :type_id, :table_id)");
		$stmt->bindParam(':type_id', $typeId, KPDO::TYPE_INTEGER);
		$usersTable = (int) $usersTable;
		$stmt->bindParam(':table_id', $usersTable, KPDO::TYPE_INTEGER);
		$stmt->execute();
	}

	change("krs_fields: LANGUAGE of krs_users");
}

// same definitions as tools/api_key.php and Auth::login_attempts_db()
$newTables = array(
	'krs_api_keys' => array(
		array('ID',          KPDO::TYPE_INTEGER,  null,  KPDO::PRIMARY_KEY|KPDO::NOT_NULL|KPDO::AUTO_INCREMENT),
		array('USER_ID',     KPDO::TYPE_INTEGER,  null,  KPDO::NOT_NULL),
		array('KEY_HASH',    KPDO::TYPE_VARCHAR,  '255', KPDO::NOT_NULL|KPDO::UNIQUE),
		array('KEY_PREFIX',  KPDO::TYPE_VARCHAR,  '255', KPDO::NOT_NULL),
		array('DESCRIPTION', KPDO::TYPE_VARCHAR,  '255', null),
		array('ACTIVE',      KPDO::TYPE_SMALLINT, null,  KPDO::NOT_NULL),
		array('CREATED',     KPDO::TYPE_VARCHAR,  '255', KPDO::NOT_NULL),
		array('LAST_USED',   KPDO::TYPE_VARCHAR,  '255', null),
	),
	'krs_login_attempts' => array(
		array('ID',       KPDO::TYPE_INTEGER, null,  KPDO::PRIMARY_KEY|KPDO::NOT_NULL|KPDO::AUTO_INCREMENT),
		array('USERNAME', KPDO::TYPE_VARCHAR, '255', KPDO::NOT_NULL),
		array('IP',       KPDO::TYPE_VARCHAR, '255', KPDO::NOT_NULL),
		array('TIME',     KPDO::TYPE_INTEGER, null,  KPDO::NOT_NULL),
	),
);

foreach ($newTables as $table => $fields) {
	$registered = (bool) value($db, "SELECT COUNT(*) FROM krs_tables WHERE NAME = :name", array(':name' => $table));

	if (!$registered || !table_exists($db, $table)) {
		if ($apply) {
			if ($registered) {
				// registered but missing: ensureTable would skip it
				$stmt = $db->prepare("DELETE FROM krs_fields WHERE TABLE_ID IN (SELECT ID FROM krs_tables WHERE NAME = :name)");
				$stmt->bindParam(':name', $table, KPDO::TYPE_VARCHAR);
				$stmt->execute();
				$stmt = $db->prepare("DELETE FROM krs_tables WHERE NAME = :name");
				$stmt->bindParam(':name', $table, KPDO::TYPE_VARCHAR);
				$stmt->execute();
			}

			UtilityDB::ensureTable($db, $table, $fields);
		}

		change("new table $table");
	} else {
		ok($table);
	}
}

if (table_exists($db, 'krs_session')) {
	// written by the old framework: nobody could resume them
	if ($n = count_rows($db, 'krs_session')) {
		if ($apply) {
			$db->exec("DELETE FROM krs_session");
		}

		change("krs_session: $n old session(s) removed, everybody logs in again");
	} else {
		ok("krs_session empty");
	}
}

if (table_exists($db, 'krs_rpt_token') && ($n = count_rows($db, 'krs_rpt_token', 'EXPIRE < NOW()'))) {
	if ($apply) {
		$db->exec("DELETE FROM krs_rpt_token WHERE EXPIRE < NOW()");
	}

	change("krs_rpt_token: $n expired token(s) removed");
}

if (table_exists($db, 'krs_users_ip_rules')) {
	$n = count_rows($db, 'krs_users_ip_rules');
	$enabled = count_rows($db, 'krs_users_ip_rules', 'ENABLED <> 0');

	if ($enabled) {
		warn("krs_users_ip_rules: $enabled enabled rule(s) out of $n: the 1.0 doesn't apply them (the table is left as it is)");
	} else {
		ok("krs_users_ip_rules: $n rule(s), none enabled; not used by the 1.0, left as it is");
	}
}

// ---------------------------------------------------------------------------
section('Charset (to utf8mb4)');

$dbCollation = value($db, "SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :schema", array(':schema' => $schema));
$collation = strpos((string) $dbCollation, 'utf8mb4_') === 0 ? $dbCollation : 'utf8mb4_unicode_ci';

echo "  collation $collation" . ($allTables ? ", all the tables" : ", krs_ tables only (--all-tables for the others)") . "\n";

$tables = array();

foreach (rows($db, "SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME", array(':schema' => $schema)) as $t) {
	if (!$allTables && strpos($t['TABLE_NAME'], 'krs_') !== 0) {
		continue;
	}

	$columns = rows($db, "SELECT COLUMN_NAME, CHARACTER_SET_NAME, COLUMN_TYPE, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND CHARACTER_SET_NAME IS NOT NULL AND CHARACTER_SET_NAME <> 'utf8mb4' ORDER BY ORDINAL_POSITION", array(':schema' => $schema, ':table' => $t['TABLE_NAME']));

	if ($columns || strpos((string) $t['TABLE_COLLATION'], 'utf8mb4_') !== 0) {
		$tables[$t['TABLE_NAME']] = $columns;
	}
}

$binaryTypes = array('char' => 'binary', 'varchar' => 'varbinary', 'tinytext' => 'tinyblob', 'text' => 'blob', 'mediumtext' => 'mediumblob', 'longtext' => 'longblob');
$converted = 0;

foreach ($tables as $table => $columns) {
	// Old programs often wrote UTF-8 text into latin1 columns: a normal
	// conversion would turn every "è" into "Ã¨". Each latin1 column is
	// checked: rows with non-ASCII bytes, and how many of them are valid
	// UTF-8. All valid UTF-8 -> the bytes are kept as they are (through a
	// binary type); none -> real latin1, converted; both -> left alone.
	$checks = array();

	foreach ($columns as $column) {
		if ($column['CHARACTER_SET_NAME'] === 'latin1') {
			$c = q($column['COLUMN_NAME']);
			$checks[] = "SUM(HEX($c) REGEXP '^(..)*[89A-F]') AS " . q('n_' . $column['COLUMN_NAME']);
			$checks[] = "SUM(HEX($c) REGEXP '^(..)*[89A-F]' AND CONVERT(CONVERT(CAST($c AS BINARY) USING utf8mb4) USING binary) = CAST($c AS BINARY)) AS " . q('u_' . $column['COLUMN_NAME']);
		}
	}

	$nonAscii = 0;
	$utf8 = 0;
	$mixed = array();

	if ($checks) {
		$result = rows($db, "SELECT " . implode(', ', $checks) . " FROM " . q($table));

		foreach ($columns as $column) {
			if ($column['CHARACTER_SET_NAME'] === 'latin1') {
				$n = (int) $result[0]['n_' . $column['COLUMN_NAME']];
				$u = (int) $result[0]['u_' . $column['COLUMN_NAME']];
				$nonAscii += $n;
				$utf8 += $u;

				if ($u && $u < $n) {
					$mixed[] = $column['COLUMN_NAME'] . " ($u UTF-8 out of $n)";
				}
			}
		}
	}

	$charsets = implode('/', array_unique(array_column($columns, 'CHARACTER_SET_NAME')));
	$label = $columns ? "$table ($charsets, " . count($columns) . " column(s), $nonAscii value(s) with non-ASCII bytes)" : "$table (no text column)";

	if ($mixed || ($utf8 && $utf8 < $nonAscii)) {
		warn("$label: latin1 and UTF-8 mixed" . ($mixed ? " in " . implode(', ', $mixed) : " between columns") . ": left as it is, to be decided by hand");
		continue;
	}

	$asBytes = $utf8 > 0;

	if ($asBytes) {
		foreach ($columns as $column) {
			if (!isset($binaryTypes[$column['DATA_TYPE']])) {
				warn("$label: UTF-8 stored as latin1 in a column of type {$column['DATA_TYPE']} ({$column['COLUMN_NAME']}): left as it is, to be decided by hand");
				continue 2;
			}
		}
	}

	if ($apply) {
		try {
			if ($asBytes) {
				$toBinary = array();
				$toText = array();

				foreach ($columns as $column) {
					$c = q($column['COLUMN_NAME']);
					$type = preg_replace('/^' . $column['DATA_TYPE'] . '/i', $binaryTypes[$column['DATA_TYPE']], $column['COLUMN_TYPE']);
					$null = $column['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
					// information_schema gives the default as an SQL expression ('x', NULL)
					$default = $column['COLUMN_DEFAULT'] !== null ? ' DEFAULT ' . $column['COLUMN_DEFAULT'] : '';
					$comment = $column['COLUMN_COMMENT'] !== '' ? ' COMMENT ' . sql_string($column['COLUMN_COMMENT']) : '';

					$toBinary[] = "MODIFY $c $type $null$default$comment";
					$toText[] = "MODIFY $c {$column['COLUMN_TYPE']} CHARACTER SET utf8mb4 COLLATE $collation $null$default$comment";
				}

				$db->exec("ALTER TABLE " . q($table) . " " . implode(', ', $toBinary));
				$db->exec("ALTER TABLE " . q($table) . " " . implode(', ', $toText) . ", DEFAULT CHARACTER SET utf8mb4 COLLATE $collation");
			} else {
				$db->exec("ALTER TABLE " . q($table) . " CONVERT TO CHARACTER SET utf8mb4 COLLATE $collation");
			}
		} catch (KPDOException $e) {
			fail("$label: " . strip_tags(str_replace('<br>', ' - ', $e->getMessage())));
			continue;
		}
	}

	$converted++;
	change("$label: " . ($asBytes ? "UTF-8 kept byte for byte" : ($columns ? "converted from $charsets" : "default charset changed")));
}

if (!$tables) {
	ok("every table is already utf8mb4");
}

// ---------------------------------------------------------------------------
section('Users and passwords');

$users = count_rows($db, 'krs_users');
$active = count_rows($db, 'krs_users', "ACTIVATION <> '' AND ACTIVATION <> '0'");
$bcrypt = count_rows($db, 'krs_users', "PASSWORD LIKE '\$2y\$%'");
$legacy = count_rows($db, 'krs_users', "PASSWORD REGEXP '^[0-9a-fA-F]{40}\$'");
$empty = count_rows($db, 'krs_users', "PASSWORD = ''");
$other = $users - $bcrypt - $legacy - $empty;
$quoted = count_rows($db, 'krs_users', "USERNAME LIKE '%&#039;%'");
$noGroup = count_rows($db, 'krs_users', "ID NOT IN (SELECT USER_ID FROM krs_user_usergroup_map)");

ok("$users user(s), $active active");
ok("$bcrypt bcrypt password(s), $legacy salted sha1 (converted to bcrypt at the first login)");

if ($empty) {
	warn("$empty user(s) without a password: they can't log in");
}

if ($other) {
	warn("$other password(s) in an unknown format: those users can't log in, they need a new password");
}

if ($legacy) {
	warn("the sha1 hashes are recognised if they are sha1(SALT . sha1(SALT . sha1(password))), as in 0.6: try logging in with one real user before going live");
}

if ($quoted) {
	ok("$quoted username(s) saved with &#039; in place of the quote: the login handles them and fixes them");
}

if ($noGroup) {
	warn("$noGroup user(s) in no group: after logging in they can't open any program");
}

// ---------------------------------------------------------------------------
section('Modules, groups and programs');

$modulePaths = array();
$modules = rows($db, "SELECT ID, PARENT_ID, NAME FROM krs_modules ORDER BY ID");
$byId = array();

foreach ($modules as $module) {
	$byId[$module['ID']] = $module;
}

// module path as the menu builds it (generete_path_module)
$pathOf = function ($id) use ($byId) {
	$path = array();

	for ($guard = 0; isset($byId[$id]) && $guard < 20; $guard++) {
		array_unshift($path, $byId[$id]['NAME']);
		$id = $byId[$id]['PARENT_ID'];
	}

	return implode('/', $path);
};

foreach ($modules as $module) {
	$path = $pathOf($module['ID']);
	$progs = (int) value($db, "SELECT COUNT(*) FROM krs_progs_modules_map WHERE MODULE_ID = :id", array(':id' => (int) $module['ID']));
	$groups = (int) value($db, "SELECT COUNT(*) FROM krs_usergroups_modules_map WHERE MODULE_ID = :id", array(':id' => (int) $module['ID']));
	$modulePaths[$module['ID']] = $path;

	if ($module['NAME'] === 'admin') {
		$admins = (int) value($db, "SELECT COUNT(DISTINCT u.ID) FROM krs_users u JOIN krs_user_usergroup_map ug ON ug.USER_ID = u.ID JOIN krs_usergroups_modules_map gm ON gm.USERGROUP_ID = ug.GROUP_ID WHERE gm.MODULE_ID = :id AND u.ACTIVATION <> '' AND u.ACTIVATION <> '0'", array(':id' => (int) $module['ID']));

		if ($admins) {
			ok("module admin: $groups group(s), $admins active user(s) can enter the administration");
		} else {
			warn("module admin: no active user is in a group of the admin module, nobody can enter the administration");
		}

		continue;
	}

	$dir = DIR_MODULE . $path;
	$state = is_dir($dir) ? (is_file($dir . '/installation/install.php') ? "with installer" : "no installer") : "NOT FOUND in " . DIR_MODULE;

	if (!is_dir($dir)) {
		warn("module $path: $progs program(s), $groups group(s), folder $state");
	} else {
		ok("module $path: $progs program(s), $groups group(s), $state");
	}
}

if (!isset(array_column($modules, 'NAME', 'NAME')['admin'])) {
	warn("there is no admin module: nobody can enter the administration (the 1.0 installer creates it with the Administrator group)");
}

$noModule = (int) value($db, "SELECT COUNT(*) FROM krs_usergroups WHERE ID NOT IN (SELECT USERGROUP_ID FROM krs_usergroups_modules_map)");
ok(count_rows($db, 'krs_usergroups') . " group(s)" . ($noModule ? ", $noModule not tied to a module" : ""));

// '' counts as 0, as in the old framework
$flags = count_rows($db, 'krs_progs_usergroups_map', "READ_ENABLED NOT IN ('', '0', '1') OR WRITE_ENABLED NOT IN ('', '0', '1') OR EXECUTION_ENABLED NOT IN ('', '0', '1')");
$none = count_rows($db, 'krs_progs_usergroups_map', "READ_ENABLED <> '1' AND WRITE_ENABLED <> '1' AND EXECUTION_ENABLED <> '1'");
$all = count_rows($db, 'krs_progs_usergroups_map', "READ_ENABLED = '1' AND WRITE_ENABLED = '1' AND EXECUTION_ENABLED = '1'");
$grants = count_rows($db, 'krs_progs_usergroups_map');
$partial = $grants - $none - $all;

ok("$grants permission(s) of groups on programs: $all with every level" . ($none ? ", $none with none (no access, as before)" : ""));

if ($partial) {
	warn("$partial permission(s) with only some levels: in 1.0 EXECUTION_ENABLED means \"delete\" and the methods are checked one by one (read = index/get/list/search/view/autocomplete, delete = delete*, write = the rest), check those groups");
}

if ($flags) {
	warn("$flags permission(s) with values other than 0/1: the 1.0 reads only 1 as enabled");
}

// The 1.0 refuses the routes of a module that no program points to, so
// every controller of an installed module needs one (or const ACCESS).
$controllerProgs = array();
$broken = array();

foreach (rows($db, "SELECT p.ID, p.PROG, p.PATTERN, pm.MODULE_ID FROM krs_progs p LEFT JOIN krs_progs_modules_map pm ON pm.PROG_ID = p.ID ORDER BY p.ID") as $prog) {
	if ($prog['PATTERN'] === 'direct' || $prog['PROG'] === '' || !isset($modulePaths[$prog['MODULE_ID']])) {
		continue;
	}

	$route = preg_replace('/[&?].*$/s', '', $modulePaths[$prog['MODULE_ID']] . '/' . $prog['PROG'] . ($prog['PATTERN'] === '' ? '.php' : ''));
	$file = realpath((new Action($route))->getFile());

	if ($file) {
		$controllerProgs[$file] = true;
	} elseif (is_dir(DIR_MODULE . $modulePaths[$prog['MODULE_ID']])) {
		$broken[] = $route;
	}
}

if ($broken) {
	warn(count($broken) . " program(s) point to a controller that doesn't exist: " . implode(', ', array_slice($broken, 0, 15)) . (count($broken) > 15 ? ', ...' : ''));
}

foreach ($modules as $module) {
	$dir = DIR_MODULE . $modulePaths[$module['ID']] . '/controller';

	if ($module['NAME'] === 'admin' || $module['PARENT_ID'] != 0 || !is_dir($dir)) {
		continue;
	}

	$missing = array();
	$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

	foreach ($files as $file) {
		if ($file->getExtension() === 'php' && !isset($controllerProgs[realpath($file->getPathname())])) {
			$code = (string) file_get_contents($file->getPathname());

			// a controller opening itself to every logged-in user
			if (!preg_match('/const\s+ACCESS\b/', $code)) {
				$missing[] = substr($file->getPathname(), strlen($dir) + 1, -4);
			}
		}
	}

	if ($missing) {
		sort($missing);
		warn("module {$modulePaths[$module['ID']]}: " . count($missing) . " controller(s) without a program, the 1.0 refuses them: " . implode(', ', $missing) . " (register them, from the installer of the module or in Admin > Programs, and give them to the groups)");
	} else {
		ok("module {$modulePaths[$module['ID']]}: every controller has a program");
	}
}

// ---------------------------------------------------------------------------
echo "\n" . ($apply ? "Upgrade finished" : "Report finished, nothing was written") . ": $errors error(s), $warnings warning(s).\n";

if (!$apply) {
	echo "Run again with --apply to make the changes marked todo.\n";
}

exit($errors ? 2 : 0);
