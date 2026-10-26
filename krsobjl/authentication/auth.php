<?php
class Auth {
	private $session;
	private $log;
	private $config;
	private $attemptsDb = null;
	private $loginBlocked = false;
	
	public function __construct($registry) {
		$this->session = $registry->get('session');
		$this->log = $registry->get('log');
		$this->config = $registry->get('config');
	}
	
	// Password hashing with password_hash() (bcrypt today, whatever PHP picks
	// as its default tomorrow): slow to brute-force and with the salt inside
	// the hash, so the SALT column stays empty. The only place hashes are made.
	public static function hash_password($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}

	// Accepts both password_hash() hashes and those of earlier installations,
	// sha1(SALT . sha1(SALT . sha1(password))), which are converted at the
	// first successful login (see auth_check_user_login).
	private function verify_password($db_user, $password) {
		if (password_get_info($db_user['PASSWORD'])['algo']) {
			return password_verify($password, $db_user['PASSWORD']);
		}

		return hash_equals((string) $db_user['PASSWORD'], sha1($db_user['SALT'] . sha1($db_user['SALT'] . sha1($password))));
	}

	public function auth_check_user_login($username, $password) {
		$this->loginBlocked = false;

		if (!($db = UtilityDB::getDBfromTable("krs_users"))) {
			return false;
		}

		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

		// Too many recent failures for this username from this IP: the
		// password isn't even checked until the window has passed
		if ($this->count_failed_logins($username, $ip) >= $this->login_max_attempts()) {
			$this->loginBlocked = true;

			return false;
		}

		$stmt_user = $db->prepare("SELECT * FROM t:krs_users WHERE USERNAME = :username");
		$stmt_user->bindTable("krs_users", "krs_users");
		$stmt_user->bindParam(":username", $username, KPDO::TYPE_VARCHAR);
		$stmt_user->execute();

		$db_user = $stmt_user->fetch(KPDO::FETCH_ASSOC);

		if (!$db_user) {
			// Unknown user: a hash is computed anyway, so the response takes
			// the same time and doesn't reveal which usernames exist (bcrypt
			// makes the difference easy to measure).
			password_verify($password, '$2y$12$' . str_repeat('a', 53));

			$this->record_failed_login($username, $ip);

			return false;
		}

		if ($username != $db_user['USERNAME'] || !$this->verify_password($db_user, $password)) {
			$this->record_failed_login($username, $ip);

			return false;
		}

		$this->clear_failed_logins($username, $ip);

		// user disabled by an admin (ACTIVATION) -> right credentials
		// but the login is refused anyway
		if (empty($db_user['ACTIVATION'])) {
			return false;
		}

		if (!password_get_info($db_user['PASSWORD'])['algo'] || password_needs_rehash($db_user['PASSWORD'], PASSWORD_DEFAULT)) {
			$hash = self::hash_password($password);
			$salt = '';

			$stmt_rehash = $db->prepare("UPDATE t:krs_users SET SALT = :salt, PASSWORD = :password WHERE ID = :id");
			$stmt_rehash->bindTable("krs_users", "krs_users");
			$stmt_rehash->bindParam(":salt", $salt, KPDO::TYPE_VARCHAR);
			$stmt_rehash->bindParam(":password", $hash, KPDO::TYPE_VARCHAR);
			$stmt_rehash->bindParam(":id", $db_user['ID'], KPDO::TYPE_INTEGER);
			$stmt_rehash->execute();
		}

		return $this->auth_get_details($db_user['ID']);
	}

	// true if the last auth_check_user_login was refused because of too many
	// failed attempts (the controllers show a different message)
	public function auth_login_blocked() {
		return $this->loginBlocked;
	}

	// Login attempt limit (brute force): after login_max_attempts failures
	// within login_lockout_minutes for the same username + IP pair, logins of
	// that pair are refused until the end of the window. The pair, and not the
	// IP alone, because behind a proxy every user comes from the same address;
	// and not the username alone, because anyone could lock someone else's
	// account.
	private function login_max_attempts() {
		$value = $this->config ? (int) $this->config->get('setting.login_max_attempts') : 0;

		return $value > 0 ? $value : 5;
	}

	public function login_lockout_minutes() {
		$value = $this->config ? (int) $this->config->get('setting.login_lockout_minutes') : 0;

		return $value > 0 ? $value : 15;
	}

