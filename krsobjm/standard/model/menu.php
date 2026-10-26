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
								
								if (!$user->hasPermission($prog['ID'])) { continue; }
								
								$args = '';
								if($prog['PATTERN'] == '' || $prog['PATTERN'] == 'direct') {
									$args = 'pattern=direct';
								}
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

	// Registered MVC programs matching a route: compares the controller and
	// method the route really resolves to with those of the programs (module
	// path + PROG, as in the menu), so variants like "customers//customers"
	// don't slip through. First the programs with the same method (e.g.
	// customers/customers/insert), otherwise the one on the index of the same
	// controller (customers/customers covers update, delete, ...).
	// No result = route not registered as a program.
	public function find_route_progs($route) {
		if (!($db = UtilityDB::getDBfromTable("krs_progs"))) {
			return array();
		}

		$requested = new Action($route);
		$requestedFile = realpath($requested->getFile());
		$method = strtolower($requested->getMethod());

		if (!$requestedFile) {
			return array();
		}

		$exact = array();
		$controller = array();

		$stmt_progs = $db->prepare("SELECT * FROM t:krs_progs WHERE PATTERN <> '' AND PATTERN <> 'direct'");
		$stmt_progs->bindTable("krs_progs", "krs_progs");
		$stmt_progs->execute();

		while (($prog = $stmt_progs->fetch(KPDO::FETCH_ASSOC))) {
			$path = $this->generete_path_prog($prog, $db);

			if (!$path) {
				continue;
			}

			$action = new Action($path);

			if (realpath($action->getFile()) !== $requestedFile) {
				continue;
			}

			if (strtolower($action->getMethod()) === $method) {
				$exact[] = $prog;
			} elseif (strtolower($action->getMethod()) === 'index') {
				$controller[] = $prog;
			}
		}

		return $exact ? $exact : $controller;
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