<?php echo $header; ?>
<div id="content">
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb): ?>
		<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php endforeach; ?>
	</div>
	<?php if ($readonly_warning): ?>
	<div class="warning"><?php echo $readonly_warning; ?></div>
	<?php endif; ?>
	<?php if ($error_warning): ?>
	<div class="warning"><?php echo $error_warning; ?></div>
	<?php endif; ?>
	<?php if ($success): ?>
	<div class="success"><?php echo $success; ?></div>
	<?php endif; ?>
	<div class="box">
		<div class="heading">
			<h1><?php echo $heading_title; ?></h1>
			<?php if ($writable): ?>
			<div class="buttons">
				<a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a>
			</div>
			<?php endif; ?>
		</div>
		<div class="content">
			<div class="vtabs">
				<?php foreach ($sections as $section): ?>
				<a href="#tab-<?php echo $section['id']; ?>"><?php echo $section['title']; ?></a>
				<?php endforeach; ?>
				<a href="#tab-editor" id="link-editor"><?php echo $tab_editor; ?></a>
				<a href="#tab-backup"><?php echo $tab_backup; ?></a>
			</div>

			<form action="<?php echo $action; ?>" method="post" id="form">
				<?php foreach ($sections as $section): ?>
				<div id="tab-<?php echo $section['id']; ?>" class="vtabs-content">
					<table class="form">
						<?php foreach ($section['fields'] as $field): ?>
						<tr>
							<td>
								<?php echo $field['label']; ?><br />
								<small class="muted"><?php echo $field['key']; ?></small>
								<?php if ($field['help']): ?><br /><small class="muted"><?php echo $field['help']; ?></small><?php endif; ?>
							</td>
							<td>
								<?php $disabled = $writable ? '' : ' disabled="disabled"'; ?>
								<?php if ($field['type'] === 'bool'): ?>
								<select name="config[<?php echo $field['name']; ?>]"<?php echo $disabled; ?>>
									<?php if ($field['optional']): ?>
									<option value=""<?php echo ($field['value'] === '') ? ' selected="selected"' : ''; ?>><?php echo $text_default_value; ?></option>
									<?php endif; ?>
									<option value="1"<?php echo ($field['value'] === '1') ? ' selected="selected"' : ''; ?>><?php echo $text_yes; ?></option>
									<option value="0"<?php echo ($field['value'] === '0' || (!$field['optional'] && $field['value'] === '')) ? ' selected="selected"' : ''; ?>><?php echo $text_no; ?></option>
								</select>
								<?php elseif ($field['type'] === 'select'): ?>
								<select name="config[<?php echo $field['name']; ?>]"<?php echo $disabled; ?>>
									<?php foreach ($field['options'] as $option): ?>
									<option value="<?php echo $option['value']; ?>"<?php echo ($option['value'] === $field['value']) ? ' selected="selected"' : ''; ?>><?php echo $option['text']; ?></option>
									<?php endforeach; ?>
								</select>
								<?php elseif ($field['type'] === 'password'): ?>
								<input type="password" name="config[<?php echo $field['name']; ?>]" value="" placeholder="<?php echo $text_password_keep; ?>" autocomplete="new-password" size="40"<?php echo $disabled; ?> />
								<?php else: ?>
								<input type="text" name="config[<?php echo $field['name']; ?>]" value="<?php echo $field['value']; ?>"<?php echo ($field['type'] === 'int') ? ' size="8"' : ' size="60"'; ?><?php echo $field['optional'] ? ' placeholder="' . $text_default_value . '"' : ''; ?><?php echo $disabled; ?> />
								<?php endif; ?>
								<?php if ($field['error']): ?><span class="error"><?php echo $field['error']; ?></span><?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</table>
				</div>
				<?php endforeach; ?>
			</form>

			<div id="tab-editor" class="vtabs-content">
				<?php if ($error_editor): ?>
				<div class="warning"><?php echo $error_editor; ?></div>
				<?php endif; ?>
				<?php if ($backup_loaded): ?>
				<div class="attention"><?php echo $backup_loaded; ?></div>
				<?php endif; ?>
				<p class="muted"><?php echo $text_editor_help; ?></p>
				<form action="<?php echo $action_editor; ?>" method="post" id="form-editor">
					<textarea name="config_text" rows="30" spellcheck="false" style="width: 100%; font-family: monospace; font-size: 13px; white-space: pre; tab-size: 4;"<?php echo $writable ? '' : ' readonly="readonly"'; ?>><?php echo $editor_text; ?></textarea>
					<?php if ($writable): ?>
					<p><a onclick="$('#form-editor').submit();" class="button"><?php echo $button_save_editor; ?></a></p>
					<?php endif; ?>
				</form>
			</div>

			<div id="tab-backup" class="vtabs-content">
				<p class="muted"><?php echo $text_backup_help; ?></p>
				<table class="list">
					<thead>
						<tr>
							<td class="left"><?php echo $column_backup; ?></td>
							<td class="left"><?php echo $column_size; ?></td>
							<td class="right"><?php echo $column_action; ?></td>
						</tr>
					</thead>
					<tbody>
						<?php if ($backups): ?>
						<?php foreach ($backups as $backup): ?>
						<tr>
							<td class="left"><?php echo $backup['date']; ?></td>
							<td class="left"><?php echo $backup['size']; ?></td>
							<td class="right">[ <a href="<?php echo $backup['load']; ?>"><?php echo $button_load_backup; ?></a> ]</td>
						</tr>
						<?php endforeach; ?>
						<?php else: ?>
						<tr><td class="center" colspan="3"><?php echo $text_no_backups; ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<?php echo $footer; ?>

<script type="text/javascript">
$('.vtabs a').tabs();
<?php if ($open_editor): ?>
$('#link-editor').click();
<?php endif; ?>
</script>
