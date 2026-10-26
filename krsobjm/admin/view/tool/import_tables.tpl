<?php echo $header; ?>
<div id="content">
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb): ?>
		<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php endforeach; ?>
	</div>
	<?php if ($success): ?>
	<div class="success"><?php echo $success; ?></div>
	<?php endif; ?>
	<div class="box">
		<div class="heading">
			<h1><?php echo $heading_title; ?></h1>
		</div>
		<div class="content">
			<div class="vtabs">
				<a href="#tab-database"><?php echo $tab_database; ?></a>
				<a href="#tab-table"><?php echo $tab_table; ?></a>
			</div>
			<div id="tab-database" class="vtabs-content">
				<form id="form_database" action="<?php echo $action_insert_database; ?>" method="post">
					<table>
						<tr>
							<td><label for="type"><?php echo $text_type; ?></label></td>
							<td>
								<input type="text" name="type" value="<?php echo $type; ?>" />
								<?php if ($error_type): ?><span class="error"><?php echo $error_type; ?></span><?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><label for="name"><?php echo $text_name; ?></label></td>
							<td>
								<input type="text" name="name" value="<?php echo $name; ?>" />
								<?php if ($error_name_database): ?><span class="error"><?php echo $error_name_database; ?></span><?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><label for="ip"><?php echo $text_ip; ?></label></td>
							<td>
								<input type="text" name="ip" value="<?php echo $ip; ?>" />
								<?php if ($error_ip): ?><span class="error"><?php echo $error_ip; ?></span><?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><label for="port"><?php echo $text_port; ?></label></td>
							<td><input type="text" name="port" value="<?php echo $port; ?>" /></td>
						</tr>
						<tr>
							<td><label for="username"><?php echo $text_username; ?></label></td>
							<td>
								<input type="text" name="username" value="<?php echo $username; ?>" />
								<?php if ($error_username): ?><span class="error"><?php echo $error_username; ?></span><?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><label for="password"><?php echo $text_password; ?></label></td>
							<td><input type="text" name="password" value="<?php echo $password; ?>" /></td>
						</tr>
						<tr>
							<td><label for="module_id"><?php echo $text_module_id; ?></label></td>
							<td><input type="text" name="module_id" value="<?php echo $module_id; ?>" /></td>
						</tr>
						<tr>
							<td colspan="2"><a class="button" onclick="$('#form_database').submit();"><?php echo $button_insert; ?></a></td>
						</tr>
					</table>
				</form>
			</div>
			<div id="tab-table" class="vtabs-content">
				<form id="form_table" action="<?php echo $action_insert_table; ?>" method="post">
					<table>
						<tr>
							<td><label for="name"><?php echo $text_name; ?></label></td>
							<td>
								<input type="text" name="name" value="<?php echo $name; ?>" />
								<?php if ($error_name_table): ?><span class="error"><?php echo $error_name_table; ?></span><?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><label for="database_id"><?php echo $text_database; ?></label></td>
							<td>
								<select id="databases" name="database_id">
									<option value="0"><?php echo $text_select; ?></option>
									<?php foreach ($databases as $database): ?>
									<option value="<?php echo $database['id']; ?>"<?php echo ($database['id'] == $database_id) ? ' selected="selected"' : ''; ?>><?php echo $database['name']; ?></option>
									<?php endforeach; ?>
								</select>
								<?php if ($error_database_id): ?><span class="error"><?php echo $error_database_id; ?></span><?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2"><a class="button" onclick="$('#form_table').submit();"><?php echo $button_insert; ?></a></td>
						</tr>
					</table>
				</form>
			</div>
		</div>
	</div>
</div>
<?php echo $footer; ?>

<script type="text/javascript">
$('.vtabs a').tabs();
</script>
