<?php
class ModelAdminUserUser extends Model {
	private function hashPassword($plain) {
		return array(
			'salt'     => '',
			'password' => Auth::hash_password($plain),
		);
	}

	public function addUser($data) {
		$db = UtilityDB::getDBfromTable('krs_users');

		try {
			$hash = $this->hashPassword($data['password']);

			$stmt = $db->prepare('INSERT INTO t:users
				(FIRSTNAME, LASTNAME, USERNAME, EMAIL, SALT, PASSWORD, REGISTER_DATA, LAST_VISIT_DATA, ACTIVATION, LANGUAGE)
				VALUES
				(:firstname, :lastname, :username, :email, :salt, :password, :register_data, :last_visit_data, :activation, :language)');
			$stmt->bindTable('users', 'krs_users');
			$stmt->bindParam('firstname', $data['firstname'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('lastname', $data['lastname'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('username', $data['username'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('email', $data['email'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('salt', $hash['salt'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('password', $hash['password'], KPDO::TYPE_VARCHAR);
			$registerDate = date('Y-m-d');
			$stmt->bindParam('register_data', $registerDate, KPDO::TYPE_VARCHAR);
			$stmt->bindParam('last_visit_data', $registerDate, KPDO::TYPE_VARCHAR);
			$stmt->bindParam('activation', $data['status'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('language', $data['language'], KPDO::TYPE_VARCHAR);
			$stmt->execute();

			$userId = $db->lastInsertId();

			$this->saveUserGroups($db, $userId, $data['usergroups']);
			$this->saveUserModule($db, $userId, $data['module']);

			return $userId;
		} catch (KPDOException $e) {
			$this->log->write($e);

			return false;
		}
	}

	public function editUser($userId, $data) {
		$db = UtilityDB::getDBfromTable('krs_users');

		try {
			$stmt = $db->prepare('UPDATE t:users
				SET FIRSTNAME = :firstname, LASTNAME = :lastname, USERNAME = :username, EMAIL = :email, ACTIVATION = :activation, LANGUAGE = :language
				WHERE ID = :id');
			$stmt->bindTable('users', 'krs_users');
			$stmt->bindParam('firstname', $data['firstname'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('lastname', $data['lastname'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('username', $data['username'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('email', $data['email'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('activation', $data['status'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('language', $data['language'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('id', $userId, KPDO::TYPE_VARCHAR);
			$stmt->execute();

			// The password is updated only if the field was filled in the
			// form: otherwise SALT/PASSWORD would be left with the hash of an
			// empty string at every user change.
			if (!empty($data['password'])) {
				$hash = $this->hashPassword($data['password']);

				$stmtPassword = $db->prepare('UPDATE t:users SET SALT = :salt, PASSWORD = :password WHERE ID = :id');
				$stmtPassword->bindTable('users', 'krs_users');
				$stmtPassword->bindParam('salt', $hash['salt'], KPDO::TYPE_VARCHAR);
				$stmtPassword->bindParam('password', $hash['password'], KPDO::TYPE_VARCHAR);
				$stmtPassword->bindParam('id', $userId, KPDO::TYPE_VARCHAR);
				$stmtPassword->execute();
			}

			if (isset($data['usergroups'])) {
				$this->saveUserGroups($db, $userId, $data['usergroups']);
			}

			if (isset($data['module'])) {
				$this->saveUserModule($db, $userId, $data['module']);
			}

			return true;
		} catch (KPDOException $e) {
			$this->log->write($e);

			return false;
		}
	}

	private function saveUserGroups($db, $userId, $groups) {
		$stmt = $db->prepare('DELETE FROM t:user_usergroup_map WHERE USER_ID = :user_id');
		$stmt->bindTable('user_usergroup_map', 'krs_user_usergroup_map');
		$stmt->bindParam('user_id', $userId, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		$groupId = '';
		$stmt = $db->prepare('INSERT INTO t:user_usergroup_map (USER_ID, GROUP_ID) VALUES (:user_id, :group_id)');
		$stmt->bindTable('user_usergroup_map', 'krs_user_usergroup_map');
		$stmt->bindParam('user_id', $userId, KPDO::TYPE_INTEGER);
		$stmt->bindParam('group_id', $groupId, KPDO::TYPE_INTEGER);

		foreach (array_unique($groups) as $groupId) {
			$stmt->execute();
		}
	}

	private function saveUserModule($db, $userId, $moduleId) {
		$stmt = $db->prepare('DELETE FROM t:users_modules_map WHERE USER_ID = :user_id');
		$stmt->bindTable('users_modules_map', 'krs_users_modules_map');
		$stmt->bindParam('user_id', $userId, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		$stmt = $db->prepare('INSERT INTO t:users_modules_map (MODULE_ID, USER_ID) VALUES (:module_id, :user_id)');
		$stmt->bindTable('users_modules_map', 'krs_users_modules_map');
		$stmt->bindParam('user_id', $userId, KPDO::TYPE_INTEGER);
		$stmt->bindParam('module_id', $moduleId, KPDO::TYPE_INTEGER);
		$stmt->execute();
	}

	public function deleteUser($userId) {
		$db = UtilityDB::getDBfromTable('krs_users');

		// Also cleans up the mappings, otherwise orphan rows stay behind and make
		// the deleted user still look "assigned" to a group (e.g. they block
		// the deletion of the group in ModelAdminUserGroup::
		// getTotalUsersByGroupId()).
		$stmt = $db->prepare('DELETE FROM t:user_usergroup_map WHERE USER_ID = :user_id');
		$stmt->bindTable('user_usergroup_map', 'krs_user_usergroup_map');
		$stmt->bindParam('user_id', $userId, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		$stmt = $db->prepare('DELETE FROM t:users_modules_map WHERE USER_ID = :user_id');
		$stmt->bindTable('users_modules_map', 'krs_users_modules_map');
		$stmt->bindParam('user_id', $userId, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		$stmt = $db->prepare('DELETE FROM t:users WHERE ID = :id');
		$stmt->bindTable('users', 'krs_users');
		$stmt->bindParam('id', $userId, KPDO::TYPE_VARCHAR);
		$stmt->execute();
	}

	public function getUser($userId) {
		$db = UtilityDB::getDBfromTable('krs_users');
		$stmt = $db->prepare('SELECT * FROM t:users WHERE ID = :id');
		$stmt->bindTable('users', 'krs_users');
		$stmt->bindParam('id', $userId, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		return $stmt->fetch();
	}

	public function getUserByUsername($username) {
		$db = UtilityDB::getDBfromTable('krs_users');
		$stmt = $db->prepare('SELECT * FROM t:users WHERE USERNAME = :username');
		$stmt->bindTable('users', 'krs_users');
		$stmt->bindParam('username', $username, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		return $stmt->fetch();
	}

	public function getUsers($data = array()) {
		$db = UtilityDB::getDBfromTable('krs_users');

		$sql = 'SELECT * FROM t:users';

		// search text always as a parameter: it used to go into the query as it
		// was (and without quotes, so the query didn't even work)
		if (!empty($data['filter_autocomplete'])) {
			$sql .= " WHERE (FIRSTNAME LIKE :needle OR LASTNAME LIKE :needle OR USERNAME LIKE :needle)";
		}

		$sortable = array('USERNAME', 'ACTIVATION', 'REGISTR_DATA');
		$sql .= ' ORDER BY ' . ((isset($data['sort']) && in_array($data['sort'], $sortable)) ? $data['sort'] : 'USERNAME');
		$sql .= (isset($data['order']) && $data['order'] === 'DESC') ? ' DESC' : ' ASC';

		$stmt = $db->prepare($sql);
		$stmt->bindTable('users', 'krs_users');

		if (!empty($data['filter_autocomplete'])) {
			$needle = '%' . $data['filter_autocomplete'] . '%';
			$stmt->bindParam('needle', $needle, KPDO::TYPE_VARCHAR);
		}

		if (isset($data['start']) || isset($data['limit'])) {
			$start = max(0, (int) $data['start']);
			$limit = ($data['limit'] < 1) ? 20 : $data['limit'];

			$stmt->limit($start, $limit);
		}

		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function getTotalUsers() {
		$db = UtilityDB::getDBfromTable('krs_users');
		$stmt = $db->prepare('SELECT COUNT(*) AS total FROM t:users');
		$stmt->bindTable('users', 'krs_users');
		$stmt->execute();

		$row = $stmt->fetch();

		return $row['total'];
	}

	public function getUserGroupByUserId($userId) {
		$db = UtilityDB::getDBfromTable('krs_usergroups');
		$stmt = $db->prepare('SELECT g.* FROM t:usergroups, t:user_usergroup_map WHERE g.ID = ug.GROUP_ID AND ug.USER_ID = :user_id');
		$stmt->bindTable('usergroups', 'krs_usergroups', 'g');
		$stmt->bindTable('user_usergroup_map', 'krs_user_usergroup_map', 'ug');
		$stmt->bindParam('user_id', $userId, KPDO::TYPE_INTEGER);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function getModuleByUserId($userId) {
		$db = UtilityDB::getDBfromTable('krs_users_modules_map');
		$stmt = $db->prepare('SELECT m.ID, m.NAME FROM t:users_modules, t:modules WHERE um.USER_ID = :id AND um.MODULE_ID = m.ID');
		$stmt->bindTable('modules', 'krs_modules', 'm');
		$stmt->bindTable('users_modules', 'krs_users_modules_map', 'um');
		$stmt->bindParam('id', $userId, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		// Each user has at most one associated module (saveUserModule writes a
		// single row), so a single row is correct here.
		return $stmt->fetch();
	}
}
?>
