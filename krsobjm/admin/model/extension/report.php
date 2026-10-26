<?php
class ModelAdminExtensionReport extends Model {
	public function addReport($data) {
		$db = UtilityDB::getDBfromTable('krs_rpt_report');

		try {
			$stmt = $db->prepare('INSERT INTO t:reports (PROG_ID, NAME, DESCRIPTION, URL) VALUES (:prog_id, :name, :description, :url)');
			$stmt->bindTable('reports', 'krs_rpt_report');
			$stmt->bindParam('prog_id', $data['prog_id'], KPDO::TYPE_INTEGER);
			$stmt->bindParam('name', $data['name'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('description', $data['description'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('url', $data['url'], KPDO::TYPE_VARCHAR);
			$stmt->execute();

			return $db->lastInsertId();
		} catch (KPDOException $e) {
			$this->log->write($e);

			return false;
		}
	}

	public function editReport($reportId, $data) {
		$db = UtilityDB::getDBfromTable('krs_rpt_report');

		try {
			$stmt = $db->prepare('UPDATE t:reports SET PROG_ID = :prog_id, NAME = :name, DESCRIPTION = :description, URL = :url WHERE ID = :id');
			$stmt->bindTable('reports', 'krs_rpt_report');
			$stmt->bindParam('prog_id', $data['prog_id'], KPDO::TYPE_INTEGER);
			$stmt->bindParam('name', $data['name'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('description', $data['description'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('url', $data['url'], KPDO::TYPE_VARCHAR);
			$stmt->bindParam('id', $reportId, KPDO::TYPE_INTEGER);
			$stmt->execute();

			return true;
		} catch (KPDOException $e) {
			$this->log->write($e);

			return false;
		}
	}

	public function deleteReport($reportId) {
		$db = UtilityDB::getDBfromTable('krs_rpt_report');
		$stmt = $db->prepare('DELETE FROM t:reports WHERE ID = :id');
		$stmt->bindTable('reports', 'krs_rpt_report');
		$stmt->bindParam('id', $reportId, KPDO::TYPE_INTEGER);
		$stmt->execute();
	}

	public function getReport($reportId) {
		$db = UtilityDB::getDBfromTable('krs_rpt_report');
		$stmt = $db->prepare('SELECT * FROM t:reports WHERE ID = :id');
		$stmt->bindTable('reports', 'krs_rpt_report');
		$stmt->bindParam('id', $reportId, KPDO::TYPE_INTEGER);
		$stmt->execute();

		return $stmt->fetch();
	}

	public function getReports($data = array()) {
		$db = UtilityDB::getDBfromTable('krs_rpt_report');

		$sql = 'SELECT * FROM t:reports';

		$sortable = array('PROG_ID', 'NAME');
		$sql .= ' ORDER BY ' . ((isset($data['sort']) && in_array($data['sort'], $sortable)) ? $data['sort'] : 'NAME');
		$sql .= (isset($data['order']) && $data['order'] === 'DESC') ? ' DESC' : ' ASC';

		$stmt = $db->prepare($sql);
		$stmt->bindTable('reports', 'krs_rpt_report');

		if (isset($data['start']) || isset($data['limit'])) {
			$start = max(0, (int) $data['start']);
			$limit = ($data['limit'] < 1) ? 20 : $data['limit'];

			$stmt->limit($start, $limit);
		}

		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function getTotalReports() {
		$db = UtilityDB::getDBfromTable('krs_rpt_report');
		$stmt = $db->prepare('SELECT COUNT(*) AS total FROM t:reports');
		$stmt->bindTable('reports', 'krs_rpt_report');
		$stmt->execute();

		$row = $stmt->fetch();

		return $row['total'];
	}
}
?>
