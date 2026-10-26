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
				<a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a>
				<a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a>
			</div>
		</div>
		<div class="content">
			<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
				<table class="form">
					<tr>
						<td><span class="required">*</span> <?php echo $entry_name; ?></td>
						<td>
							<input type="text" name="name" value="<?php echo $name; ?>" />
							<?php if ($error_name): ?><span class="error"><?php echo $error_name; ?></span><?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><span class="required">*</span> <?php echo $entry_code; ?><br /><small class="muted"><?php echo $help_code; ?></small></td>
						<td>
							<input type="text" name="code" value="<?php echo $code; ?>" />
							<?php if ($error_code): ?><span class="error"><?php echo $error_code; ?></span><?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><span class="required">*</span> <?php echo $entry_directory; ?><br /><small class="muted"><?php echo $editing ? $help_directory_fixed : $help_directory; ?></small></td>
						<td>
							<?php if ($editing): ?>
							<strong><?php echo $directory; ?></strong>
							<?php else: ?>
							<input type="text" name="directory" value="<?php echo $directory; ?>" />
							<?php endif; ?>
							<?php if ($error_directory): ?><span class="error"><?php echo $error_directory; ?></span><?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><?php echo $entry_locale; ?><br /><small class="muted"><?php echo $help_locale; ?></small></td>
						<td>
							<input type="text" name="locale" value="<?php echo $locale; ?>" size="40" />
							<?php if ($error_locale): ?><span class="error"><?php echo $error_locale; ?></span><?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><?php echo $entry_sort_order; ?></td>
						<td><input type="text" name="sort_order" value="<?php echo $sort_order; ?>" size="3" /></td>
					</tr>
					<tr>
						<td><?php echo $entry_status; ?></td>
						<td>
							<select name="status">
								<option value="1"<?php echo $status ? ' selected="selected"' : ''; ?>><?php echo $text_enabled; ?></option>
								<option value="0"<?php echo !$status ? ' selected="selected"' : ''; ?>><?php echo $text_disabled; ?></option>
							</select>
							<?php if ($error_status): ?><span class="error"><?php echo $error_status; ?></span><?php endif; ?>
						</td>
					</tr>
				</table>
			</form>

			<h2><?php echo $text_pack; ?></h2>
			<?php if ($editing): ?>
			<p class="muted"><?php echo $text_pack_help; ?></p>
			<form action="<?php echo $upload; ?>" method="post" enctype="multipart/form-data" id="form-pack">
				<table class="form">
					<tr>
						<td><?php echo $entry_pack; ?></td>
						<td>
							<a href="<?php echo $download; ?>" class="button"><?php echo $button_download; ?></a>
							&nbsp;
							<input type="file" name="pack" accept=".json,application/json" />
							<a onclick="$('#form-pack').submit();" class="button"><?php echo $button_upload; ?></a>
						</td>
					</tr>
				</table>
			</form>
			<?php else: ?>
			<p class="muted"><?php echo $text_pack_new; ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php echo $footer; ?>
