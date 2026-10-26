<?php
class ModelAdminSettingExtension extends Model {
	public function getInstalled() {
		$db = UtilityDB::getDBfromTable('krs_modules');
		$stmt = $db->prepare('SELECT * FROM t:krs_modules');
		$stmt->bindTable('krs_modules', 'krs_modules');
		$stmt->execute();

		$names = array();

		foreach ($stmt->fetchAll() as $result) {
			$names[] = $result['NAME'];
		}

		return $names;
	}
}
?>
