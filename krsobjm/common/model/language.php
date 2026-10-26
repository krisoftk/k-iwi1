<?php
class ModelCommonLanguage extends Model {

	public function getLanguage($language_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM krs_languages WHERE ID = '" . (int)$language_id . "'");

		$result = $query->fetch();
		
		$language_data = array();
		
		if ($result) {
			$language_data = array(
				'id'          => $result['ID'],
				'name'        => $result['NAME'],
				'code'        => $result['CODE'],
				'locale'      => $result['LOCALE'],
				'image'       => $result['IMAGE'],
				'directory'   => $result['DIRECTORY'],
				'filename'    => $result['FILENAME'],
				'sort_order'  => $result['SORT_ORDER'],
				'status'      => $result['STATUS']
			);
		}
		
		return $language_data;
	}

	public function getLanguages($data = array()) {
		if ($data) {
			$sql = "SELECT * FROM krs_languages";

			$sort_data = array(
				'name',
				'code',
				'sort_order'
			);

			if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
				$sql .= " ORDER BY " . $data['sort'];
			} else {
				$sql .= " ORDER BY SORT_ORDER, NAME";
			}

			if (isset($data['order']) && ($data['order'] == 'DESC')) {
				$sql .= " DESC";
			} else {
				$sql .= " ASC";
			}

			if (isset($data['start']) || isset($data['limit'])) {
				if ($data['start'] < 0) {
					$data['start'] = 0;
				}

				if ($data['limit'] < 1) {
					$data['limit'] = 20;
				}

				$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
			}
		} else {
			
			$sql = "SELECT * FROM krs_languages ORDER BY SORT_ORDER, NAME";
			
		}
		
		$query = $this->db->query($sql);

		$language_data = array();

		foreach ($query->fetchAll() as $result) {
			$language_data[$result['CODE']] = array(
				'id'          => $result['ID'],
				'name'        => $result['NAME'],
				'code'        => $result['CODE'],
				'locale'      => $result['LOCALE'],
				'image'       => $result['IMAGE'],
				'directory'   => $result['DIRECTORY'],
				'filename'    => $result['FILENAME'],
				'sort_order'  => $result['SORT_ORDER'],
				'status'      => $result['STATUS']
			);
		}

		return $language_data;
	}

	public function getTotalLanguages() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM krs_languages");

		$result = $query->fetch();
		return $result['total'];
	}
}