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
			<div class="vtabs">
				<a href="#tab-group"><?php echo $tab_group; ?></a>
				<a href="#tab-prog"><?php echo $tab_prog; ?></a>
			</div>
			<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
				<div id="tab-group" class="vtabs-content">
					<table class="form">
						<tr>
							<td><span class="required">*</span> <?php echo $entry_name; ?></td>
							<td>
								<input type="text" name="name" value="<?php echo $name; ?>" />
								<?php if ($error_name): ?><span class="error"><?php echo $error_name; ?></span><?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><?php echo $entry_modules; ?></td>
							<td>
								<select name="module">
									<?php foreach ($modules as $moduleOption): ?>
									<option value="<?php echo $moduleOption['ID']; ?>"<?php echo ($group_module == $moduleOption['ID']) ? ' selected="selected"' : ''; ?>><?php echo $moduleOption['NAME']; ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</table>
				</div>
				<div id="tab-prog" class="vtabs-content">
					<?php if ($error_progs): ?>
					<span class="error"><?php echo $error_progs; ?></span>
					<?php endif; ?>
					<table class="list">
						<thead>
							<tr>
								<td width="1" style="text-align: center;"></td>
								<td class="left"><?php echo $column_prog_name; ?></td>
								<td class="left"><?php echo $column_prog_menu; ?></td>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($progs as $prog): ?>
							<tr>
								<td style="text-align: center;">
									<input name="progs[]" type="checkbox" value="<?php echo $prog['ID']; ?>"<?php echo in_array($prog['ID'], $group_progs) ? ' checked="checked"' : ''; ?> />
								</td>
								<td class="left">
									<?php echo $prog['TITLE']; ?><br />
									<small class="muted"><?php echo $prog['PROG']; ?></small>
								</td>
								<td class="left">
									<?php if (!empty($prog['MENU_TITLE'])): ?>
									<?php echo $prog['MENU_TITLE']; ?>
									<?php else: ?>
									<small class="muted">&mdash;</small>
									<?php endif; ?>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</form>
		</div>
	</div>
</div>
<?php echo $footer; ?>

<script type="text/javascript">
$('.vtabs a').tabs();
</script>