	// Table of the attempts, created on first use on installations made before
	// it existed (on new ones the installer creates it).
	private function login_attempts_db() {
		global $db;

		if ($this->attemptsDb === null) {
			UtilityDB::ensureTable($db, 'krs_login_attempts', array(
				array('ID',       KPDO::TYPE_INTEGER, null,  KPDO::PRIMARY_KEY|KPDO::NOT_NULL|KPDO::AUTO_INCREMENT),
				array('USERNAME', KPDO::TYPE_VARCHAR, '255', KPDO::NOT_NULL),
				array('IP',       KPDO::TYPE_VARCHAR, '255', KPDO::NOT_NULL),
				array('TIME',     KPDO::TYPE_INTEGER, null,  KPDO::NOT_NULL),
			));

			$this->attemptsDb = UtilityDB::getDBfromTable('krs_login_attempts');
		}

		return $this->attemptsDb;
	}

	private function count_failed_logins($username, $ip) {
		$db = $this->login_attempts_db();
		$since = time() - $this->login_lockout_minutes() * 60;

		$stmt = $db->prepare("SELECT COUNT(*) AS TOTAL FROM t:krs_login_attempts WHERE USERNAME = :username AND IP = :ip AND TIME > :since");
		$stmt->bindTable("krs_login_attempts", "krs_login_attempts");
		$stmt->bindParam(":username", $username, KPDO::TYPE_VARCHAR);
		$stmt->bindParam(":ip", $ip, KPDO::TYPE_VARCHAR);
		$stmt->bindParam(":since", $since, KPDO::TYPE_INTEGER);
		$stmt->execute();

		$row = $stmt->fetch(KPDO::FETCH_ASSOC);

		return $row ? (int) $row['TOTAL'] : 0;
	}

	private function record_failed_login($username, $ip) {
		$db = $this->login_attempts_db();
		$now = time();
		$expired = $now - $this->login_lockout_minutes() * 60;

		// cleanup: attempts outside the window are no longer needed
		$stmt = $db->prepare("DELETE FROM t:krs_login_attempts WHERE TIME <= :expired");
		$stmt->bindTable("krs_login_attempts", "krs_login_attempts");
		$stmt->bindParam(":expired", $expired, KPDO::TYPE_INTEGER);
		$stmt->execute();

		$stmt = $db->prepare("INSERT INTO t:krs_login_attempts (USERNAME, IP, TIME) VALUES (:username, :ip, :time)");
		$stmt->bindTable("krs_login_attempts", "krs_login_attempts");
		$stmt->bindParam(":username", $username, KPDO::TYPE_VARCHAR);
		$stmt->bindParam(":ip", $ip, KPDO::TYPE_VARCHAR);
		$stmt->bindParam(":time", $now, KPDO::TYPE_INTEGER);
		$stmt->execute();
	}

	private function clear_failed_logins($username, $ip) {
		$db = $this->login_attempts_db();

		$stmt = $db->prepare("DELETE FROM t:krs_login_attempts WHERE USERNAME = :username AND IP = :ip");
		$stmt->bindTable("krs_login_attempts", "krs_login_attempts");
		$stmt->bindParam(":username", $username, KPDO::TYPE_VARCHAR);
		$stmt->bindParam(":ip", $ip, KPDO::TYPE_VARCHAR);
		$stmt->execute();
	}

	public function auth_check_user_pin($pin) {
		$pin = sha1($pin);
	
		if (($db = UtilityDB::getDBfromTable("krs_users_auth"))) {
				
			$stmt_user = $db->prepare("SELECT * FROM t:krs_users_auth WHERE PIN = :pin");
			$stmt_user->bindTable("krs_users_auth", "krs_users_auth");
			$stmt_user->bindParam(":pin", $pin, KPDO::TYPE_VARCHAR);
				
			$stmt_user->execute();
				
			if (($db_user = $stmt_user->fetch(KPDO::FETCH_ASSOC))) {
				$details = $this->auth_get_details($db_user['USER_ID']);

				// same check as auth_check_user_login: a disabled user
				// can't get in with a PIN either
				if (empty($details['ACTIVATION'])) {
					return false;
				}

				return $details;
			} else {
				return false;
			}
		}
	}

