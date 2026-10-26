<?php
class ModelAdminToolImportTables extends Model {
	public function addDatabase($data) {
		$db = UtilityDB::getDBfromTable('krs_databases');

		$stmt = $db->prepare('INSERT INTO t:table (TYPE, NAME, IP, PORT, USERNAME, PASSWORD, MODULE_ID)
			VALUES (:type, :name, :ip, :port, :username, :password, :module_id)');
		$stmt->bindTable('table', 'krs_databases');
		$stmt->bindParam('type', $data['type'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('name', $data['name'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('ip', $data['ip'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('port', $data['port'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('username', $data['username'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('password', $data['password'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('module_id', $data['module_id'], KPDO::TYPE_INTEGER);
		$stmt->execute();
	}

	public function addTable($data) {
		$db = UtilityDB::getDBfromTable('krs_tables');

		$stmt = $db->prepare('INSERT INTO t:table (NAME, DATABASE_ID, MODULE_ID) VALUES (:name, :database_id, :module_id)');
		$stmt->bindTable('table', 'krs_tables');
		$stmt->bindParam('name', $data['name'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('database_id', $data['database_id'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('module_id', $data['module_id'], KPDO::TYPE_VARCHAR);
		$stmt->execute();
	}
}
?>
