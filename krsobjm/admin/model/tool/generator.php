<?php
class ModelAdminToolGenerator extends Model {
	public function getDatabases() {
		$databases = array();

		$db = UtilityDB::getDBfromTable('krs_databases');
		$stmt = $db->prepare('SELECT * FROM t:krs_databases');
		$stmt->bindTable("krs_databases", "krs_databases");
		$stmt->execute();

		while ($result = $stmt->fetch()) {
			$databases[] = array('id' => $result['ID'], 'name' => $result['NAME']);
		}

		return $databases;
	}
	
	public function getTables($database) {
		$tables = array();

		$db = UtilityDB::getDBfromTable('krs_tables');
		$stmt = $db->prepare('SELECT * FROM t:krs_tables WHERE database_id = :database');
		$stmt->bindTable("krs_tables", "krs_tables");
		$stmt->bindParam("database", $database, KPDO::TYPE_INTEGER);
		$stmt->execute();
		
		while ($result = $stmt->fetch()) {
			$tables[] = array('id' => $result['ID'], 'name' => $result['NAME']);
		}
		
		return $tables;
	}
	
	public function getDescribeTable($table_id) {
		$fields = array();
		
		$table = $this->getTable($table_id);
		if ($table) {
			$table_name = $table['NAME'];
			
			$db = UtilityDB::getDBfromTable($table_name);
			$fields = $db->describeTable($table_name);
		}
		
		return $fields;
	}
	
	private function getTable($id) {
		$db = UtilityDB::getDBfromTable('krs_tables');
		$stmt = $db->prepare('SELECT * FROM t:krs_tables WHERE id = :id');
		$stmt->bindTable("krs_tables", "krs_tables");
		$stmt->bindParam("id", $id, KPDO::TYPE_INTEGER);
		$stmt->execute();
	
		return $stmt->fetch();
	}
	
	public function generator($data) {
		$table = $this->getTable($data['table_id']);
		$module = $data['module'];
		
		$this->makeStructure($module);
		$this->createInstall($module, $table, $data);
		$this->createModel($module, $table, $data);
		$this->createView($module, $table, $data);
		$this->createController($module, $table, $data);
		$this->createLanguage($module, $table, $data);
	}
	
	private function makeStructure($module) {
		if (!file_exists(DIR_MODULE . $module)) {
			mkdir(DIR_MODULE . $module);
			mkdir(DIR_MODULE . $module . '/model');
			mkdir(DIR_MODULE . $module . '/view');
			mkdir(DIR_MODULE . $module . '/controller');
			mkdir(DIR_MODULE . $module . '/installation');
			mkdir(DIR_MODULE . $module . '/language');
		}
	}
	
	// A single installation/install.php per module (the one the admin's Modules
	// page looks for) and an installation/menu_<table>.php for each generated
	// table, which install.php collects: generating a second table in the same
	// module adds its items instead of creating another installer.
	private function createInstall($module, $table, $data) {
		$Table = str_replace('_', '', ucfirst(strtolower($table['NAME'])));

		$insert_item = '';

		if (!empty($data['insert_check'])) {
			// title in the default language: it's also the key of createLanguage()'s menu.php files
			$title = addcslashes($this->textAdd($this->defaultLanguage()) . ' ' . $Table, "\\'");

			$insert_item = "\r\n\tarray('" . $title . "', '" . $title . "', '" . strtolower($table['NAME']) . "/insert', '0', 'mvc'),";
		}

		$file_menu = str_replace(
			array('{Table}', '{table}', '{insert.item}'),
			array($Table, strtolower($table['NAME']), $insert_item),
			file_get_contents(DIR_LIBRARY . 'generator/menu_file.dat')
		);

		file_put_contents(DIR_MODULE . $module . '/installation/menu_' . strtolower($table['NAME']) . '.php', $file_menu);

		$file_install = str_replace(
			array('{Module}', '{module}', '{delete.table}'),
			array(
				str_replace('_', '', ucfirst(strtolower($module))),
				$module,
				(isset($data['delete_table']) && $data['delete_table']) ? '' : 'array(\'delete.table\' => false)'
			),
			file_get_contents(DIR_LIBRARY . 'generator/install_file.dat')
		);

		file_put_contents(DIR_MODULE . $module . '/installation/install.php', $file_install);
	}
	
