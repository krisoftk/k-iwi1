<?php echo $header; ?>
<div id="content">
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb): ?>
		<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php endforeach; ?>
	</div>
	<?php if ($notice): ?>
	<div class="warning"><?php echo $notice; ?></div>
	<?php endif; ?>
	<?php if ($error_warning): ?>
	<div class="warning"><?php echo $error_warning; ?></div>
	<?php endif; ?>
	<?php if ($success): ?>
	<div class="success"><?php echo $success; ?></div>
	<?php endif; ?>
	<div class="box">
		<div class="heading">
			<h1><?php echo $heading_title; ?> <small class="muted"><?php echo $database; ?></small></h1>
		</div>
		<div class="content">
			<p class="muted"><?php echo $text_help; ?></p>

			<h2 class="backup-section"><?php echo $text_backups; ?></h2>
			<table class="list">
				<thead>
					<tr>
						<td class="left"><?php echo $column_date; ?></td>
						<td class="left"><?php echo $column_kind; ?></td>
						<td class="left"><?php echo $column_tables; ?></td>
						<td class="left"><?php echo $column_size; ?></td>
						<td class="right"><?php echo $column_action; ?></td>
					</tr>
				</thead>
				<tbody>
					<?php if ($backups): ?>
					<?php foreach ($backups as $backup): ?>
					<tr>
						<td class="left"><?php echo $backup['date']; ?><br /><small class="muted"><?php echo $backup['name']; ?></small></td>
						<td class="left"><?php echo $backup['kind']; ?></td>
						<td class="left"><?php echo $backup['tables']; ?> <small class="muted"><?php echo $backup['database']; ?></small></td>
						<td class="left"><?php echo $backup['size']; ?></td>
						<td class="right backup-actions">
							[ <a href="<?php echo $backup['download']; ?>"><?php echo $button_download; ?></a> ]
							<?php if ($can_work): ?>
							[ <a href="<?php echo $backup['restore']; ?>"><?php echo $button_restore; ?></a> ]
							<form action="<?php echo $action_delete; ?>" method="post" onsubmit="return confirm('<?php echo htmlspecialchars(addslashes($text_confirm_delete), ENT_QUOTES, 'UTF-8'); ?>');">
								<input type="hidden" name="file" value="<?php echo $backup['name']; ?>" />
								[ <button type="submit" class="link-button"><?php echo $button_delete; ?></button> ]
							</form>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
					<?php else: ?>
					<tr><td class="center" colspan="5"><?php echo $text_no_backups; ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ($can_work): ?>
			<h2 class="backup-section"><?php echo $text_create; ?></h2>
			<p class="muted"><?php echo $text_create_help; ?></p>
			<form action="<?php echo $action_create; ?>" method="post" id="form-create">
				<table class="list">
					<thead>
						<tr>
							<td width="1" style="text-align: center;"><input type="checkbox" onclick="$('#form-create input[name*=\'tables\']').prop('checked', this.checked);" /></td>
							<td class="left"><?php echo $column_table; ?></td>
							<td class="right"><?php echo $column_rows; ?></td>
							<td class="right"><?php echo $column_size; ?></td>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($tables as $table): ?>
						<tr>
							<td style="text-align: center;"><input type="checkbox" name="tables[]" value="<?php echo $table['name']; ?>" id="table-<?php echo $table['name']; ?>"<?php echo $table['checked'] ? ' checked="checked"' : ''; ?> /></td>
							<td class="left"><label for="table-<?php echo $table['name']; ?>"><?php echo $table['name']; ?></label></td>
							<td class="right"><?php echo $table['rows']; ?></td>
							<td class="right"><?php echo $table['size']; ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p><button type="submit" class="button"><?php echo $button_create; ?></button></p>
			</form>

			<h2 class="backup-section"><?php echo $text_upload; ?></h2>
			<p class="muted"><?php echo $text_upload_help; ?> <?php echo $text_upload_limit; ?></p>
			<form action="<?php echo $action_upload; ?>" method="post" enctype="multipart/form-data">
				<p>
					<input type="file" name="backup" accept=".sql,application/sql,text/plain" />
					<button type="submit" class="button"><?php echo $button_upload; ?></button>
				</p>
			</form>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php echo $footer; ?>
