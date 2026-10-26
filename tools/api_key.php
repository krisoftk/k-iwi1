<?php
/**
 * API key management from the command line.
 *
 *   php tools/api_key.php create <username> [description]
 *   php tools/api_key.php list
 *   php tools/api_key.php revoke <id>
 *
 * The key is shown only once, when it's created: the database keeps only
 * its SHA-256 hash (plus the first 8 characters, to recognise it in the
 * list). Whoever uses it acts as the user it belongs to, with the same
 * permissions.
 *
 * On first run it creates the krs_api_keys table if it's missing
 * (installations made before API keys were introduced).
 */

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

require_once dirname(__DIR__) . '/krsobjp/globals.php';
require_once DIR_LIBRARY . 'common/application_management.php';
require_once DIR_LIBRARY . 'common/bootstrap.php';

$config = new Config();
$config->load('config');

$db = new KPDO($config->get('database.driver'), $config->get('database.host'), $config->get('database.port'), $config->get('database.user'), $config->get('database.password'), $config->get('database.name'));

function ensure_api_keys_table($db) {
	// same definition as krsobjm/install/controller/step_1.php
	$created = UtilityDB::ensureTable($db, 'krs_api_keys', array(
		array('ID',          KPDO::TYPE_INTEGER,  null,  KPDO::PRIMARY_KEY|KPDO::NOT_NULL|KPDO::AUTO_INCREMENT),
		array('USER_ID',     KPDO::TYPE_INTEGER,  null,  KPDO::NOT_NULL),
		array('KEY_HASH',    KPDO::TYPE_VARCHAR,  '255', KPDO::NOT_NULL|KPDO::UNIQUE),
		array('KEY_PREFIX',  KPDO::TYPE_VARCHAR,  '255', KPDO::NOT_NULL),
		array('DESCRIPTION', KPDO::TYPE_VARCHAR,  '255', null),
		array('ACTIVE',      KPDO::TYPE_SMALLINT, null,  KPDO::NOT_NULL),
		array('CREATED',     KPDO::TYPE_VARCHAR,  '255', KPDO::NOT_NULL),
		array('LAST_USED',   KPDO::TYPE_VARCHAR,  '255', null),
	));

	if ($created) {
		echo "Creata la tabella krs_api_keys.\n";
	}
}

function usage() {
	fwrite(STDERR, "Uso:\n  php tools/api_key.php create <username> [descrizione]\n  php tools/api_key.php list\n  php tools/api_key.php revoke <id>\n");
	exit(1);
}

ensure_api_keys_table($db);

$command = isset($argv[1]) ? $argv[1] : '';

switch ($command) {
	case 'create':
		if (empty($argv[2])) {
			usage();
		}

		$username = $argv[2];
		$description = isset($argv[3]) ? $argv[3] : '';

		$stmt = $db->prepare("SELECT ID, ACTIVATION FROM t:krs_users WHERE USERNAME = :username");
		$stmt->bindTable("krs_users", "krs_users");
		$stmt->bindParam(":username", $username, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		if (!($user = $stmt->fetch(KPDO::FETCH_ASSOC))) {
			fwrite(STDERR, "Utente $username non trovato.\n");
			exit(1);
		}

		if (empty($user['ACTIVATION'])) {
			fwrite(STDERR, "Attenzione: l'utente $username e' disabilitato, la chiave non funzionera' finche' non viene riattivato.\n");
		}

		$key = bin2hex(random_bytes(32));
		$hash = hash('sha256', $key);
		$prefix = substr($key, 0, 8);
		$active = 1;
		$created = date('Y-m-d H:i:s');

		$stmt = $db->prepare("INSERT INTO t:krs_api_keys (USER_ID, KEY_HASH, KEY_PREFIX, DESCRIPTION, ACTIVE, CREATED) VALUES (:user_id, :hash, :prefix, :description, :active, :created)");
		$stmt->bindTable("krs_api_keys", "krs_api_keys");
		$stmt->bindParam(":user_id", $user['ID'], KPDO::TYPE_INTEGER);
		$stmt->bindParam(":hash", $hash, KPDO::TYPE_VARCHAR);
		$stmt->bindParam(":prefix", $prefix, KPDO::TYPE_VARCHAR);
		$stmt->bindParam(":description", $description, KPDO::TYPE_VARCHAR);
		$stmt->bindParam(":active", $active, KPDO::TYPE_INTEGER);
		$stmt->bindParam(":created", $created, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		echo "API key creata (id " . $db->lastInsertId() . ") per l'utente $username.\n";
		echo "Copiala ora, non verra' piu' mostrata:\n\n" . $key . "\n";
		break;

	case 'list':
		$stmt = $db->prepare("SELECT k.ID, u.USERNAME, k.KEY_PREFIX, k.DESCRIPTION, k.ACTIVE, k.CREATED, k.LAST_USED FROM t:krs_api_keys, t:krs_users WHERE k.USER_ID = u.ID ORDER BY k.ID");
		$stmt->bindTable("krs_api_keys", "krs_api_keys", "k");
		$stmt->bindTable("krs_users", "krs_users", "u");
		$stmt->execute();

		printf("%-5s %-15s %-10s %-8s %-20s %-20s %s\n", 'ID', 'UTENTE', 'CHIAVE', 'ATTIVA', 'CREATA', 'ULTIMO USO', 'DESCRIZIONE');

		while (($row = $stmt->fetch(KPDO::FETCH_ASSOC))) {
			printf("%-5s %-15s %-10s %-8s %-20s %-20s %s\n", $row['ID'], $row['USERNAME'], $row['KEY_PREFIX'] . '...', $row['ACTIVE'] ? 'si' : 'no', $row['CREATED'], $row['LAST_USED'] ? $row['LAST_USED'] : '-', $row['DESCRIPTION']);
		}
		break;

	case 'revoke':
		if (empty($argv[2]) || !ctype_digit($argv[2])) {
			usage();
		}

		$id = (int) $argv[2];
		$active = 0;

		$stmt = $db->prepare("UPDATE t:krs_api_keys SET ACTIVE = :active WHERE ID = :id");
		$stmt->bindTable("krs_api_keys", "krs_api_keys");
		$stmt->bindParam(":active", $active, KPDO::TYPE_INTEGER);
		$stmt->bindParam(":id", $id, KPDO::TYPE_INTEGER);
		$stmt->execute();

		echo $stmt->rowCount() ? "API key $id revocata.\n" : "Nessuna API key con id $id.\n";
		break;

	default:
		usage();
}
?>
