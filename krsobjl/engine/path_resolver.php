<?php
/**
 * Risolve una route testuale (es. "admin/user/user") in un percorso fisico
 * sotto DIR_MODULE, camminando i segmenti uno alla volta e verificando sul
 * filesystem se corrispondono a una sottocartella o al file finale.
 *
 * E' l'unico punto che sa come tradurre una route in file+classe: sia i
 * controller (Action) sia i model (Loader::model) lo usano, passando solo
 * la sottocartella di partenza ('controller' o 'model').
 */
final class PathResolver {
	public static function resolve($route, $kind) {
		// Segmenti vuoti o "." ("clienti//clienti", "clienti/./clienti")
		// verrebbero presi per sottocartelle e darebbero un percorso diverso per
		// lo stesso controller: si scartano, cosi' ogni variante risolve allo
		// stesso file (conta per i confronti dei controlli di accesso).
		$segments = array_values(array_filter(explode('/', str_replace('..', '', (string) $route)), function ($segment) {
			return $segment !== '' && $segment !== '.';
		}));
		$area     = array_shift($segments);

		$path         = $area . '/' . $kind;
		$classSegments = array($area);

		while (!empty($segments)) {
			$candidate = $path . '/' . $segments[0];

			if (is_dir(DIR_MODULE . $candidate)) {
				$classSegments[] = array_shift($segments);
				$path = $candidate;
				continue;
			}

			if (is_file(DIR_MODULE . $candidate . '.php')) {
				$classSegments[] = array_shift($segments);
				$path = $candidate;
				break;
			}

			break;
		}

		$class = '';

		foreach ($classSegments as $segment) {
			$class .= ucfirst(preg_replace('/[^a-zA-Z0-9]/', '', $segment));
		}

		return array(
			'file'      => DIR_MODULE . $path . '.php',
			'class'     => $class,
			'remainder' => $segments,
		);
	}
}
?>