	public function auth_check_user_rfid($tag) {
		$tag = sha1($tag);
		
		if (($db = UtilityDB::getDBfromTable("krs_users_auth"))) {
	
			$stmt_user = $db->prepare("SELECT * FROM t:krs_users_auth WHERE TAG = :tag");
			$stmt_user->bindTable("krs_users_auth", "krs_users_auth");
			$stmt_user->bindParam(":tag", $tag, KPDO::TYPE_VARCHAR);
	
			$stmt_user->execute();
	
			if (($db_user = $stmt_user->fetch(KPDO::FETCH_ASSOC))) {
				$details = $this->auth_get_details($db_user['USER_ID']);

				// same check as auth_check_user_login: a disabled user
				// can't get in with an RFID tag either
				if (empty($details['ACTIVATION'])) {
					return false;
				}

				return $details;
			} else {
				return false;
			}
		}
	}

	// Machine-to-machine calls (APIs, the scheduler's cron): the key isn't
	// stored in clear but as a SHA-256 hash in krs_api_keys. Looking up the
	// hash of a random 256-bit key leaks nothing through response times, so
	// no constant-time comparison is needed.
	public function auth_check_api_key($key) {
		if (!is_string($key) || strlen($key) < 32) {
			return false;
		}

		$hash = hash('sha256', $key);

		if (($db = UtilityDB::getDBfromTable("krs_api_keys"))) {

			$stmt_key = $db->prepare("SELECT * FROM t:krs_api_keys WHERE KEY_HASH = :hash AND ACTIVE = 1");
			$stmt_key->bindTable("krs_api_keys", "krs_api_keys");
			$stmt_key->bindParam(":hash", $hash, KPDO::TYPE_VARCHAR);
			$stmt_key->execute();

			if (($db_key = $stmt_key->fetch(KPDO::FETCH_ASSOC))) {
				$details = $this->auth_get_details($db_key['USER_ID']);

				// same check as auth_check_user_login: the key of a disabled
				// user is no longer valid
				if (empty($details['ACTIVATION'])) {
					return false;
				}

				$now = date('Y-m-d H:i:s');

				$stmt_used = $db->prepare("UPDATE t:krs_api_keys SET LAST_USED = :now WHERE ID = :id");
				$stmt_used->bindTable("krs_api_keys", "krs_api_keys");
				$stmt_used->bindParam(":now", $now, KPDO::TYPE_VARCHAR);
				$stmt_used->bindParam(":id", $db_key['ID'], KPDO::TYPE_INTEGER);
				$stmt_used->execute();

				return $details;
			}
		}

		return false;
	}

	public function auth_get_details($user_id) {
		if (isset($user_id)) {
		
			if (($db = UtilityDB::getDBfromTable("krs_users"))) {
					
				$stmt_user = $db->prepare("SELECT * FROM t:krs_users WHERE ID = :id");
				$stmt_user->bindTable("krs_users", "krs_users");
				$stmt_user->bindParam(":id", $user_id, KPDO::TYPE_INTEGER);
				$stmt_user->execute();
		
				$user = $stmt_user->fetch(KPDO::FETCH_ASSOC);
				$user['GROUPS'] = $this->getGroups($user_id);
				return $user;
			}
		} else {
			return false;
		}
	}
	
	public function auth_check_permission($user, $prog_id, $mode) {
		try {
			$db = UtilityDB::getDBfromTable("krs_progs_usergroups_map");
			
			$query = "SELECT * FROM t:krs_progs_usergroups_map WHERE PROG_ID = :prog_id AND GROUP_ID = :group_id";
				
			if ($mode) {
				$binary_mode = str_pad(decbin($mode), 3, 0, STR_PAD_LEFT);
				$read = $binary_mode[0];
				$write = $binary_mode[1];
				$execution = $binary_mode[2];
				
				$query .= " AND READ_ENABLED = :read AND WRITE_ENABLED = :write AND EXECUTION_ENABLED = :execution";
			}
				
			$stmt_permission = $db->prepare($query);
			$stmt_permission->bindTable("krs_progs_usergroups_map", "krs_progs_usergroups_map");
			$stmt_permission->bindParam(":prog_id", $prog_id, KPDO::TYPE_INTEGER);
			
			if ($mode) {
				$stmt_permission->bindParam(":read", $read, KPDO::TYPE_INTEGER);
				$stmt_permission->bindParam(":write", $write, KPDO::TYPE_INTEGER);
				$stmt_permission->bindParam(":execution", $execution, KPDO::TYPE_INTEGER);
			}
			
			foreach ($user['GROUPS'] as $group) {
				$stmt_permission->bindParam("group_id", $group['ID'], KPDO::TYPE_INTEGER);
				$stmt_permission->execute();
				
				if($stmt_permission->rowCount()) {
					return true;
				}
			}
				
			return false;
		} catch (KPDOException $e) {
			trigger_error($e);
			return false;
		}
	}
	
