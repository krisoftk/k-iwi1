<?php  
class ModelStandardMenu extends Model {
	public function create_menu($user, $type, $parent_id = 0) {
		try {
			$array_menu = null;
			
			if (($db = UtilityDB::getDBfromTable("krs_menu_type"))) {
					
				$stmt_menutype = $db->prepare("SELECT * FROM t:krs_menu_type WHERE MENUTYPE = :type");
				$stmt_menutype->bindTable("krs_menu_type", "krs_menu_type");
				$stmt_menutype->bindParam(":type", $type, KPDO::TYPE_VARCHAR);
				$stmt_menutype->execute();
					
				$menutype = $stmt_menutype->fetch(KPDO::FETCH_ASSOC);
	
				$stmt_menuitems = $db->prepare("SELECT * FROM t:krs_menuitems WHERE MENUTYPE_ID = :menutype_id AND PARENT_ID = :parent_id ORDER BY ORDERING, ID ASC");
				$stmt_menuitems->bindTable("krs_menuitems", "krs_menuitems");
				$stmt_menuitems->bindParam(":menutype_id", $menutype['ID'], KPDO::TYPE_INTEGER);
				$stmt_menuitems->bindParam(":parent_id", $parent_id, KPDO::TYPE_INTEGER);
				$stmt_menuitems->execute();
	
				if ($stmt_menuitems->rowCount() > 0) {
					while ($menuitem = $stmt_menuitems->fetch(KPDO::FETCH_ASSOC)) {
	
						$stmt_prog_menuitems = $db->prepare("SELECT * FROM t:krs_progs_menuitems_map WHERE MENUITEMS_ID = :menuitem_id ");
						$stmt_prog_menuitems->bindTable("krs_progs_menuitems_map", "krs_progs_menuitems_map");
						$stmt_prog_menuitems->bindParam(":menuitem_id", $menuitem['ID'], KPDO::TYPE_INTEGER);
						$stmt_prog_menuitems->execute();
	
						if (!($prog_menuitem = $stmt_prog_menuitems->fetch(KPDO::FETCH_ASSOC))) {
							continue;
						} else {
							if ($array_menu == null) {
								$array_menu = array();
							}
	
							$link = '';
							$title = '';
							$children = null;
							if ($prog_menuitem['PROG_ID'] != -1) {
									
								$stmt_prog = $db->prepare("SELECT * FROM t:krs_progs WHERE ID = :id");
								$stmt_prog->bindTable("krs_progs", "krs_progs");
								$stmt_prog->bindParam(":id", $prog_menuitem['PROG_ID'], KPDO::TYPE_INTEGER);
								$stmt_prog->execute();
	
								$prog = $stmt_prog->fetch(KPDO::FETCH_ASSOC);
								
								$args = '';
								if($prog['PATTERN'] == '' || $prog['PATTERN'] == 'direct') {
									$args = 'pattern=direct';
								}

								// the item shows up only if the user can run what it opens,
								// with the access level that route needs (e.g. "New" =
								// write), the same check as krsobjp/index.php
								if ($args ? !$user->hasPermission($prog['ID']) : !$this->can_run($user, $this->generete_path_prog($prog, $db))) { continue; }
								$link = 'javascript:actionKAJAX(\'' . $this->url->link($this->generete_path_prog($prog, $db), $args, 'SSL') . '\', null, \'\', \'get\')';
								$title = $menuitem['TITLE'];
									
							} else {
								$link = null;
								$title = $menuitem['TITLE'];
							}
							
							$children = $this->create_menu($user, $type, $menuitem['ID']);
							
							$array_menu[] = array(
								'title' => $title,
								'link' => $link,
								'children' => $children
							);
						}
					}
				}
			}
			
			return $array_menu;
			
		} catch (KPDOException $e) {
			echo $e;
		}
	}
	
	// "Direct" program (empty PATTERN or 'direct') matching a route requested
	// with pattern=direct: the path of every direct program is rebuilt exactly
	// as the menu does and compared, on real files, with the requested one.
	// krsobjp/index.php uses it to check the permission on the program before
	// including it.
	public function find_direct_prog($route) {
		$target = realpath(DIR_MODULE . $route);

		if (!$target || !($db = UtilityDB::getDBfromTable("krs_progs"))) {
			return false;
		}

		$stmt_progs = $db->prepare("SELECT * FROM t:krs_progs WHERE PATTERN = '' OR PATTERN = 'direct'");
		$stmt_progs->bindTable("krs_progs", "krs_progs");
		$stmt_progs->execute();

		while (($prog = $stmt_progs->fetch(KPDO::FETCH_ASSOC))) {
			$path = $this->generete_path_prog($prog, $db);

			if ($path && realpath(DIR_MODULE . $path) === $target) {
				return $prog;
			}
		}

		return false;
	}

	// Access check of a route for a user, used by the pre-action of
	// krsobjp/index.php and by the menu.
	//
	// Permissions belong to the controller: every program registered on the
	// same controller (its list, its "New" item...) counts, and the method
	// decides the level it needs (required_mode). A route of a module that
	// matches no program is refused, unless its controller declares the
	// method open to any logged-in user (ACCESS = 'login'). The framework's
	// own areas, which do their own checks, only need the login.
	public function can_run($user, $route) {
		if (!$this->is_route_module_available($route)) {
			return false;
		}

		$action = new Action($route);

		// a missing controller: the dispatcher answers "not found"
		if (!is_file($action->getFile())) {
			return true;
		}

		$mode = $this->required_mode($action);
		$progs = $this->controller_progs($action);

		if (!$progs) {
			return $mode === null || in_array(PathResolver::area($route), array('standard', 'common', 'error'), true);
		}

		if ($mode === null) {
			return true;
		}

		foreach ($progs as $prog) {
			if ($user->hasPermission($prog['ID'], $mode)) {
				return true;
			}
		}

		return false;
	}

