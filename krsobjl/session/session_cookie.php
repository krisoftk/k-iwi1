<?php
class SessionCookie {
	public $data = array();
			
  	public function __construct($time = 900) {		
		if (!session_id()) {
			ini_set('session.use_cookies', 'On');
			ini_set('session.use_trans_sid', 'Off');
			ini_set('session.save_path', DIR_SESSIONS);
			ini_set('session.cookie_lifetime', $time * 2);
			ini_set('session.gc_maxlifetime', $time * 2);
			
			session_harden();
			session_start();
		}
	
		$this->data =& $_SESSION;
	}
	
	public function getId() {
		return session_id();
	}
	
	public function destroy() {
		return session_destroy();
	}
}
?>