<?php echo $header; ?>
<div id="content">
	<div class="breadcrumb">
    	<?php foreach ($breadcrumbs as $breadcrumb) { ?>
    	<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    	<?php } ?>
  	</div>
  	<?php if ($success) { ?>
  		<div class="success"><?php echo $success; ?></div>
  	<?php } ?>
	<div class="box">
	    <div class="heading">
	      	<h1><img src="<?php echo makePath('img/log.png'); ?>" alt="" /> <?php echo $heading_title; ?></h1>
	      	<div class="buttons">
	      		<a onclick="$('#form').submit();" class="button"><?php echo $button_generator; ?></a>
	      	</div>
	    </div>
	    <div class="content">
	    	<div class="vtabs">
	    		<a href="#tab-general"><?php echo $tab_general; ?></a>
	    		<a href="#tab-insert"><?php echo $tab_insert; ?></a>
	    		<a href="#tab-search"><?php echo $tab_search; ?></a>
	    		<a href="#tab-relationship"><?php echo $tab_relationship; ?></a>
	      	</div>
	      	<form id="form" action="<?php echo $action_generator; ?>" method="post">
		      	<div id="tab-general" class="vtabs-content">
			    	<label for="database_id" ><?php echo $text_database; ?></label>
			    	<select id="databases" name="database_id">
			    		<option value="0"><?php echo $text_select; ?></option>
			    		<?php foreach ($databases as $database) { ?>
			    			<?php if ($database['id'] == $database_id) { ?>
			    				<option value="<?php echo $database['id']; ?>" selected="selected"><?php echo $database['name']; ?></option>
                  			<?php } else { ?>
                  				<option value="<?php echo $database['id']; ?>"><?php echo $database['name']; ?></option>
                  			<?php } ?>
			    		<?php }?>
			    	</select>
			    	<?php if ($error_database_id) { ?>
                		<span class="error"><?php echo $error_database_id; ?></span>
                	<?php } ?>
                	
			    	<label for="table_name"><?php echo $text_table; ?></label>
			    	<select id="tables" name="table_id" <?php echo ($database_id != '0')? '' : 'disabled="disabled"'?>>
			    		<option value="0"><?php echo $text_select; ?></option>
			    		<?php if (isset($tables)) {?>
			    			<?php foreach ($tables as $table) { ?>
				    			<?php if ($table['id'] == $table_id) { ?>
				    				<option value="<?php echo $table['id']; ?>" selected="selected"><?php echo $table['name']; ?></option>
	                  			<?php } else { ?>
	                  				<option value="<?php echo $table['id']; ?>"><?php echo $table['name']; ?></option>
	                  			<?php } ?>
			    			<?php }?>
			    		<?php }?>
			    	</select>
			    	<?php if ($error_table_id) { ?>
                		<span class="error"><?php echo $error_table_id; ?></span>
                	<?php } ?>
                	
			    	<br>
			    	<br>
			    	
			    	<?php if ($error_fields) { ?>
                		<span class="error"><?php echo $error_fields; ?></span>
                	<?php } ?>
                	<?php if ($error_fields_key) { ?>
                		<span class="error"><?php echo $error_fields_key; ?></span>
                	<?php } ?>
			    	<table id="fields" class="list">
			    		<thead>
			    			<tr>
			    				<td><?php echo $column_view; ?></td>
			    				<td><?php echo $column_key; ?></td>
			    				<td><?php echo $column_field; ?></td>
			    				<td><?php echo $column_type; ?></td>
			    				<td><?php echo $column_null; ?></td>
			    				<td><?php echo $column_key; ?></td>
			    				<td><?php echo $column_default; ?></td>
			    				<td><?php echo $column_extra; ?></td>
			    			</tr>
			    		</thead>
			    		<tbody>
			    			<?php if (isset($fields)) {?>
			    			<?php foreach ($fields as $field) { ?>
			    				<tr>
			    					<td>
			    						<?php if (isset($fields_check[$field['field']]['check'])) { ?>
			    							<input type="checkbox" name="fields[<?php echo $field['field']; ?>][check]" checked="checked" />
			    						<?php } else { ?>
			    							<input type="checkbox" name="fields[<?php echo $field['field']; ?>][check]" />
			    						<?php } ?>
			    					</td>
			    					<td>
			    						<?php if (isset($fields_check[$field['field']]['key_check'])) { ?>
			    							<input type="checkbox" name="fields[<?php echo $field['field']; ?>][key_check]" checked="checked" />
			    						<?php } else { ?>
			    							<input type="checkbox" name="fields[<?php echo $field['field']; ?>][key_check]" />
			    						<?php } ?>
			    					</td>
			    					<td>
			    						<?php echo $field['field']; ?>
			    						<input type="hidden" name="fields[<?php echo $field['field']; ?>][field]" value="<?php echo $field['field']; ?>" />
			    					</td>
			    					<td>
			    						<?php echo $field['type']; ?>
			    						<input type="hidden" name="fields[<?php echo $field['field']; ?>][type]" value="<?php echo $field['type']; ?>" />
			    					</td>
			    					<td>
			    						<?php echo $field['is_null']; ?>
			    						<input type="hidden" name="fields[<?php echo $field['field']; ?>][is_null]" value="<?php echo $field['is_null']; ?>" />
			    					</td>
			    					<td>
			    						<?php echo $field['key']; ?>
			    						<input type="hidden" name="fields[<?php echo $field['field']; ?>][key]" value="<?php echo $field['key']; ?>" />
			    					</td>
			    					<td>
			    						<?php echo $field['default_value']; ?>
			    						<input type="hidden" name="fields[<?php echo $field['field']; ?>][default_value]" value="<?php echo $field['default_value']; ?>" />
			    					</td>
			    					<td>
			    						<?php echo $field['extra']; ?>
			    						<input type="hidden" name="fields[<?php echo $field['field']; ?>][extra]" value="<?php echo $field['extra']; ?>" />
			    					</td>
			    				</tr>
			    			<?php }?>
			    		<?php }?>
			    		</tbody>
			    	</table>
			    	
			    	<table>
			    		<tr>
			    			<td>
			    				<label for="module"><?php echo $text_module;?></label>
			    			</td>
			    			<td>
								<input type="text" name="module" value="<?php echo $module; ?>"/>
			    				<?php if ($error_module) { ?>
                					<span class="error"><?php echo $error_module; ?></span>
                				<?php } ?>			    				
			    			</td>
			    		</tr>
			    		<tr>
			    			<td>
			    				<label for="delete_table"><?php echo $text_delete_table;?></label>
			    			</td>
			    			<td>
			    				<?php if ($delete_table) { ?>
									<input type="checkbox" name="delete_table" value="true" checked="checked" />
								<?php } else { ?>
									<input type="checkbox" name="delete_table" value="true"/>
								<?php } ?>
			    			</td>
			    		</tr>
			    		<tr>
			    			<td>
			    				<label for="search_check"><?php echo $text_search_check;?></label>
			    			</td>
			    			<td>
			    				<?php if ($search_check) { ?>
									<input type="checkbox" name="search_check" value="true" checked="checked" />
								<?php } else { ?>
									<input type="checkbox" name="search_check" value="true"/>
								<?php } ?>
			    			</td>
			    		</tr>
			    		<tr>
			    			<td>
			    				<label for="insert_check"><?php echo $text_insert_check;?></label>
			    			</td>
			    			<td>
			    				<?php if ($insert_check) { ?>
									<input type="checkbox" name="insert_check" value="true" checked="checked" />
								<?php } else { ?>
									<input type="checkbox" name="insert_check" value="true"/>
								<?php } ?>
			    			</td>
			    		</tr>
			    		<tr>
			    			<td>
			    				<label for="delete_check"><?php echo $text_delete_check;?></label>
			    			</td>
			    			<td>
			    				<?php if ($delete_check) { ?>
									<input type="checkbox" name="delete_check" value="true" checked="checked" />
								<?php } else { ?>
									<input type="checkbox" name="delete_check" value="true"/>
								<?php } ?>
			    			</td>
			    		</tr>
			    		<tr>
			    			<td>
			    				<label for="pagination_check"><?php echo $text_pagination_check;?></label>
			    			</td>
			    			<td>
			    				<?php if ($pagination_check) { ?>
									<input type="checkbox" name="pagination_check" value="true" checked="checked" />
								<?php } else { ?>
									<input type="checkbox" name="pagination_check" value="true"/>
								<?php } ?>
								<input type="text" name="pagination_limit" value="<?php echo $pagination_limit; ?>" />
			    			</td>
			    		</tr>
			    	</table>  	
		    	</div>
		    	<div id="tab-insert" class="vtabs-content">
		    		<p><?php echo $text_insert_description; ?></p>
		    		<table id="fields_insert" class="list">
		    			<thead>
					    	<tr>
						    	<td></td>
						    	<td><?php echo $column_field; ?></td>
						    	<td><?php echo $column_type; ?></td>
						    	<td><?php echo $column_null; ?></td>
						    	<td><?php echo $column_key; ?></td>
						    	<td><?php echo $column_default; ?></td>
						    	<td><?php echo $column_extra; ?></td>
					    	</tr>
					    </thead>
					   	<tbody>
		    			<?php if (isset($fields)) {?>
    				    	<?php foreach ($fields as $field) { ?>
    				    		<tr>
    				    			<td>
    				    				<?php if (isset($fields_check[$field['field']]['field_insert_check'])) { ?>
    				    					<input type="checkbox" name="fields[<?php echo $field['field']; ?>][field_insert_check]" checked="checked" />
    				    				<?php } else { ?>
    				    					<input type="checkbox" name="fields[<?php echo $field['field']; ?>][field_insert_check]" />
    				    				<?php } ?>
    				    			</td>
    				    			<td>
    				    				<?php echo $field['field']; ?>
    				    			</td>
    				    			<td>
    				    				<?php echo $field['type']; ?>
    				    			</td>
    				    			<td>
    				    				<?php echo $field['is_null']; ?>
    				    			</td>
    				    			<td>
    				    				<?php echo $field['key']; ?>
    				    			</td>
    				    			<td>
    				    				<?php echo $field['default_value']; ?>
    				    			</td>
    				    			<td>
    				    				<?php echo $field['extra']; ?>
    				    			</td>
    				    		</tr>
    				    	<?php }?>
    				    <?php }?>
    				   	</tbody>
					</table>
			    	<?php if ($error_fields_insert) { ?>
                		<span class="error"><?php echo $error_fields_insert; ?></span>
                	<?php } ?>
		    	</div>
		    	<div id="tab-search" class="vtabs-content">
		    		<p><?php echo $text_search_description; ?></p>
		    		<table id="fields_search" class="list">
		    			<thead>
					    	<tr>
						    	<td></td>
						    	<td><?php echo $column_field; ?></td>
						    	<td><?php echo $column_type; ?></td>
						    	<td><?php echo $column_null; ?></td>
						    	<td><?php echo $column_key; ?></td>
						    	<td><?php echo $column_default; ?></td>
						    	<td><?php echo $column_extra; ?></td>
					    	</tr>
					    </thead>
					    <tbody>
		    			<?php if (isset($fields)) { ?>
    				    	<?php foreach ($fields as $field) { ?>
    				    		<tr>
    				    			<td>
    				    				<?php if (isset($fields_check[$field['field']]['field_search_check'])) { ?>
    				    					<input type="checkbox" name="fields[<?php echo $field['field']; ?>][field_search_check]" checked="checked" />
    				    				<?php } else { ?>
    				    					<input type="checkbox" name="fields[<?php echo $field['field']; ?>][field_search_check]" />
    				    				<?php } ?>
    				    			</td>
    				    			<td>
    				    				<?php echo $field['field']; ?>
    				    			</td>
    				    			<td>
    				    				<?php echo $field['type']; ?>
    				    			</td>
    				    			<td>
    				    				<?php echo $field['is_null']; ?>
    				    			</td>
    				    			<td>
    				    				<?php echo $field['key']; ?>
    				    			</td>
    				    			<td>
    				    				<?php echo $field['default_value']; ?>
    				    			</td>
    				    			<td>
    				    				<?php echo $field['extra']; ?>
    				    			</td>
    				    		</tr>
    				    	<?php }?>
    				    <?php }?>
    				   	</tbody>
					</table>
					<?php if ($error_fields_search) { ?>
                		<span class="error"><?php echo $error_fields_search; ?></span>
                	<?php } ?>
		    	</div>
		    	<div id="tab-relationship" class="vtabs-content">
		    		<p><?php echo $text_relationship_description; ?></p>
		    		<table id="fields_relationship">
		    			<?php $count_relationships = 0; ?>
		    			<?php if (isset($relationships)) { ?>
			    			<?php foreach ($relationships as $index => $relationship) { ?>
			    				<tr>
			    					<td>
					    				<select name="relationship[<?php echo index; ?>][foreignkey]">
					    					<?php foreach ($fields as $field) { ?>
					    						<?php if ($relationship['field'] == $field['field']) { ?>
					    							<option value="<?php echo $field['field']; ?>" selected="selected"><?php echo $field['field']; ?></option>
					    						<?php } else { ?>
					    							<option value="<?php echo $field['field']; ?>"><?php echo $field['field']; ?></option>
					    						<?php } ?>
					    					<?php }?>
					    				</select>
					    				<select name="relationship[<?php echo index; ?>][type]">
					    					<?php if ($relationship['type'] == 1) { ?>
					    						<option value="1" selected="selected"><?php echo $text_relationship_one_to_many; ?></option>
					    					<?php } else { ?>
					    						<option value="1"><?php echo $text_relationship_one_to_many; ?></option>
					    					<?php }?>
					    					<?php if ($relationship['type'] == 2) { ?>
					    						<option value="2" selected="selected"><?php echo $text_relationship_many_to_one; ?></option>
					    					<?php } else { ?>
					    						<option value="2"><?php echo $text_relationship_many_to_one; ?></option>
					    					<?php }?>
					    					<?php if ($relationship['type'] == 3) { ?>
					    						<option value="3" selected="selected"><?php echo $text_relationship_many_to_many; ?></option>
					    					<?php } else { ?>
					    						<option value="3"><?php echo $text_relationship_many_to_many; ?></option>
					    					<?php }?>
					    				</select>
				    				</td>
			    				</tr>
			    				<?php $count_relationships++; ?>
			    			<?php }?>
			    		<?php }?>
		    		</table>
		    		<a id="add_relationship" class="button"><?php echo $button_add_relationship; ?></a>
		    	</div>
	    	</form>
	    </div>
	</div>
