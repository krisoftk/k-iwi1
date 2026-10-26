<?php
/**
 * Base for the controllers of machine-to-machine APIs (no session,
 * authentication with an API key). The key is already verified in the
 * public bootstrap, which authenticates the owner user for the current
 * request only: here we check that the request really came with a key and
 * not with a browser's session cookie, otherwise an external site could
 * call the API on behalf of the logged-in user (CSRF).
 *
 * Key: X-API-KEY header (recommended), or the 'api_key' POST/GET field.
 * Standard JSON response:
 *   { "result": bool, "stato": "ok|warning|duplicato|errore|auth", "id": int, "msg": string }
 *
 * Typical use in a module:
 *
 *   class ControllerMymoduleApiOrders extends ApiController {
 *       public function insert() {
 *           if (!$this->authorize('POST')) {
 *               return;
 *           }
 *
 *           $this->load->model('mymodule/orders');
 *           $this->respond($this->model_mymodule_orders->insert($this->user->getId(), $this->request->post));
 *       }
 *   }
 */
abstract class ApiController extends Controller {

	// Checks the HTTP method and the API key; if something is wrong it has
	// already written the error response and the caller only has to return.
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
