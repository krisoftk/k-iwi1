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
						<td><span class="required">*</span> <?php echo $entry_prog; ?></td>
						<td>
							<select name="prog_id">
								<?php foreach ($progs as $prog): ?>
								<option value="<?php echo $prog['ID']; ?>"<?php echo ($prog_id == $prog['ID']) ? ' selected="selected"' : ''; ?>><?php echo $prog['TITLE'] . '/' . $prog['PROG']; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td><span class="required">*</span> <?php echo $entry_name; ?></td>
						<td>
							<input type="text" name="name" value="<?php echo $name; ?>" />
							<?php if ($error_name): ?><span class="error"><?php echo $error_name; ?></span><?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><span class="required">*</span> <?php echo $entry_url; ?></td>
						<td>
							<input type="text" name="url" value="<?php echo $url; ?>" />
							<?php if ($error_url): ?><span class="error"><?php echo $error_url; ?></span><?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><?php echo $entry_description; ?></td>
						<td><input type="text" name="description" value="<?php echo $description; ?>" /></td>
					</tr>
				</table>
			</form>
		</div>
	</div>
</div>
<?php echo $footer; ?>
