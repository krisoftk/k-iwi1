<?php
class ModelAdminExtensionProg extends Model {
	public function getProgs($data = array()) {
		$db = UtilityDB::getDBfromTable('krs_progs');

		// MENU_TITLE = text of the menu item through which the program is
		// actually reachable (krs_menuitems); a prog without a menu item of
		// its own (e.g. the ".../insert" actions, reachable only from a
		// button inside another screen) has it NULL.
		$sql = 'SELECT p.*, GROUP_CONCAT(DISTINCT mi.TITLE SEPARATOR \', \') AS MENU_TITLE
			FROM t:progs
			LEFT JOIN t:progs_menuitems_map ON (pm.PROG_ID = p.ID)
			LEFT JOIN t:menuitems ON (mi.ID = pm.MENUITEMS_ID)
			GROUP BY p.ID
			ORDER BY p.TITLE';

		$sql .= (isset($data['order']) && $data['order'] === 'DESC') ? ' DESC' : ' ASC';

		$stmt = $db->prepare($sql);
		$stmt->bindTable('progs', 'krs_progs', 'p');
		$stmt->bindTable('progs_menuitems_map', 'krs_progs_menuitems_map', 'pm');
		$stmt->bindTable('menuitems', 'krs_menuitems', 'mi');

		if (isset($data['start']) || isset($data['limit'])) {
			$start = max(0, (int) $data['start']);
			$limit = ($data['limit'] < 1) ? 20 : $data['limit'];

			$stmt->limit($start, $limit);
		}

		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function getTotalProgs() {
		$db = UtilityDB::getDBfromTable('krs_progs');
		$stmt = $db->prepare('SELECT COUNT(*) AS total FROM t:progs');
		$stmt->bindTable('progs', 'krs_progs');
		$stmt->execute();

		$row = $stmt->fetch();

		return $row['total'];
	}
}
?>
