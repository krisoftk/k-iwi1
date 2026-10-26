<?php 
class ControllerCommonScheduler extends Controller { 
	private $error = array();
	
	// Called by cron with the API key of a system user:
	//   https://host/krsobjp/index.php?route=common/scheduler&api_key=<key>
	// The same key is passed to the jobs, which are normal routes and so go
	// through the login check of the public bootstrap like any other request.
	public function index() {		
		if (!$this->user->isApiAuthenticated()) {
			http_response_code(401);
			$this->response->setOutput('Lo scheduler va chiamato con una API key valida.');
			return;
		}

		$this->load->model('common/scheduler');
		$this->check_scheduler();
	}
	
	// Sends the request to the job and doesn't wait for the response
	// (fire-and-forget), so the scheduler moves straight to the next job. It
	// uses a plain PHP socket instead of an external binary (curl/wget) to
	// work the same way on Windows and Linux without system dependencies.
	// The key travels in the X-API-KEY header, not in the URL, so it doesn't
	// end up in the web server's access logs; with setting.use_ssl on, the
	// call is over https and the certificate is verified, unless
	// setting.scheduler_ssl_verify = "0" (self-signed certificates).
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

		// with ssl:// the reason of a failure (e.g. a rejected certificate)
		// arrives as a warning and not in $errstr: it's caught here
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

		// Before closing, wait for the first byte of the response (one second
		// at most): with TLS, closing right away with server data not yet
		// read resets the connection and the web server drops the request
		// before running it. A job that takes longer carries on after the
		// close anyway.
		stream_set_timeout($socket, 1);
		fread($socket, 1);
		fclose($socket);

		return true;
	}

	private function check_scheduler() {
		$jobs=$this->model_common_scheduler->get_jobs();
		foreach ($jobs as $job) {		
			// LAST_RUN only if the job really started: otherwise a job that
			// didn't start (server unreachable, certificate rejected) would be
			// marked as run and would skip the whole day
			if ($this->model_common_scheduler->check_job($job) && $this->execInBackground($job["CONTROLLER"])) {
				$this->model_common_scheduler->set_job_last_run_date($job["ID"]);
			}
		}
	}
}
?>
