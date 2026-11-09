<?php
/**
 * Resolves a textual route (e.g. "admin/user/user") into a physical path
 * under DIR_MODULE, walking the segments one at a time and checking on the
 * filesystem whether they match a subfolder or the final file.
 *
 * It's the only place that knows how to turn a route into file+class: both
 * controllers (Action) and models (Loader::model) use it, passing only the
 * starting subfolder ('controller' or 'model').
 */
final class PathResolver {
	// The area of a route (its first folder under krsobjm: admin, standard,
	// install, a module...), normalised as resolve() does and in lower case
	// (on case-insensitive filesystems "Admin" opens the admin folder too).
	// The entry points use it to decide which areas they may run.
	public static function area($route) {
		foreach (explode('/', str_replace('..', '', (string) $route)) as $segment) {
			if ($segment !== '' && $segment !== '.') {
				return strtolower($segment);
			}
		}

		return '';
	}

	public static function resolve($route, $kind) {
		// Empty or "." segments ("customers//customers", "customers/./customers")
		// would be taken for subfolders and give a different path for the
		// same controller: they are dropped, so every variant resolves to the
		// same file (it matters for the access-control comparisons).
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