	// A user can use a module if at least one of their groups belongs to that
	// module (krs_usergroups_modules_map, the "Module" field of the group).
	// This is how administrators are told apart: the groups of the "admin"
	// module, like the Administrator group created by the installer.
	public function auth_check_module_access($user, $module_name) {
		if (empty($user['GROUPS'])) {
			return false;
		}

		try {
			$db = UtilityDB::getDBfromTable("krs_usergroups_modules_map");

			$stmt_access = $db->prepare("SELECT um.USERGROUP_ID FROM t:krs_usergroups_modules_map as um, t:krs_modules as m WHERE um.MODULE_ID = m.ID AND m.NAME = :module AND um.USERGROUP_ID = :group_id");
			$stmt_access->bindTable("krs_usergroups_modules_map", "krs_usergroups_modules_map");
			$stmt_access->bindTable("krs_modules", "krs_modules");
			$stmt_access->bindParam(":module", $module_name, KPDO::TYPE_VARCHAR);

			foreach ($user['GROUPS'] as $group) {
				$stmt_access->bindParam(":group_id", $group['ID'], KPDO::TYPE_INTEGER);
				$stmt_access->execute();

				if ($stmt_access->fetch(KPDO::FETCH_ASSOC)) {
					return true;
				}
			}

			return false;
		} catch (KPDOException $e) {
			trigger_error($e);
			return false;
		}
	}

	public function auth_check_user_and_timeout() {
		if (empty($this->session->data['user_id'])) {
			return true;
		} else {
			return $this->auth_check_timeout();
		}
	}
	
	private function auth_check_timeout() {
		if (!empty($this->session->data['time_start']) && $this->session->data['time_start'] <= (time() - (ini_get('session.gc_maxlifetime') / 2))) {
			return false;
		} else {
			return true;
		}
	}
	
	private function getGroups($user_id) {
		$groups = array();
		
		if (($db = UtilityDB::getDBfromTable("krs_usergroups"))) {
				
			$stmt_group = $db->prepare("
				SELECT g.* FROM t:krs_users as u, t:krs_usergroups as g, t:krs_user_usergroup_map as m WHERE
				u.ID = :id AND
				m.USER_ID = u.ID AND
				g.ID = m.GROUP_ID");
			$stmt_group->bindTable("krs_users", "krs_users");
			$stmt_group->bindTable("krs_usergroups", "krs_usergroups");
			$stmt_group->bindTable("krs_user_usergroup_map", "krs_user_usergroup_map");
			$stmt_group->bindParam("id", $user_id, KPDO::TYPE_INTEGER);
			$stmt_group->execute();
			
			while(($group = $stmt_group->fetch())) {
				$groups[] = $group;
			}
		}
		
		return $groups;
	}
	
	/* DEPRECATED DON'T USE auth_check_user */
	static function auth_check_user() {
		if (isset($this->session->data['user_id'])) {
			$user_id = $this->session->data['user_id'];
				
			if (($db = UtilityDB::getDBfromTable("krs_users"))) {
					
				$stmt_user = $db->prepare("SELECT * FROM t:krs_users WHERE ID = :id");
				$stmt_user->bindTable("krs_users", "krs_users");
				$stmt_user->bindParam(":id", $user_id, KPDO::TYPE_INTEGER);
				$stmt_user->execute();
	
				$user = $stmt_user->fetch(KPDO::FETCH_ASSOC);
				$user['GROUPS'] = $this->getGroups($user_id);
				return $user;
			}
		} else {
			return false;
		}
	}
	/* DEPRECATED DON'T USE auth_check_user */
}
?>
