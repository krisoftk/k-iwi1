<?php
/**
 * Wrapper esposto ai controller come $this->user: tiene i dati dell'utente
 * loggato in sessione e delega ad Auth le verifiche vere e proprie
 * (credenziali, permessi) senza duplicarne la logica.
 */
class User {
	private $session;
	private $auth;
	private $details;
	private $apiKey = null;
	private $apiAuthenticated = false;

	public function __construct($registry) {
		$this->session = $registry->get('session');
		$this->auth = $registry->get('auth');

		if (isset($this->session->data['user_id'])) {
			$this->details = $this->auth->auth_get_details($this->session->data['user_id']);
		}
	}

	// Con $module il login riesce solo se l'utente puo' usare quel modulo
	// (es. 'admin'): chi non puo' non arriva nemmeno a toccare la sessione.
	public function login($username, $password, $module = null) {
		$user = $this->auth->auth_check_user_login($username, $password);

		if (!$user) {
			return false;
		}

		if ($module !== null && !$this->auth->auth_check_module_access($user, $module)) {
			return false;
		}

		// nuovo ID di sessione a ogni login: un ID noto prima (fissato da
		// altri o letto in chiaro) non diventa mai una sessione autenticata
		session_regenerate_id(true);

		$this->session->data['user_id'] = $user['ID'];
		$this->details = $user;

		return true;
	}

	// Autentica la sola richiesta corrente tramite API key, senza scrivere
	// nulla in sessione. Se la richiesta presenta una chiave, decide la
	// chiave: una chiave non valida annulla anche un eventuale utente di
	// sessione, cosi' chi la manda riceve sempre un esito coerente.
	public function loginByApiKey($key) {
		$this->apiKey = (string) $key;
		$this->apiAuthenticated = false;

		$user = $this->auth->auth_check_api_key($this->apiKey);

		if (!$user) {
			$this->details = null;

			return false;
		}

		$this->details = $user;
		$this->apiAuthenticated = true;

		return true;
	}

	// login rifiutato per i troppi tentativi falliti (vedi Auth)
	public function isLoginBlocked() {
		return $this->auth->auth_login_blocked();
	}

	public function getLoginLockoutMinutes() {
		return $this->auth->login_lockout_minutes();
	}

	public function hasApiKey() {
		return $this->apiKey !== null;
	}

	public function isApiAuthenticated() {
		return $this->apiAuthenticated;
	}

	public function getApiKey() {
		return $this->apiKey;
	}

	public function logout() {
		$this->details = null;

		session_unset();
		session_destroy();
	}

	public function isLogged() {
		return $this->details;
	}

	public function getDetails() {
		return $this->details;
	}

	public function getId() {
		return $this->details['ID'];
	}

	public function getUsername() {
		return $this->details['USERNAME'];
	}

	// codice della lingua scelta (krs_users.LANGUAGE), '' se non scelta:
	// vale allora setting.language di config.ini
	public function getLanguage() {
		return isset($this->details['LANGUAGE']) ? (string) $this->details['LANGUAGE'] : '';
	}

	public function getGroups() {
		return $this->details['GROUPS'];
	}

	public function hasGroup($groupId) {
		if (empty($this->details['GROUPS'])) {
			return false;
		}

		foreach ($this->details['GROUPS'] as $group) {
			if ($group['ID'] == $groupId) {
				return true;
			}
		}

		return false;
	}

	public function canAccessModule($module) {
		return $this->details && $this->auth->auth_check_module_access($this->details, $module);
	}

	public function hasPermission($progId, $mode = 0) {
		return $this->auth->auth_check_permission($this->details, $progId, $mode);
	}
}
?>
