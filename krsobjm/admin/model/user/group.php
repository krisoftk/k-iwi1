<?php
class ModelAdminUserGroup extends Model {
	public function addUserGroup($data) {
		$db = UtilityDB::getDBfromTable('krs_usergroups');

		$stmt = $db->prepare('INSERT INTO t:usergroups (PARENT_ID, NAME) VALUES (0, :name)');
		$stmt->bindTable('usergroups', 'krs_usergroups');
		$stmt->bindParam('name', $data['name'], KPDO::TYPE_VARCHAR);
		$stmt->execute();

		$groupId = $db->lastInsertId();

		$this->saveProgs($db, $groupId, $data['progs']);
		$this->saveModule($db, $groupId, $data['module']);

		return $groupId;
	}

	public function editUserGroup($groupId, $data) {
		$db = UtilityDB::getDBfromTable('krs_usergroups');

		$stmt = $db->prepare('UPDATE t:usergroups SET NAME = :name WHERE ID = :id');
		$stmt->bindTable('usergroups', 'krs_usergroups');
		$stmt->bindParam('name', $data['name'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('id', $groupId, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		$this->saveProgs($db, $groupId, $data['progs']);
		$this->saveModule($db, $groupId, $data['module']);
	}

	private function saveProgs($db, $groupId, $progs) {
		$stmt = $db->prepare('DELETE FROM t:progs_usergroups_map WHERE GROUP_ID = :group_id');
		$stmt->bindTable('progs_usergroups_map', 'krs_progs_usergroups_map');
		$stmt->bindParam('group_id', $groupId, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		$progId = '';
		$stmt = $db->prepare('INSERT INTO t:progs_usergroups_map
			(PROG_ID, GROUP_ID, READ_ENABLED, WRITE_ENABLED, EXECUTION_ENABLED)
			VALUES (:prog_id, :group_id, 1, 1, 1)');
		$stmt->bindTable('progs_usergroups_map', 'krs_progs_usergroups_map');
		$stmt->bindParam('group_id', $groupId, KPDO::TYPE_INTEGER);
		$stmt->bindParam('prog_id', $progId, KPDO::TYPE_INTEGER);

		foreach ($progs as $progId) {
			$stmt->execute();
		}
	}

	private function saveModule($db, $groupId, $moduleId) {
		$stmt = $db->prepare('DELETE FROM t:usergroups_modules_map WHERE USERGROUP_ID = :group_id');
		$stmt->bindTable('usergroups_modules_map', 'krs_usergroups_modules_map');
		$stmt->bindParam('group_id', $groupId, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		$stmt = $db->prepare('INSERT INTO t:usergroups_modules_map (MODULE_ID, USERGROUP_ID) VALUES (:module_id, :group_id)');
		$stmt->bindTable('usergroups_modules_map', 'krs_usergroups_modules_map');
		$stmt->bindParam('group_id', $groupId, KPDO::TYPE_INTEGER);
		$stmt->bindParam('module_id', $moduleId, KPDO::TYPE_INTEGER);
		$stmt->execute();
	}

	public function deleteUserGroup($groupId) {
		$db = UtilityDB::getDBfromTable('krs_usergroups');
		$stmt = $db->prepare('DELETE FROM t:usergroups WHERE ID = :id');
		$stmt->bindTable('usergroups', 'krs_usergroups');
		$stmt->bindParam('id', $groupId, KPDO::TYPE_VARCHAR);
		$stmt->execute();
	}

	public function getUserGroup($id) {
		$db = UtilityDB::getDBfromTable('krs_usergroups');
		$stmt = $db->prepare('SELECT * FROM t:usergroups WHERE ID = :id');
		$stmt->bindTable('usergroups', 'krs_usergroups');
		$stmt->bindParam('id', $id, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		return $stmt->fetch();
	}

	public function getUserGroups($data = array()) {
		$db = UtilityDB::getDBfromTable('krs_usergroups');

		$sql = 'SELECT g.*, m.NAME AS MODULE_NAME FROM t:groups, t:usergroups_modules_map, t:modules
			WHERE g.ID = um.USERGROUP_ID AND um.MODULE_ID = m.ID';

		$sortable = array('NAME', 'MODULE_NAME');
		$sql .= ' ORDER BY ' . ((isset($data['sort']) && in_array($data['sort'], $sortable)) ? $data['sort'] : 'MODULE_NAME');
		$sql .= (isset($data['order']) && $data['order'] === 'DESC') ? ' DESC' : ' ASC';

		$stmt = $db->prepare($sql);
		$stmt->bindTable('groups', 'krs_usergroups', 'g');
		$stmt->bindTable('usergroups_modules_map', 'krs_usergroups_modules_map', 'um');
		$stmt->bindTable('modules', 'krs_modules', 'm');

		if (isset($data['start']) || isset($data['limit'])) {
			$start = max(0, (int) $data['start']);
			$limit = ($data['limit'] < 1) ? 20 : $data['limit'];

			$stmt->limit($start, $limit);
		}

		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function getTotalUserGroups() {
		$db = UtilityDB::getDBfromTable('krs_usergroups');
		$stmt = $db->prepare('SELECT COUNT(*) AS total FROM t:groups');
		$stmt->bindTable('groups', 'krs_usergroups');
		$stmt->execute();

		$row = $stmt->fetch();

		return $row['total'];
	}

	public function getProgByGroupId($id) {
		$db = UtilityDB::getDBfromTable('krs_progs');
		$stmt = $db->prepare('SELECT p.* FROM t:progs, t:progs_usergroups_map WHERE p.ID = pg.PROG_ID AND pg.GROUP_ID = :group_id');
		$stmt->bindTable('progs', 'krs_progs', 'p');
		$stmt->bindTable('progs_usergroups_map', 'krs_progs_usergroups_map', 'pg');
		$stmt->bindParam('group_id', $id, KPDO::TYPE_INTEGER);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function getModuleByGroupId($id) {
		$db = UtilityDB::getDBfromTable('krs_usergroups_modules_map');
		$stmt = $db->prepare('SELECT m.ID, m.NAME FROM t:usergroups_modules_map, t:modules WHERE um.USERGROUP_ID = :id AND um.MODULE_ID = m.ID');
		$stmt->bindTable('usergroups_modules_map', 'krs_usergroups_modules_map', 'um');
		$stmt->bindTable('modules', 'krs_modules', 'm');
		$stmt->bindParam('id', $id, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		return $stmt->fetch();
	}

	public function getTotalUsersByGroupId($id) {
		$db = UtilityDB::getDBfromTable('krs_user_usergroup_map');
		$stmt = $db->prepare('SELECT COUNT(*) AS total FROM t:user_usergroup_map WHERE GROUP_ID = :id');
		$stmt->bindTable('user_usergroup_map', 'krs_user_usergroup_map');
		$stmt->bindParam('id', $id, KPDO::TYPE_INTEGER);
		$stmt->execute();

		$row = $stmt->fetch();

		return $row['total'];
	}
}
?>
