<?php
class ControllerCommonKReport extends Controller {
	
	
	public function index() {
		$this->load->language('common/kreport');
		
		$prog_id = isset($this->request->get['prog']) ? $this->request->get['prog'] : '';
		$name = isset($this->request->get['report']) ? $this->request->get['report'] : '';

		if ($this->user->isLogged() && ctype_digit((string) $prog_id) && $this->user->hasPermission($prog_id)) {
			$stmt = $this->db->prepare("SELECT * FROM t:reports WHERE PROG_ID = :prog_id AND NAME = :name");
			$stmt->bindTable("reports", "krs_rpt_report");
			$stmt->bindParam("prog_id", $prog_id, KPDO::TYPE_INTEGER);
			$stmt->bindParam("name", $name, KPDO::TYPE_VARCHAR);
			$stmt->execute();
			
			$report = $stmt->fetch();

			if ($report) {
				$token = $this->createToken($report['ID']);
				$this->redirect($this->config->get('report.base_url') . $report['URL'] . '&token=' . $token);
			} else {
				echo $this->language->get('error_report');
			}
		} else {
			echo $this->language->get('error_permission');
		}
	}
	
	private function createToken($report_id) {
		$current_time = time();
		
		$this->deleteTokenExpire(date('Y-m-d H:i:s', $current_time));
		
		$expire = date('Y-m-d H:i:s', $current_time + $this->config->get('report.time_expire'));		
		// casuale e imprevedibile: chi ha il token apre il report
		$token = bin2hex(random_bytes(16));
		$ip = $_SERVER['REMOTE_ADDR'];
	
		$stmt = $this->db->prepare("INSERT INTO t:token
		(TOKEN, REPORT_ID, IP, EXPIRE)
		VALUES
		(:token, :report_id, :ip, :expire)");
		$stmt->bindTable("token", "krs_rpt_token");
		$stmt->bindParam("token", $token, KPDO::TYPE_VARCHAR);
		$stmt->bindParam("report_id", $report_id, KPDO::TYPE_INTEGER);
		$stmt->bindParam("ip", $ip, KPDO::TYPE_VARCHAR);
		$stmt->bindParam("expire", $expire, KPDO::TYPE_TIMESTAMP);
		$stmt->execute();
	
		return $token;
	}
	
	private function deleteTokenExpire($current_time) {
		$stmt = $this->db->prepare("DELETE FROM t:token WHERE EXPIRE < :time");
		$stmt->bindTable("token", "krs_rpt_token");
		$stmt->bindParam("time", $current_time, KPDO::TYPE_TIMESTAMP);
		$stmt->execute();
	}
}
?>