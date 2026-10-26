<?php
/**
 * Wrapper exposed to controllers as $this->user: it keeps the data of the
 * user logged in the session and delegates the actual checks to Auth
 * (credentials, permissions) without duplicating its logic.
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

	// With $module the login succeeds only if the user can use that module
	// (e.g. 'admin'): whoever can't never even touches the session.
	public function login($username, $password, $module = null) {
		$user = $this->auth->auth_check_user_login($username, $password);

		if (!$user) {
			return false;
		}

		if ($module !== null && !$this->auth->auth_check_module_access($user, $module)) {
			return false;
		}

		// new session ID at every login: an ID known beforehand (set by
		// someone else or read in clear) never becomes an authenticated session
		session_regenerate_id(true);

		$this->session->data['user_id'] = $user['ID'];
		$this->details = $user;

		return true;
	}

	// Authenticates the current request only, through an API key, without
	// writing anything to the session. If the request carries a key, the key
	// decides: an invalid key also cancels any session user, so whoever sends
	// it always gets a consistent result.
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

	// login refused because of too many failed attempts (see Auth)
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

	// code of the chosen language (krs_users.LANGUAGE), '' if none was chosen:
	// setting.language from config.ini applies then
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