	private function createModel($module, $table, $data) {
		$model = fopen(DIR_MODULE . $module . '/model/' . strtolower($table['NAME']) . '.php', 'w');
		
		$file_model = file_get_contents(DIR_LIBRARY . 'generator/model_file.dat');
		
		// Model Part
		$filters_query = '';
		if (!empty($data['search_check'])) {
			foreach ($data['fields'] as $field) {
				if (isset($field['field_search_check'])) {
					
					// "starts with" filter with a bound parameter, not concatenated
					$filters_query .= 'if (!empty($data[\'filter_' . $field['field'] . '\'])) {
						$implode[] = "' . $field['field'] . ' LIKE :filter_' . $field['field'] . '";
						$params[\'filter_' . $field['field'] . '\'] = $data[\'filter_' . $field['field'] . '\'] . \'%\';
					}';
				}
			}
		}
		
		// Search Part
		$search_model = file_get_contents(DIR_LIBRARY . 'generator/model_search.dat');
			
		$find = array(
			'{Table}',
			'{table}',
			'{filters_query}'
		);
		
		$replace = array(
			str_replace('_', '', ucfirst(strtolower($table['NAME']))),
			$table['NAME'],
			$filters_query
		);
		
		$search_model = str_replace($find, $replace, $search_model);
		
		// Insert/Update Part
		$insert_model = '';
		if ($data['insert_check']) {
			
			$insert_model = file_get_contents(DIR_LIBRARY . 'generator/model_insert.dat');
			
			$where = '';
			$where_array = array();
			foreach ($data['fields'] as $field) {
				if (isset($field['key_check'])) {
					$where_array[] = $field['field'] . "= :" . $field['field'];
				}
			}
			$where = implode(" AND ", $where_array);
			
			$bind_key = '';
			foreach ($data['fields'] as $field) {
				if (isset($field['key_check'])) {
					$type = $this->getType($field['type']);
					$bind_key .= '$stmt->bindParam("' . $field['field'] . '", $data[\'' . $field['field'] . '\'], ' . $type . ');' . "\n";
				}
			}
			
			$field_insert = '';
			$field_insert_array = array();
			foreach ($data['fields'] as $field) {
				if (isset($field['field_insert_check'])) {
				    $field_insert_array[] = '`'.$field['field'].'`';
				}
			}
			$field_insert = implode(", ", $field_insert_array);
			
			$field_insert_value = '';
			$field_insert_array = array();
			foreach ($data['fields'] as $field) {
				if (isset($field['field_insert_check'])) {
					$field_insert_array[] = ':' . $field['field'];
				}
			}
			$field_insert_value = implode(", ", $field_insert_array);
			
			$bind_insert = '';
			foreach ($data['fields'] as $field) {
				if (isset($field['field_insert_check'])) {
					$type = $this->getType($field['type']);
					$bind_insert .= '$stmt->bindParam("' . $field['field'] . '", $data[\'' . $field['field'] . '\'], ' . $type . ');' . "\n";
				}
			}
			
			$field_update = '';
			$field_update_array = array();
			foreach ($data['fields'] as $field) {
				if (isset($field['field_insert_check'])) {
					$field_update_array[] = $field['field'] . "= :" . $field['field'];
				}
			}
			$field_update = implode(", ", $field_update_array);
			
			$find = array(
				'{Table}',
				'{table}',
				'{where}',
				'{bind_key}',
				'{field_insert}',
				'{field_insert_value}',
				'{bind_insert}',
				'{field_update}'
			);
				
			$replace = array(
				str_replace('_', '', ucfirst(strtolower($table['NAME']))),
				$table['NAME'],
				$where,
				$bind_key,
				$field_insert,
				$field_insert_value,
				$bind_insert,
				$field_update
			);
			
			$insert_model = str_replace($find, $replace, $insert_model);
		}
		
		// Delete Part
		$delete_model = '';
		if ($data['delete_check']) {
				
			$delete_model = file_get_contents(DIR_LIBRARY . 'generator/model_delete.dat');
			
			$where = '';
			$where_array = array();
			foreach ($data['fields'] as $field) {
				if (isset($field['key_check'])) {
					$where_array[] = $field['field'] . "= :" . $field['field'];
				}
			}
			$where = implode(" AND ", $where_array);
				
			$bind_key = '';
			foreach ($data['fields'] as $field) {
				if (isset($field['key_check'])) {
					$type = $this->getType($field['type']);
					$bind_key .= '$stmt->bindParam("' . $field['field'] . '", $data[\'' . $field['field'] . '\'], ' . $type . ');' . "\n";
				}
			}
			
			$find = array(
				'{Table}',
				'{table}',
				'{where}',
				'{bind_key}'
			);
			
			$replace = array(
				str_replace('_', '', ucfirst(strtolower($table['NAME']))),
				$table['NAME'],
				$where,
				$bind_key
			);
			
			$delete_model = str_replace($find, $replace, $delete_model);
		}
		
		// Pagination Part
		$pagination_model = '';
		if (isset($data['pagination_check'])) {
			$pagination_model = file_get_contents(DIR_LIBRARY . 'generator/model_pagination.dat');
			
			$find = array(
				'{Table}',
				'{table}',
				'{filters_query}'
			);
		
			$replace = array(
				str_replace('_', '', ucfirst(strtolower($table['NAME']))),
				$table['NAME'],
				$filters_query
			);
		
			$pagination_model = str_replace($find, $replace, $pagination_model);
		}
		
		$find = array(
			'{Module}',
			'{module}',
			'{Table}',
			'{table}',
			'{search_model}',
			'{insert_model}',
			'{delete_model}',
			'{pagination_model}'
		);
			
		$replace = array(
			str_replace('_', '', ucfirst(strtolower($module))),
			strtolower($module),
			str_replace('_', '', ucfirst(strtolower($table['NAME']))),
			$table['NAME'],
			$search_model,
			$insert_model,
			$delete_model,
			$pagination_model
		);
		
		$file_model = str_replace($find, $replace, $file_model);
			
		fwrite($model, $file_model);
		fclose($model);
	}
	
