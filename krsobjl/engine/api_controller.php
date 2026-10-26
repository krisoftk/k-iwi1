<?php
/**
 * Base per i controller delle API machine-to-machine (nessuna sessione,
 * autenticazione con API key). La chiave viene gia' verificata nel
 * bootstrap pubblico, che autentica l'utente proprietario solo per la
 * richiesta corrente: qui si controlla che la richiesta sia arrivata
 * davvero con una chiave e non con il cookie di sessione di un browser,
 * altrimenti un sito esterno potrebbe chiamare l'API a nome dell'utente
 * loggato (CSRF).
 *
 * Chiave: header X-API-KEY (consigliato), oppure campo POST/GET 'api_key'.
 * Risposta JSON standard:
 *   { "result": bool, "stato": "ok|warning|duplicato|errore|auth", "id": int, "msg": string }
 *
 * Uso tipico in un modulo:
 *
 *   class ControllerMiomoduloApiOrdini extends ApiController {
 *       public function insert() {
 *           if (!$this->authorize('POST')) {
 *               return;
 *           }
 *
 *           $this->load->model('miomodulo/ordini');
 *           $this->respond($this->model_miomodulo_ordini->insert($this->user->getId(), $this->request->post));
 *       }
 *   }
 */
abstract class ApiController extends Controller {

	// Verifica metodo HTTP e API key; se qualcosa non va ha gia' scritto la
	// risposta d'errore e il chiamante deve solo fare return.
	protected function authorize($method = 'POST') {
		$current = isset($this->request->server['REQUEST_METHOD']) ? $this->request->server['REQUEST_METHOD'] : '';

		if ($method && strtoupper($current) != strtoupper($method)) {
			$this->fail('errore', 'Metodo non consentito: usare ' . strtoupper($method) . '.', 405);

			return false;
		}

		if (!$this->user->isApiAuthenticated()) {
			$this->fail('auth', 'API key non valida o non attiva.', 401);

			return false;
		}

		return true;
	}

	protected function fail($stato, $msg, $status = 400) {
		$this->respond(array('result' => false, 'stato' => $stato, 'id' => 0, 'msg' => $msg), $status);
	}

	protected function respond($json, $status = 200) {
		$json = array_merge(array('result' => false, 'stato' => 'errore', 'id' => 0, 'msg' => ''), $json);

		http_response_code($status);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
?>
