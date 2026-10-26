<?php echo $header; ?>
<div id="content">
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb): ?>
		<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php endforeach; ?>
	</div>
	<?php if ($error_warning): ?>
	<div class="warning"><?php echo $error_warning; ?></div>
	<?php endif; ?>
	<?php if ($success): ?>
	<div class="success"><?php echo $success; ?></div>
	<?php endif; ?>
	<div class="box">
		<div class="heading">
			<h1><?php echo $heading_title; ?></h1>
			<div class="buttons">
				<a href="<?php echo $insert; ?>" class="button"><?php echo $button_insert; ?></a>
				<a onclick="$('#form').submit();" class="button"><?php echo $button_delete; ?></a>
			</div>
		</div>
		<div class="content">
			<p class="muted"><?php echo $text_help_list; ?></p>
			<form action="<?php echo $delete; ?>" method="post" enctype="multipart/form-data" id="form">
				<table class="list">
					<thead>
						<tr>
							<td width="1" style="text-align: center;"><input type="checkbox" onclick="$('input[name*=\'selected\']').attr('checked', this.checked);" /></td>
							<td class="left"><?php echo $column_name; ?></td>
							<td class="left"><?php echo $column_code; ?></td>
							<td class="left"><?php echo $column_directory; ?></td>
							<td class="left"><?php echo $column_files; ?></td>
							<td class="left"><?php echo $column_status; ?></td>
							<td class="right"><?php echo $column_action; ?></td>
						</tr>
					</thead>
					<tbody>
						<?php if ($languages): ?>
						<?php foreach ($languages as $language): ?>
						<tr>
							<td style="text-align: center;"><input type="checkbox" name="selected[]" value="<?php echo $language['language_id']; ?>"<?php echo $language['selected'] ? ' checked="checked"' : ''; ?> /></td>
							<td class="left"><?php echo $language['name']; ?></td>
							<td class="left"><?php echo $language['code']; ?></td>
							<td class="left"><?php echo $language['directory']; ?></td>
							<td class="left"><?php echo $language['files']; ?></td>
							<td class="left"><?php echo $language['status']; ?></td>
							<td class="right">
								<?php foreach ($language['action'] as $action): ?>
								[ <a href="<?php echo $action['href']; ?>"><?php echo $action['text']; ?></a> ]
								<?php endforeach; ?>
							</td>
						</tr>
						<?php endforeach; ?>
						<?php else: ?>
						<tr>
							<td class="center" colspan="7"><?php echo $text_no_results; ?></td>
						</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</form>
		</div>
	</div>
</div>
<?php echo $footer; ?>
