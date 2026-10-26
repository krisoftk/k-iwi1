<?php 
class ControllerCommonScheduler extends Controller { 
	private $error = array();
	
	// Chiamato dal cron con l'API key di un utente di sistema:
	//   https://host/krsobjp/index.php?route=common/scheduler&api_key=<chiave>
	// La stessa chiave viene passata ai job, che sono route normali e quindi
	// passano dal controllo di login del bootstrap pubblico come qualunque
	// altra richiesta.
	public function index() {		
		if (!$this->user->isApiAuthenticated()) {
			http_response_code(401);
			$this->response->setOutput('Lo scheduler va chiamato con una API key valida.');
			return;
		}

		$this->load->model('common/scheduler');
		$this->check_scheduler();
	}
	
	// Lancia la richiesta al job e non aspetta la risposta (fire-and-forget),
	// cosi' lo scheduler passa subito al job successivo. Usa un socket PHP
	// puro invece di un binario esterno (curl/wget) per funzionare in modo
	// identico su Windows e Linux senza dipendenze di sistema.
	// La chiave viaggia nell'header X-API-KEY, non nell'URL, per non finire
	// nei log di accesso del web server; con setting.use_ssl attivo la
	// chiamata e' in https e il certificato viene verificato, a meno di
	// setting.scheduler_ssl_verify = "0" (certificati self-signed).
	private function execInBackground($route) {
		$base = $this->config->get('setting.use_ssl') ? HTTPS_PUBLIC : HTTP_PUBLIC;
		$parts = parse_url($base . 'index.php?route=' . $route);

		if (!isset($parts['host'])) {
			return false;
		}

		$scheme = isset($parts['scheme']) ? $parts['scheme'] : 'http';
		$port = isset($parts['port']) ? $parts['port'] : (($scheme === 'https') ? 443 : 80);
		$path = (isset($parts['path']) ? $parts['path'] : '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
		$remote = (($scheme === 'https') ? 'ssl://' : 'tcp://') . $parts['host'] . ':' . $port;

		$verify = !$this->config->has('setting.scheduler_ssl_verify') || $this->config->get('setting.scheduler_ssl_verify') != '0';
		$context = stream_context_create(array('ssl' => array('verify_peer' => $verify, 'verify_peer_name' => $verify, 'peer_name' => $parts['host'])));

		// con ssl:// il motivo di un fallimento (es. certificato rifiutato)
		// arriva come warning e non in $errstr: lo si cattura qui
		$reason = '';

		set_error_handler(function ($errno, $message) use (&$reason) {
			$reason .= ($reason ? ' | ' : '') . str_replace(array("\r", "\n"), ' ', $message);

			return true;
		});

		$socket = stream_socket_client($remote, $errno, $errstr, 2, STREAM_CLIENT_CONNECT, $context);

		restore_error_handler();

		if (!$socket) {
			$this->log->write('Scheduler: impossibile avviare il job ' . $route . ' su ' . $remote . ' (' . ($errstr ? $errstr : $reason) . ')');

			return false;
		}

		fwrite($socket, "GET {$path} HTTP/1.1\r\nHost: {$parts['host']}\r\nX-API-KEY: " . $this->user->getApiKey() . "\r\nConnection: Close\r\n\r\n");

		// Prima di chiudere si aspetta il primo byte di risposta (al massimo
		// un secondo): in TLS, chiudendo subito con dati del server non ancora
		// letti, la connessione viene resettata e il web server scarta la
		// richiesta prima di eseguirla. Un job che dura di piu' continua
		// comunque dopo la chiusura.
		stream_set_timeout($socket, 1);
		fread($socket, 1);
		fclose($socket);

		return true;
	}

	private function check_scheduler() {
		$jobs=$this->model_common_scheduler->get_jobs();
		foreach ($jobs as $job) {		
			// LAST_RUN solo se il job e' partito davvero: altrimenti un job
			// non avviato (server irraggiungibile, certificato rifiutato)
			// verrebbe segnato come eseguito e salterebbe l'intera giornata
			if ($this->model_common_scheduler->check_job($job) && $this->execInBackground($job["CONTROLLER"])) {
				$this->model_common_scheduler->set_job_last_run_date($job["ID"]);
			}
		}
	}
}
?>
