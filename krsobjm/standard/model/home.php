<?php
class ModelStandardHome extends Model {

	// Cambio password self-service per l'utente loggato. La verifica della
	// password attuale e' fatta a monte nel controller (auth_check_user_login,
	// stessa logica del login); qui si salva solo il nuovo hash (Auth::hash_password).
	public function changePassword($user_id, $password) {
		$db = UtilityDB::getDBfromTable('krs_users');

		$salt = '';
		$password_hash = Auth::hash_password($password);

		$stmt = $db->prepare('UPDATE t:users
			SET
				SALT = :salt,
				PASSWORD = :password
			WHERE
				ID = :id');
		$stmt->bindTable("users", "krs_users");
		$stmt->bindParam("salt", $salt, KPDO::TYPE_VARCHAR);
		$stmt->bindParam("password", $password_hash, KPDO::TYPE_VARCHAR);
		$stmt->bindParam("id", $user_id, KPDO::TYPE_VARCHAR);

		$stmt->execute();
	}
}
?>
