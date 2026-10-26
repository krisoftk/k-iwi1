<?php
require_once 'session_db.php';
require_once 'session_cookie.php';

// Impostazioni comuni a SessionCookie e SessionDB, da chiamare prima di
// session_start(): PHP rifiuta gli ID di sessione che non ha emesso lui
// (session fixation) e il cookie non e' leggibile da JavaScript, non viaggia
// in http in chiaro sotto https e non parte con le richieste di altri siti
// (Lax lascia funzionare i normali link in arrivo da fuori).
function session_harden() {
	ini_set('session.use_strict_mode', '1');
	ini_set('session.use_only_cookies', '1');

	session_set_cookie_params(array(
		'lifetime' => 0,
		'path' => '/',
		'secure' => !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off',
		'httponly' => true,
		'samesite' => 'Lax',
	));
}
?>