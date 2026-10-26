<?php
/**
 * Sessioni salvate su DB (tabella krs_session) invece che su file, per
 * poterle condividere tra pi web server. Implementa SessionHandlerInterface
 * (stile ad oggetti): la vecchia forma con array di callback e' deprecata
 * da PHP 8.
 */
class SessionDB implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
	/**
	 * a database connection resource
	 * @var KPDO
	 */
	private $_sess_db;

	public $data = array();

	public function __construct() {
		if (!session_id()) {
			ini_set('session.gc_maxlifetime', 2 * 60 * 60);
			ini_set('session.gc_probability', 100);

			session_set_save_handler($this);
			session_harden();

			session_start();
		}

		$this->data =& $_SESSION;
	}

	public function open(string $path, string $name): bool {
		try {
			if (!isset($this->_sess_db)) {
				$this->_sess_db = UtilityDB::getDBfromTable('krs_session');

				if (!$this->_sess_db) {
					return false;
				}
			}

			return true;
		} catch (KPDOException $e) {
			return false;
		}
	}

	// session_regenerate_id() chiude e riapre l'handler a meta' richiesta:
	// dimenticare la connessione chiusa fa si' che open() ne apra una nuova.
	public function close(): bool {
		try {
			$closed = (bool) $this->_sess_db->disconnect();
			$this->_sess_db = null;

			return $closed;
		} catch (KPDOException $e) {
			$this->_sess_db = null;

			return false;
		}
	}

	public function read(string $id): string|false {
		try {
			$stmt = $this->_sess_db->prepare('SELECT * FROM t:krs_session WHERE SESSION_ID = :id');
			$stmt->bindTable('krs_session', 'krs_session');
			$stmt->bindParam(':id', $id, KPDO::TYPE_VARCHAR);

			if ($stmt->execute()) {
				$record = $stmt->fetch(KPDO::FETCH_ASSOC);

				if ($record) {
					return $record['DATA'];
				}
			}

			return '';
		} catch (KPDOException $e) {
			return '';
		}
	}

	public function write(string $id, string $data): bool {
		try {
			$time = time();

			$stmt = $this->_sess_db->prepare('DELETE FROM t:krs_session WHERE SESSION_ID = :id');
			$stmt->bindTable('krs_session', 'krs_session');
			$stmt->bindParam('id', $id, KPDO::TYPE_VARCHAR);
			$stmt->execute();

			$stmt = $this->_sess_db->prepare('INSERT INTO t:krs_session (SESSION_ID, TIME, DATA) VALUES (:id, :time, :data)');
			$stmt->bindTable('krs_session', 'krs_session');
			$stmt->bindParam('id', $id, KPDO::TYPE_VARCHAR);
			$stmt->bindParam('time', $time, KPDO::TYPE_VARCHAR);
			$stmt->bindParam('data', $data, KPDO::TYPE_VARCHAR);
			$stmt->execute();

			return true;
		} catch (KPDOException $e) {
			return false;
		}
	}

	// Con session.use_strict_mode PHP chiede all'handler se l'ID esiste: un ID
	// mai emesso (es. imposto da un attaccante) viene scartato e ne nasce uno
	// nuovo. Senza questo metodo lo strict mode non avrebbe effetto sul DB.
	public function validateId(string $id): bool {
		try {
			$stmt = $this->_sess_db->prepare('SELECT SESSION_ID FROM t:krs_session WHERE SESSION_ID = :id');
			$stmt->bindTable('krs_session', 'krs_session');
			$stmt->bindParam(':id', $id, KPDO::TYPE_VARCHAR);

			return $stmt->execute() && (bool) $stmt->fetch(KPDO::FETCH_ASSOC);
		} catch (KPDOException $e) {
			return false;
		}
	}

	public function updateTimestamp(string $id, string $data): bool {
		return $this->write($id, $data);
	}

	public function destroy(string $id): bool {
		try {
			$stmt = $this->_sess_db->prepare('DELETE FROM t:krs_session WHERE SESSION_ID = :id');
			$stmt->bindTable('krs_session', 'krs_session');
			$stmt->bindParam(':id', $id, KPDO::TYPE_VARCHAR);
			$stmt->execute();

			return true;
		} catch (KPDOException $e) {
			return false;
		}
	}

	/**
	 * @see session.gc_divisor      100
	 * @see session.gc_maxlifetime 1440
	 * @see session.gc_probability    1
	 * @usage execution rate 1/100 (session.gc_probability/session.gc_divisor)
	 */
	public function gc(int $max_lifetime): int|false {
		try {
			$stmt = $this->_sess_db->prepare("DELETE FROM t:krs_session WHERE SESSION_ID != '' AND TIME < " . (time() - $max_lifetime));
			$stmt->bindTable('krs_session', 'krs_session');
			$stmt->execute();

			return $stmt->rowCount();
		} catch (KPDOException $e) {
			return false;
		}
	}
}
?>
