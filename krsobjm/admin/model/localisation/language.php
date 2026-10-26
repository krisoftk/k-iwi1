<?php
class ModelAdminLocalisationLanguage extends Model {
	public function getLanguages() {
		$db = UtilityDB::getDBfromTable('krs_languages');
		$stmt = $db->prepare('SELECT * FROM t:languages ORDER BY SORT_ORDER, NAME');
		$stmt->bindTable('languages', 'krs_languages');
		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function getLanguage($languageId) {
		$db = UtilityDB::getDBfromTable('krs_languages');
		$stmt = $db->prepare('SELECT * FROM t:languages WHERE ID = :id');
		$stmt->bindTable('languages', 'krs_languages');
		$stmt->bindParam('id', $languageId, KPDO::TYPE_INTEGER);
		$stmt->execute();

		return $stmt->fetch();
	}

	public function getLanguageBy($column, $value) {
		if (!in_array($column, array('CODE', 'DIRECTORY'))) {
			return false;
		}

		$db = UtilityDB::getDBfromTable('krs_languages');
		$stmt = $db->prepare('SELECT * FROM t:languages WHERE ' . $column . ' = :value');
		$stmt->bindTable('languages', 'krs_languages');
		$stmt->bindParam('value', $value, KPDO::TYPE_VARCHAR);
		$stmt->execute();

		return $stmt->fetch();
	}

	// FILENAME (name of each module's main file, e.g. admin/english.php)
	// always equals the folder: no need to ask for it
	public function addLanguage($data) {
		$db = UtilityDB::getDBfromTable('krs_languages');
		$image = '';

		$stmt = $db->prepare('INSERT INTO t:languages (NAME, CODE, LOCALE, IMAGE, DIRECTORY, FILENAME, SORT_ORDER, STATUS)
			VALUES (:name, :code, :locale, :image, :directory, :directory, :sort_order, :status)');
		$stmt->bindTable('languages', 'krs_languages');
		$stmt->bindParam('name', $data['name'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('code', $data['code'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('locale', $data['locale'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('image', $image, KPDO::TYPE_VARCHAR);
		$stmt->bindParam('directory', $data['directory'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('sort_order', $data['sort_order'], KPDO::TYPE_INTEGER);
		$stmt->bindParam('status', $data['status'], KPDO::TYPE_VARCHAR);
		$stmt->execute();

		return $db->lastInsertId();
	}

	// The folder doesn't change after creation (the files would stay in the
	// old one); the code does, and the users who had chosen it follow it.
	public function editLanguage($languageId, $data) {
		$old = $this->getLanguage($languageId);

		$db = UtilityDB::getDBfromTable('krs_languages');
		$stmt = $db->prepare('UPDATE t:languages SET NAME = :name, CODE = :code, LOCALE = :locale, SORT_ORDER = :sort_order, STATUS = :status WHERE ID = :id');
		$stmt->bindTable('languages', 'krs_languages');
		$stmt->bindParam('name', $data['name'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('code', $data['code'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('locale', $data['locale'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('sort_order', $data['sort_order'], KPDO::TYPE_INTEGER);
		$stmt->bindParam('status', $data['status'], KPDO::TYPE_VARCHAR);
		$stmt->bindParam('id', $languageId, KPDO::TYPE_INTEGER);
		$stmt->execute();

		if ($old && $old['CODE'] !== $data['code']) {
			$this->setUsersLanguage($old['CODE'], $data['code']);
		}
	}

	// The files on disk stay: they can be downloaded first or removed by hand.
	// The users who had chosen this language go back to the default one.
	public function deleteLanguage($languageId) {
		$language = $this->getLanguage($languageId);

		if (!$language) {
			return;
		}

		$db = UtilityDB::getDBfromTable('krs_languages');
		$stmt = $db->prepare('DELETE FROM t:languages WHERE ID = :id');
		$stmt->bindTable('languages', 'krs_languages');
		$stmt->bindParam('id', $languageId, KPDO::TYPE_INTEGER);
		$stmt->execute();

		$this->setUsersLanguage($language['CODE'], null);
	}

	private function setUsersLanguage($oldCode, $newCode) {
		$db = UtilityDB::getDBfromTable('krs_users');
		$stmt = $db->prepare('UPDATE t:users SET LANGUAGE = ' . ($newCode === null ? 'NULL' : ':new') . ' WHERE LANGUAGE = :old');
		$stmt->bindTable('users', 'krs_users');
		$stmt->bindParam('old', $oldCode, KPDO::TYPE_VARCHAR);

		if ($newCode !== null) {
			$stmt->bindParam('new', $newCode, KPDO::TYPE_VARCHAR);
		}

		$stmt->execute();
	}
}
?>