	private function createView($module, $table, $data) {
		$view = fopen(DIR_MODULE . $module . '/view/' . strtolower($table['NAME']) . '.tpl', 'w');
		
		$file_view = file_get_contents(DIR_LIBRARY . 'generator/view_file.dat');
		
		// View Part
		$fields_count = 0;
		$head_fields = '';
		$body_fields = '';
		foreach ($data['fields'] as $field) {
			if (isset($field['check'])) {
			    $fields_count++;
			    
				$head_fields .= '<th class="text-left"><?php echo $text_' . $field['field'] . '; ?></th>';
				
				$body_fields .= '<td class="text-left"><?php echo $' . strtolower($table['NAME']) . '[\'' . $field['field'] . '\']; ?></td>';
			}
		}
		
		// Search Part
		$form_search = '';
		$script_search = '';
		if (isset($data['search_check'])) {
			$script_search = '
			<script type="text/javascript"><!--
				$(\'#button-filter\').on(\'click\', function() {
					url = \'<?php echo $action_search; ?>\';';
			
			foreach ($data['fields'] as $field) {
				if (isset($field['field_search_check'])) {
					$form_search .= '
					<div class="row">
						<div class="col-sm-12">
							<div class="form-group">
								<label class="control-label" for="input-' . $field['field'] . '"><?php echo $text_' . $field['field'] . '; ?></label>
								<input type="text" name="filter_' . $field['field'] . '" value="<?php echo $filter_' . $field['field'] . '; ?>" placeholder="<?php echo $text_' . $field['field'] . '; ?>" id="input-' . $field['field'] . '" class="form-control" />
						  	</div>
						</div>
					</div>';
					
					$script_search .= '
					var filter_' . $field['field'] . ' = $(\'input[name=\\\'filter_' . $field['field'] . '\\\']\').val();
						
					if (filter_' . $field['field'] . ') {
						url += \'&filter_' . $field['field'] . '=\' + encodeURIComponent(filter_' . $field['field'] . ');
					}';
				}
			}
			
			$script_search .= '
					actionKAJAX(url);
				});
				
				$(\'#button-reset\').click(function() {
					var form = $(\'#form-search\');
				
					form.find(\'input[type=text]\').each(function() {
						$(this).val(\'\');
					});
		
					$(\'#button-filter\').trigger(\'click\');
		
					return false;
				});
			//--></script>';
		}
		
		// Insert/Update Part
		$head_update = '';
		$body_update = '';
		$script_update = '';
		if (isset($data['insert_check'])) {
		    $fields_count++;
		    
			$head_update = '<td></td>';
			
			$body_update .= '<td><form action="<?php echo $action_update; ?>" method="post">';
			foreach ($data['fields'] as $field) {
				if (isset($field['key_check'])) {
					$body_update .= '<input type="hidden" name="' . $field['field'] . '" value="<?php echo $' . strtolower($table['NAME']) . '[\'' . $field['field'] . '\']; ?>" />';
				}
			}
			$body_update .= '<input class="update" type="submit" value="<?php echo $button_edit; ?>" /></form></td>';
			
			$script_update = '
			<script type="text/javascript"><!--
				$(\'.update\').click(function() {
					var form = $(this).parent();
					actionKAJAX(form.attr(\'action\'), form.serialize());
					return false;
				});
			//--></script>';
		}
		
		// Delete Part
		$head_delete = '';
		$body_delete = '';
		$script_delete = '';
		if (isset($data['delete_check'])) {
		    $fields_count++;
		    
			$head_delete = '<td></td>';
			
			$body_delete .= '<td><form action="<?php echo $action_delete; ?>" method="post">';
			foreach ($data['fields'] as $field) {
				if (isset($field['key_check'])) {
					$body_delete .= '<input type="hidden" name="' . $field['field'] . '" value="<?php echo $' . strtolower($table['NAME']) . '[\'' . $field['field'] . '\']; ?>" />';
				}
			}
			$body_delete .= '<input class="delete" type="submit" value="<?php echo $button_delete; ?>" /></form></td>';
			
			$script_delete = '
			<script type="text/javascript"><!--
				$(\'.delete\').click(function() {
					if (confirm(<?php echo json_encode($text_confirm); ?>)) {
						var form = $(this).parent();
						actionKAJAX(form.attr(\'action\'), form.serialize());
					}
	
					return false;
				});
			//--></script>';
		}
		
		$autocomplete_view = '';
		if (isset($data['relationship'])) {
			foreach ($data['relationship'] as $relationship) {
				$table_many = str_replace('_', '', ucfirst(strtolower($relationship['table_name'])));
					
				$function = '
				<script type="text/javascript"><!--
					$("input[name=\'filter[' . $relationship['foreignkey'] . ']\']").autocomplete({
						source: function( request, response ) {
							$.ajax({
								url: "<?php echo $action_autocomplete' . $table_many . '; ?>",
								type: "POST",
								dataType: "json",
								data: {
									filter: {\'' . $relationship['primarykey_label'] . '\':request.term}
								},
								success: function( data ) {
									response( $.map( data, function( item ) {
										return {
											label: item.'. $relationship['primarykey_label'] . ',
											value: item.'. $relationship['primarykey_id'] . '
										};
									}));
								}
							});
						},
						select: function(event, ui) {
							$(this).parent().find(\'span\').remove();
							$(this).after(\'<span>\' + ui.item.label + \'</span>\');
						}
					});
				//--></script>';
					
				$autocomplete_view .= $function . "\n";
			}
		}
		
		$find = array(
			'{Module}',
			'{module}',
			'{Table}',
			'{table}',
			'{form_search}',
		    '{fields_count}',
			'{head_fields}',
			'{head_update}',
			'{head_delete}',
			'{body_fields}',
			'{body_update}',
			'{body_delete}',
			'{script_search}',
			'{script_update}',
			'{script_delete}',
			'{autocomplete_view}'
		);
		
		$replace = array(
			str_replace('_', '', ucfirst(strtolower($module))),
			strtolower($module),
			str_replace('_', '', ucfirst(strtolower($table['NAME']))),
			strtolower($table['NAME']),
			$form_search,
		    $fields_count,
			$head_fields,
			$head_update,
			$head_delete,
			$body_fields,
			$body_update,
			$body_delete,
			$script_search,
			$script_update,
			$script_delete,
			$autocomplete_view
		);
		
		$file_view = str_replace($find, $replace, $file_view);
			
		fwrite($view, $file_view);
		fclose($view);
		
		if (isset($data['insert_check'])) {
			$view_form = fopen(DIR_MODULE . $module . '/view/' . strtolower($table['NAME']) . '_form.tpl', 'w');
			
			$file_view_form = file_get_contents(DIR_LIBRARY . 'generator/view_form_file.dat');
			
			$field_input = '';
			foreach ($data['fields'] as $field) {
				if (isset($field['field_insert_check'])) {
					$field_input .= '
					<div class="row">
						<div class="col-sm-10">
							<div class="form-group">
								<label class="col-sm-2 control-label" for="' . $field['field'] . '"><?php echo $text_' . $field['field'] . '; ?></label>
								<div class="col-sm-10">
									<input type="text" name="' . $field['field'] . '" value="<?php echo $' . $field['field'] . '; ?>" placeholder="<?php echo $text_' . $field['field'] . '; ?>" id="' . $field['field'] . '" class="form-control" />
								</div>
							</div>
						</div>
					</div>';
				}
			}
			
			$field_hidden = '';
			foreach ($data['fields'] as $field) {
				if (isset($field['key_check'])) {
					$field_hidden .= '<input type="hidden" name="' . $field['field'] . '" value="<?php echo $' . $field['field'] . '; ?>"/>';
				}
			}
			
			$autocomplete_view = '';
			if (isset($data['relationship'])) {
				foreach ($data['relationship'] as $relationship) {
					$table_many = str_replace('_', '', ucfirst(strtolower($relationship['table_name'])));
					
					$function = '$("input[name=\'' . $relationship['foreignkey'] . '\']").autocomplete({
						source: function( request, response ) {
							$.ajax({
								url: "<?php echo $action_autocomplete' . $table_many . '; ?>",
								type: "POST",
								dataType: "json",
								data: {
									filter: {\'' . $relationship['primarykey_label'] . '\':request.term}
								},
								success: function( data ) {
									response( $.map( data, function( item ) {
										return {
											label: item.'. $relationship['primarykey_label'] . ',
											value: item.'. $relationship['primarykey_id'] . '
										};
									}));
								}
							});
						},
						select: function(event, ui) {
							$(this).parent().find(\'span\').remove();
							$(this).after(\'<span>\' + ui.item.label + \'</span>\');
						}
					});';
			
					$autocomplete_view .= $function . "\n";
				}
			}
			
			$find = array(
				'{field_input}',
				'{field_hidden}',
				'{autocomplete_view}'
			);
			
			$replace = array(
				$field_input,
				$field_hidden,
				$autocomplete_view
			);
			
			$file_view_form = str_replace($find, $replace, $file_view_form);
				
			fwrite($view_form, $file_view_form);
			fclose($view_form);
		}
	}
	
	private function createController($module, $table, $data) {
		$controller = fopen(DIR_MODULE . $module . '/controller/' . strtolower($table['NAME']) . '.php', 'w');
		
		$file_controller = file_get_contents(DIR_LIBRARY . 'generator/controller_file.dat');
		
		// Controller Part
		$text_fields = '';
		foreach ($data['fields'] as $field) {
			if (isset($field['check'])) {
				$text_fields .= '$this->data[\'text_' . $field['field'] . '\'] = $this->language->get(\'text_' . $field['field'] . '\');' . "\n";
			}
		}
		
		// Search Part
		$filters_query = '';
		$text_search = '';
		$action_search = '';
		$url_filters = '';
		$filters_data_filter = '';
		if (isset($data['search_check'])) {
			foreach ($data['fields'] as $field) {
				if (isset($field['field_search_check'])) {
					$filters_query .= 'if (isset($this->request->get[\'filter_' . $field['field'] . '\'])) {
						$filter_' . $field['field'] . ' = $this->request->get[\'filter_' . $field['field'] . '\'];
					} else {
						$filter_' . $field['field'] . ' = null;
					}
					
					$this->data[\'filter_' . $field['field'] . '\'] = $filter_' . $field['field'] . ';';
					
					$url_filters .= 'if (isset($this->request->get[\'filter_' . $field['field'] . '\'])) {
						$url .= \'&filter_' . $field['field'] . '=\' . urlencode(html_entity_decode($this->request->get[\'filter_' . $field['field'] . '\'], ENT_QUOTES, \'UTF-8\'));
					}';
					
					$filters_data_filter .= '\'filter_' . $field['field'] . '\' => $filter_' . $field['field'] . ',';
				}
			}
			
			$text_search .= '$this->data[\'button_filter\'] = $this->language->get(\'button_search\');';
			$text_search .= '$this->data[\'button_reset\'] = $this->language->get(\'button_reset\');';
			
			$action_search = '$this->data[\'action_search\'] = $this->url->link(\'' . $module . '/' . strtolower($table['NAME']) . '\', \'\', \'SSL\');';
		}
		
		// Pagination Part
		$page_query = '';
		$url_page = '';
		$limit = '';
		$filters_data_page = '';
		$pagination_controller = '';
		$url_pagination_filters = '';
		if (isset($data['pagination_check'])) {
			// page comes from the URL and ends up in the LIMIT and in the
			// pagination JavaScript: always an integer >= 1
			$page_query = 'if (isset($this->request->get[\'page\'])) {
				$page = max(1, (int) $this->request->get[\'page\']);
			} else {
				$page = 1;
			}';
				
			$url_page = 'if (isset($this->request->get[\'page\'])) {
				$url .= \'&page=\' . max(1, (int) $this->request->get[\'page\']);
			}';
				
			// written as it is into the generated source: only a number
			$limit = '$limit = ' . max(1, (int) $data['pagination_limit']) . ';';
				
			$filters_data_page = '\'start\' => ($page - 1) * $limit,
			\'limit\' => $limit';
				
			$pagination_controller = file_get_contents(DIR_LIBRARY . 'generator/controller_pagination.dat');
				
			$url_pagination_filters = '';
			foreach ($data['fields'] as $field) {
				if (isset($field['field_search_check'])) {
					$url_pagination_filters .= 'if (isset($this->request->get[\'filter_' . $field['field'] . '\'])) {
						$url .= \'&filter_' . $field['field'] . '=\' . urlencode(html_entity_decode($this->request->get[\'filter_' . $field['field'] . '\'], ENT_QUOTES, \'UTF-8\'));
					}';
				}
			}
				
			$find = array(
				'{module}',
				'{Table}',
				'{table}',
				'{url_pagination_filters}'
			);
		
			$replace = array(
				$module,
				str_replace('_', '', ucfirst(strtolower($table['NAME']))),
				strtolower($table['NAME']),
				$url_pagination_filters
			);
		
			$pagination_controller = str_replace($find, $replace, $pagination_controller);
		}
		
		// Autocomplete Part
		$action_autocomplete = '';
		$autocomplete_controller = '';
		if (isset($data['relationship'])) {
		
			foreach ($data['relationship'] as $relationship) {
				$table_many = str_replace('_', '', ucfirst(strtolower($relationship['table_name'])));
					
				$action_autocomplete .= '$this->data[\'action_autocomplete' . $table_many . '\'] = $this->url->link(\'' . $module . '/' . strtolower($table['NAME']) . '/autocomplete' . $table_many . '\', \'\', \'SSL\');' . "\n";
			}
				
			foreach ($data['relationship'] as $relationship) {
				$table_many = str_replace('_', '', ucfirst(strtolower($relationship['table_name'])));
				$model = $module . "/" . strtolower($relationship['table_name']);
		
				$function = 'public function autocomplete' . $table_many . '() {
					$this->load->model(\'' . $model . '\');
		
					$result = array();
		
					if (isset($this->request->post[\'filter\'])) {
						$filter = $this->request->post[\'filter\'];
		
						$result = $this->model_' . $module . '_' . strtolower($relationship['table_name']) . '->getAll' . $table_many . '($filter, 0, 10);
					}
		
					$this->response->setOutput(json_encode($result));
				}';
		
				$autocomplete_controller .= $function . "\n";
			}
		}
		
		// Insert/Update Part
		$text_update = '';
		$action_update = '';
		$insert_controller = '';
		if (isset($data['insert_check'])) {
			$text_update = '$this->data[\'button_edit\'] = $this->language->get(\'button_edit\');';
				
			$action_update = '$this->data[\'action_update\'] = $this->url->link(\'' . $module . '/' . strtolower($table['NAME']) . '/update\', $url, \'SSL\');';
			
			$insert_controller = file_get_contents(DIR_LIBRARY . 'generator/controller_insert.dat');
			
			$text_fields_insert = '';
			foreach ($data['fields'] as $field) {
				if (isset($field['field_insert_check'])) {
					$text_fields_insert .= '$this->data[\'text_' . $field['field'] . '\'] = $this->language->get(\'text_' . $field['field'] . '\');' . "\n";
				}
			}
			
			$test_key = '';
			$test_key_array = array();
			foreach ($data['fields'] as $field) {
				if (isset($field['key_check'])) {
					$test_key_array[] = '!empty($this->request->post[\'' . $field['field'] . '\'])';
				}
			}
			$test_key = implode(" && ", $test_key_array);
			
			$field_key_update = '';
			foreach ($data['fields'] as $field) {
				if (isset($field['key_check'])) {
					$field_key_update .= '$this->data[\'' . $field['field'] . '\'] = $this->request->post[\'' . $field['field'] . '\'];';
				}
			}
			
			$field_key_insert = '';
			foreach ($data['fields'] as $field) {
				if (isset($field['key_check'])) {
					$field_key_insert .= '$this->data[\'' . $field['field'] . '\'] = \'\';';
				}
			}
			
			$check_field_insert = '';
			foreach ($data['fields'] as $field) {
				if (isset($field['field_insert_check'])) {
					$check_field_insert .= 'if (isset($this->request->post[\'' . $field['field'] . '\'])) {
						$this->data[\'' . $field['field'] . '\'] = $this->request->post[\'' . $field['field'] . '\'];
					} elseif (isset($' . strtolower($table['NAME']) . ')) {
						$this->data[\'' . $field['field'] . '\'] = $' . strtolower($table['NAME']) . '[\'' . $field['field'] . '\'];
					} else {
						$this->data[\'' . $field['field'] . '\'] = \'\';
					}';
				}
			}
			
			$find = array(
				'{Module}',
				'{module}',
				'{Table}',
				'{table}',
				'{text_fields_insert}',
				'{action_autocomplete}',
				'{test_key}',
				'{field_key_update}',
				'{field_key_insert}',
				'{check_field_insert}',
				'{url_filters}',
				'{url_page}'
			);
			
			$replace = array(
				str_replace('_', '', ucfirst(strtolower($module))),
				$module,
				str_replace('_', '', ucfirst(strtolower($table['NAME']))),
				strtolower($table['NAME']),
				$text_fields_insert,
				$action_autocomplete,
				$test_key,
				$field_key_update,
				$field_key_insert,
				$check_field_insert,
				$url_filters,
				$url_page
			);
			
			$insert_controller = str_replace($find, $replace, $insert_controller);
		}
		
		// Delete Part
		$text_delete = '';
		$action_delete = '';
		$delete_controller = '';
		if (isset($data['delete_check'])) {
			$text_delete = '$this->data[\'button_delete\'] = $this->language->get(\'button_delete\');';
				
			$action_delete = '$this->data[\'action_delete\'] = $this->url->link(\'' . $module . '/' . strtolower($table['NAME']) . '/delete\', $url, \'SSL\');';
			
			$delete_controller = file_get_contents(DIR_LIBRARY . 'generator/controller_delete.dat');
			
			$test_key = '';
			$test_key_array = array();
			foreach ($data['fields'] as $field) {
				if (isset($field['key_check'])) {
					$test_key_array[] = '!isset($this->request->post[\'' . $field['field'] . '\'])';
				}
			}
			$test_key = implode(" || ", $test_key_array);
			
			$find = array(
				'{module}',
				'{Table}',
				'{table}',
				'{test_key}',
				'{url_filters}',
				'{url_page}'
			);
				
			$replace = array(
				$module,
				str_replace('_', '', ucfirst(strtolower($table['NAME']))),
				strtolower($table['NAME']),
				$test_key,
				$url_filters,
				$url_page
			);
				
			$delete_controller = str_replace($find, $replace, $delete_controller);
		}

		// List Part
		$list_controller = file_get_contents(DIR_LIBRARY . 'generator/controller_list.dat');
		
		$find = array(
			'{Module}',
			'{module}',
			'{Table}',
			'{table}',
			'{filters_query}',
			'{page_query}',
			'{url_filters}',
			'{url_page}',
			'{action_update}',
			'{action_delete}',
			'{action_search}',
			'{limit}',
			'{filters_data_filter}',
			'{filters_data_page}',
			'{text_fields}',
			'{text_search}',
			'{text_update}',
			'{text_delete}',
			'{pagination_controller}'
		);
			
		$replace = array(
			str_replace('_', '', ucfirst(strtolower($module))),
			$module,
			str_replace('_', '', ucfirst(strtolower($table['NAME']))),
			strtolower($table['NAME']),
			$filters_query,
			$page_query,
			$url_filters,
			$url_page,
			$action_update,
			$action_delete,
			$action_search,
			$limit,
			$filters_data_filter,
			$filters_data_page,
			$text_fields,
			$text_search,
			$text_update,
			$text_delete,
			$pagination_controller
		);
			
		$list_controller = str_replace($find, $replace, $list_controller);
		
		
		$find = array(
			'{Module}',
			'{module}',
			'{Table}',
			'{table}',
			'{list_controller}',
			'{insert_controller}',
			'{delete_controller}',
			'{autocomplete_controller}'
		);
		
		$replace = array(
			str_replace('_', '', ucfirst(strtolower($module))),
			$module,
			str_replace('_', '', ucfirst(strtolower($table['NAME']))),
			strtolower($table['NAME']),
			$list_controller,
			$insert_controller,
			$delete_controller,
			$autocomplete_controller,
			$pagination_controller,
			
			$filters_query,
			$filters_data_filter,
			$filters_data_page,
			
			$page_query,
			
			$url_filters,
			$url_page,
			$url_pagination_filters,
				
			$text_fields,
			$text_search,
			$text_update,
			$text_delete,
			$text_fields_insert,
			
			$action_delete,
			$action_search,
			$action_autocomplete,
			
			$test_key,
			$field_key_update,
			$field_key_insert,
			$check_field_insert,
			
			$limit
			
		);
		
		$file_controller = str_replace($find, $replace, $file_controller);
			
		fwrite($controller, $file_controller);
		fclose($controller);
	}
	
	// Texts of the module, one file per active language: labels (title,
	// list, fields: the same in every language until they are translated
	// from Admin > Languages) and menu items. The common messages (added,
	// updated, deleted) are in the user side's main language file.
	private function createLanguage($module, $table, $data) {
		$name = strtolower($table['NAME']);
		$Table = str_replace('_', '', ucfirst($name));

		$strings = array(
			'heading_title' => ucfirst(strtolower($module)),
			'text_list'     => ucfirst($name),
		);

		foreach ($data['fields'] as $field) {
			$strings['text_' . $field['field']] = ucfirst(strtolower($field['field']));
		}

		$defaultAdd = $this->textAdd($this->defaultLanguage());

		foreach ($this->languages() as $language) {
			if (!$language['status']) {
				continue;
			}

			$dir = DIR_MODULE . $module . '/language/' . $language['directory'] . '/';

			if (!is_dir($dir)) {
				mkdir($dir, 0775, true);
			}

			$this->writeStrings($dir . $name . '.php', $strings);

			// the menu translates the titles stored in the DB (see createInstall),
			// so the key is the title in the default language; there's one file
			// per module, collecting the items of all its tables
			$menu = $this->readStrings($dir . 'menu.php');
			$menu[$Table] = $Table;

			if (!empty($data['insert_check'])) {
				$menu[$defaultAdd . ' ' . $Table] = $this->textAdd($language) . ' ' . $Table;
			}

			$this->writeStrings($dir . 'menu.php', $menu);
		}
	}

	private function languages() {
		$db = UtilityDB::getDBfromTable('krs_languages');
		$stmt = $db->prepare('SELECT * FROM t:languages ORDER BY SORT_ORDER, NAME');
		$stmt->bindTable('languages', 'krs_languages');
		$stmt->execute();

		$languages = array();

		foreach ($stmt->fetchAll() as $row) {
			$languages[$row['CODE']] = array(
				'code'      => $row['CODE'],
				'status'    => $row['STATUS'],
				'directory' => $row['DIRECTORY'],
				'filename'  => $row['FILENAME'],
			);
		}

		return $languages;
	}

	private function defaultLanguage() {
		return Language::choose($this->languages(), array($this->config->get('setting.language')));
	}

	// "New" in the given language (text_add of the user side), for the menu
	// item of the insert form; if the language lacks it, the default's one
	private function textAdd($language) {
		foreach (array($language, $this->defaultLanguage()) as $candidate) {
			$strings = $this->readStrings(DIR_MODULE . 'standard/language/' . $candidate['directory'] . '/' . $candidate['filename'] . '.php');

			if (isset($strings['text_add'])) {
				return $strings['text_add'];
			}
		}

		return 'New';
	}

	private function readStrings($file) {
		if (!is_file($file)) {
			return array();
		}

		$_ = array();

		include $file;

		return $_;
	}

	// var_export: field names and texts go into the file as correctly escaped
	// PHP strings, whatever characters they contain
	private function writeStrings($file, $strings) {
		$php = "<?php\n";

		foreach ($strings as $key => $text) {
			$php .= '$_[' . var_export((string) $key, true) . '] = ' . var_export((string) $text, true) . ";\n";
		}

		file_put_contents($file, $php . "?>\n");
	}

	private function getType($type) {
		if (strlen(strstr($type, 'int')) > 0) {
			return 'KPDO::TYPE_INTEGER';
		} else {
			return 'KPDO::TYPE_VARCHAR';
		}
	}
}
?>