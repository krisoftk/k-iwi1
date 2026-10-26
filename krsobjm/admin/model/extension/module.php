<?php
class ModelAdminExtensionModule extends Model {
	public function getModules() {
		$db = UtilityDB::getDBfromTable('krs_modules');
		$stmt = $db->prepare('SELECT * FROM t:modules WHERE 1');
		$stmt->bindTable('modules', 'krs_modules');
		$stmt->execute();

		return $stmt->fetchAll();
	}
}
?>