	// Route a program opens (module path + PROG), as the menu builds it; ''
	// if it can't be built. Used by Admin > User Groups too.
	public function prog_route($prog) {
		$db = UtilityDB::getDBfromTable("krs_progs");

		return $db ? (string) $this->generete_path_prog($prog, $db) : '';
	}

	// Level a method needs (Auth::PERM_*), or null if any logged-in user can
	// run it. The controller can declare it:
	//   const ACCESS = array('index' => 'read', 'export' => 'read', 'ping' => 'login');
	// otherwise index and the methods starting with autocomplete, get,
	// search, list or view need read, those starting with delete need
	// delete, every other one write (the safer guess).
	public function required_mode($action) {
		$levels = array('read' => Auth::PERM_READ, 'write' => Auth::PERM_WRITE, 'delete' => Auth::PERM_DELETE, 'login' => null);
		$method = strtolower($action->getMethod());

		require_once($action->getFile());

		$class = $action->getClass();

		if (class_exists($class, false) && defined($class . '::ACCESS')) {
			$access = array_change_key_case((array) constant($class . '::ACCESS'), CASE_LOWER);

			if (isset($access[$method]) && array_key_exists($access[$method], $levels)) {
				return $levels[$access[$method]];
			}
		}

		if ($method === 'index' || preg_match('/^(autocomplete|get|search|list|view)/', $method)) {
			return Auth::PERM_READ;
		}

		if (strpos($method, 'delete') === 0) {
			return Auth::PERM_DELETE;
		}

		return Auth::PERM_WRITE;
	}

	// The programs registered on the controller a route resolves to (any of
	// its methods), comparing real files, so variants like
	// "customers//customers" don't slip through.
	private function controller_progs($action) {
		if (!($db = UtilityDB::getDBfromTable("krs_progs"))) {
			return array();
		}

		$requestedFile = realpath($action->getFile());
		$progs = array();

		$stmt_progs = $db->prepare("SELECT * FROM t:krs_progs WHERE PATTERN <> '' AND PATTERN <> 'direct'");
		$stmt_progs->bindTable("krs_progs", "krs_progs");
		$stmt_progs->execute();

		while (($prog = $stmt_progs->fetch(KPDO::FETCH_ASSOC))) {
			$path = $this->generete_path_prog($prog, $db);

			if ($path && realpath((new Action($path))->getFile()) === $requestedFile) {
				$progs[] = $prog;
			}
		}

		return $progs;
	}

	// A module with an installer (installation/install.php) is reachable only
	// if it's installed: uninstalling it removes programs and permissions but
	// leaves the files, which would otherwise stay open to every logged-in user.
	// The framework areas (standard, common, error, ...) have no installer.
	public function is_route_module_available($route) {
		$segments = array_values(array_filter(explode('/', str_replace('..', '', (string) $route)), function ($segment) {
			return $segment !== '' && $segment !== '.';
		}));

		if (!$segments || !is_file(DIR_MODULE . $segments[0] . '/installation/install.php')) {
			return true;
		}

		if (!($db = UtilityDB::getDBfromTable("krs_modules"))) {
			return false;
		}

		$stmt = $db->prepare("SELECT ID FROM t:krs_modules WHERE NAME = :name");
		$stmt->bindTable("krs_modules", "krs_modules");
		$stmt->bindParam(":name", $segments[0], KPDO::TYPE_VARCHAR);
		$stmt->execute();

		return (bool) $stmt->fetch(KPDO::FETCH_ASSOC);
	}

	private function generete_path_prog($prog, $db) {
		try {
			$stmt_progs_modules_map = $db->prepare("SELECT * FROM t:krs_progs_modules_map WHERE PROG_ID = :prog_id");
			$stmt_progs_modules_map->bindTable("krs_progs_modules_map", "krs_progs_modules_map");
			$stmt_progs_modules_map->bindParam(":prog_id", $prog['ID'], KPDO::TYPE_INTEGER);
			$stmt_progs_modules_map->execute();
				
			$map = $stmt_progs_modules_map->fetch(KPDO::FETCH_ASSOC);
				
			$stmt_modules = $db->prepare("SELECT * FROM t:krs_modules WHERE ID = :id");
			$stmt_modules->bindTable("krs_modules", "krs_modules");
				
			$suffix = '';
			if($prog['PATTERN'] == '') {
				$suffix = '.php';
			}
			return $this->generete_path_module($stmt_modules, $map['MODULE_ID']) . "/" . $prog['PROG'] . $suffix;
		} catch (KPDOException $e) {
			echo $e;
		}
	}
	
	private function generete_path_module(iStatement $stmt_modules, $module_id) {
		try {
			$stmt_modules->bindParam(":id", $module_id, KPDO::TYPE_INTEGER);
			$stmt_modules->execute();
	
			$module = $stmt_modules->fetch(KPDO::FETCH_ASSOC);
			if ($module['PARENT_ID'] != 0) {
				return $this->generete_path_module($stmt_modules, $module['PARENT_ID']) . "/" . $module['NAME'];
			} else {
				return $module['NAME'];
			}
		} catch (KPDOException $e) {
			echo $e;
		}
	}
	
	public function create_menu_label() {
		$extension_data = array();
		
		$db = UtilityDB::getDBfromTable('krs_modules');
		$stmt = $db->prepare('SELECT * FROM t:krs_modules');
		$stmt->bindTable('krs_modules', 'krs_modules');
		$stmt->execute();
		
		while ($result = $stmt->fetch()) {
			$this->language->load($result['NAME'] . '/menu');
		}
	}
}
?>