</div>
<?php echo $footer; ?>

<script type="text/javascript">
<!--
	$('#databases').change(function() {
		database_id = $('select[name=\'database_id\']').val();
		if (database_id != 0) {
			$.ajax({
				type: 'POST',
				url: '<?php echo $action_tables; ?>',
				data: 'database_id=' + $('select[name=\'database_id\']').val(),
				dataType: 'json',	
				success: function(json) {
					$('#tables').empty();
					$('#tables').append('<option value="0"><?php echo $text_select; ?></option>');
					$.each(json, function() {
						$('#tables').append('<option value="' + this.id + '">' + this.name + '</option>');
					});
					$('#tables').prop('disabled', false);
				}
			});
		} else {
			$('#tables').empty();
			$('#tables').append('<option value="0"><?php echo $text_select; ?></option>');
			$('#tables').prop('disabled', true);
		}
	});

	$('#tables').change(function() {
		$.ajax({
			type: 'POST',
			url: '<?php echo $action_fields; ?>',
			data: 'table_id=' + $('select[name=\'table_id\']').val(),
			dataType: 'json',	
			success: function(json) {

				$('#fields tbody').empty();
				$('#fields_insert tbody').empty();
				$('#fields_search tbody').empty();
				$('#fields_relationship').empty();
				
				$.each(json, function() {
					$('#fields tbody').append(
						'<tr>' +
						'<td><input type="checkbox" name="fields[' + this.field + '][check]" /></td>' +
						'<td><input type="checkbox" name="fields[' + this.field + '][key_check]" /></td>' +
						'<td>' + 
							this.field +
							'<input type="hidden" name="fields[' + this.field + '][field]" value="' + this.field + '" />' + 
						'</td>' + 
						'<td>' + 
							this.type + 
							'<input type="hidden" name="fields[' + this.field + '][type]" value="' + this.type + '" />' +
						'</td>' +
						'<td>' + 
							this.is_null + 
							'<input type="hidden" name="fields[' + this.field + '][is_null]" value="' + this.is_null + '" />' +
						'</td>' + 
						'<td>' + 
							this.key + 
							'<input type="hidden" name="fields[' + this.field + '][key]" value="' + this.key + '" />' +
						'</td>' + 
						'<td>' + 
							this.default_value + 
							'<input type="hidden" name="fields[' + this.field + '][default_value]" value="' + this.default_value + '" />' + 
						'</td>' + 
						'<td>' + 
							this.extra + 
							'<input type="hidden" name="fields[' + this.field + '][extra]" value="' + this.extra + '" />' +
						'</td>' + 
						'</tr>'
					);

					$('#fields_insert tbody').append(
						'<tr>' +
						'<td><input type="checkbox" name="fields[' + this.field + '][field_insert_check]" /></td>' +
						'<td>' + 
							this.field +
						'</td>' + 
						'<td>' + 
							this.type + 
						'</td>' +
						'<td>' + 
							this.is_null + 
						'</td>' + 
						'<td>' + 
							this.key + 
						'</td>' + 
						'<td>' + 
							this.default_value +  
						'</td>' + 
						'<td>' + 
							this.extra + 
						'</td>' + 
						'</tr>'
					);
					
					$('#fields_search tbody').append(
						'<tr>' +
						'<td><input type="checkbox" name="fields[' + this.field + '][field_search_check]" /></td>' +
						'<td>' + 
							this.field +
						'</td>' + 
						'<td>' + 
							this.type + 
						'</td>' +
						'<td>' + 
							this.is_null + 
						'</td>' + 
						'<td>' + 
							this.key + 
						'</td>' + 
						'<td>' + 
							this.default_value +  
						'</td>' + 
						'<td>' + 
							this.extra + 
						'</td>' + 
						'</tr>'
					);
				});
			}
		});
	});

	var count_relationships = <?php echo $count_relationships; ?>;
	$('#add_relationship').click(function () {
		$.ajax({
			type: 'POST',
			url: '<?php echo $action_fields; ?>',
			data: 'table_id=' + $('select[name=\'table_id\']').val(),
			dataType: 'json',	
			success: function(json) {
				options = '';
				$.each(json, function() {
					options += '<option value="' + this.field + '">' + this.field + '</option>';
				});

				$('#fields_relationship').append(
				'<tr id="relationshiop_' + count_relationships + '">' + 
						'<td>' +
						'<select name="relationship[' + count_relationships + '][foreignkey]">' +
							options +
						'</select>' +
					'</td>' +
					'<td>' +
						'<select name="relationship[' + count_relationships + '][type]">' +
    						'<option value="1"><?php echo $text_relationship_one_to_many; ?></option>' +
    						'<option value="2"><?php echo $text_relationship_many_to_one; ?></option>' +
    						'<option value="3"><?php echo $text_relationship_many_to_many; ?></option>' +
    					'</select>' +
    				'</td>' +
    				'<td>' +
						'<select class="relationship_databases" name="relationship[' + count_relationships + '][database]">' +
		    				'<option value="0"><?php echo $text_select; ?></option>' +
		    				<?php foreach ($databases as $database) { ?>
              					'<option value="<?php echo $database['id']; ?>"><?php echo $database['name']; ?></option>' +
              				<?php } ?>
		    			'</select>' +
		    		'</td>' +
		    		'<td>' +
		    			'<select class="relationship_tables" name="relationship[' + count_relationships + '][table_id]" disabled="disabled">' +
		    				'<option value="0"><?php echo $text_select; ?></option>' +
		    			'</select>' +
		    			'<input type="hidden" class="relationship_table_name" name="relationship[' + count_relationships + '][table_name]" value="" />' +
		    		'</td>' +
		    		'<td>' +
		    			'<select class="relationship_fields" name="relationship[' + count_relationships + '][primarykey_id]" disabled="disabled">' +
		    				'<option value="0"><?php echo $text_select; ?></option>' +
		    			'</select>' +
		    			'</br>' +
		    			'<select class="relationship_fields" name="relationship[' + count_relationships + '][primarykey_label]" disabled="disabled">' +
	    					'<option value="0"><?php echo $text_select; ?></option>' +
	    				'</select>' +
					'</td>' +
					'<td>' +
						'<a class="remove_relationship" class="button"><?php echo $button_remove_relationship; ?></a>' + 
					'</td>' +
				'</tr>');
				
				count_relationships++;
			}
		});
	});

	$("body").on("click", ".remove_relationship", function() {
		tr = $(this).parent().parent();
		tr.remove();
	});
		
	$("body").on("change", ".relationship_databases", function() {
		tables = $(this).parent().parent().find('.relationship_tables');
		database_id = $(this).val();
		if (database_id != 0) {
			$.ajax({
				type: 'POST',
				url: '<?php echo $action_tables; ?>',
				data: 'database_id=' + database_id,
				dataType: 'json',	
				success: function(json) {
					tables.empty();
					tables.append('<option value="0"><?php echo $text_select; ?></option>');
					$.each(json, function() {
						tables.append('<option value="' + this.id + '">' + this.name + '</option>');
					});
					tables.prop('disabled', false);
				}
			});
		} else {
			tables.empty();
			tables.append('<option value="0"><?php echo $text_select; ?></option>');
			tables.prop('disabled', true);
		}
	});

	$("body").on("change", ".relationship_tables", function() {
		fields = $(this).parent().parent().find('.relationship_fields');
		table_hidden = $(this).parent().find('.relationship_table_name');
		table_id = $(this).val();
		table_name = $(this).find($("option:selected")).text();
		if (database_id != 0) {
			$.ajax({
				type: 'POST',
				url: '<?php echo $action_fields; ?>',
				data: 'table_id=' + table_id,
				dataType: 'json',	
				success: function(json) {
					fields.empty();
					fields.append('<option value="0"><?php echo $text_select; ?></option>');
					$.each(json, function() {
						fields.append('<option value="' + this.field + '">' + this.field + '</option>');
					});
					fields.prop('disabled', false);
					table_hidden.val(table_name);
				}
			});
		} else {
			fields.empty();
			fields.append('<option value="0"><?php echo $text_select; ?></option>');
			fields.prop('disabled', true);
		}
	});
//-->
</script>

<script type="text/javascript"><!--
$('.vtabs a').tabs();
//--></script>