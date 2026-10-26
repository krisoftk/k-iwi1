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
				<a href="#tab-user"><?php echo $tab_user; ?></a>
				<a href="#tab-group"><?php echo $tab_group; ?></a>
			</div>
			<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
				<div id="tab-user" class="vtabs-content">
					<table class="form">
						<tr>
							<td><span class="required">*</span> <?php echo $entry_username; ?></td>
							<td>
								<input type="text" name="username" value="<?php echo $username; ?>" />
								<?php if ($error_username): ?><span class="error"><?php echo $error_username; ?></span><?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><span class="required">*</span> <?php echo $entry_firstname; ?></td>
							<td>
								<input type="text" name="firstname" value="<?php echo $firstname; ?>" />
								<?php if ($error_firstname): ?><span class="error"><?php echo $error_firstname; ?></span><?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><span class="required">*</span> <?php echo $entry_lastname; ?></td>
							<td>
								<input type="text" name="lastname" value="<?php echo $lastname; ?>" />
								<?php if ($error_lastname): ?><span class="error"><?php echo $error_lastname; ?></span><?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><?php echo $entry_email; ?></td>
							<td><input type="text" name="email" value="<?php echo $email; ?>" /></td>
						</tr>
						<tr>
							<td><?php echo $entry_password; ?></td>
							<td>
								<input type="password" name="password" value="<?php echo $password; ?>" />
								<?php if ($error_password): ?><span class="error"><?php echo $error_password; ?></span><?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><?php echo $entry_confirm; ?></td>
							<td>
								<input type="password" name="confirm" value="<?php echo $confirm; ?>" />
								<?php if ($error_confirm): ?><span class="error"><?php echo $error_confirm; ?></span><?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><?php echo $entry_modules; ?></td>
							<td>
								<select name="module">
									<?php foreach ($all_modules as $moduleOption): ?>
									<option value="<?php echo $moduleOption['ID']; ?>"<?php echo ($module == $moduleOption['ID']) ? ' selected="selected"' : ''; ?>><?php echo $moduleOption['NAME']; ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<td><?php echo $entry_language; ?></td>
							<td>
								<select name="language">
									<option value=""><?php echo $text_language_default; ?></option>
									<?php foreach ($all_languages as $languageOption): ?>
									<option value="<?php echo $languageOption['code']; ?>"<?php echo ($language === $languageOption['code']) ? ' selected="selected"' : ''; ?>><?php echo $languageOption['name']; ?></option>
									<?php endforeach; ?>
								</select>
								<?php if ($error_language): ?><span class="error"><?php echo $error_language; ?></span><?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><?php echo $entry_status; ?></td>
							<td>
								<select name="status">
									<option value="0"<?php echo !$status ? ' selected="selected"' : ''; ?>><?php echo $text_disabled; ?></option>
									<option value="1"<?php echo $status ? ' selected="selected"' : ''; ?>><?php echo $text_enabled; ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>
				<div id="tab-group" class="vtabs-content">
					<table id="usergroup" class="list">
						<thead>
							<tr>
								<td class="left"><?php echo $entry_usergroup; ?></td>
								<td></td>
							</tr>
						</thead>
						<?php $usergroupRow = 0; ?>
						<?php foreach ($usergroups as $selectedGroupId): ?>
						<tbody id="usergroup-row<?php echo $usergroupRow; ?>">
							<tr>
								<td class="left">
									<select name="usergroups[<?php echo $usergroupRow; ?>]">
										<?php foreach ($all_usergroups as $usergroup): ?>
										<option value="<?php echo $usergroup['ID']; ?>"<?php echo ($selectedGroupId == $usergroup['ID']) ? ' selected="selected"' : ''; ?>><?php echo $usergroup['NAME']; ?> -- <?php echo $usergroup['MODULE_NAME']; ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td class="left"><a onclick="$('#usergroup-row<?php echo $usergroupRow; ?>').remove();" class="button"><?php echo $button_remove; ?></a></td>
							</tr>
						</tbody>
						<?php $usergroupRow++; ?>
						<?php endforeach; ?>
						<tfoot>
							<tr>
								<td>
									<?php if ($error_usergroups): ?><span class="error"><?php echo $error_usergroups; ?></span><?php endif; ?>
								</td>
								<td class="left"><a onclick="addUsergroup();" class="button"><?php echo $button_add_usergroup; ?></a></td>
							</tr>
						</tfoot>
					</table>
				</div>
			</form>
		</div>
	</div>
</div>
<?php echo $footer; ?>

<script type="text/javascript">
var usergroupRow = <?php echo $usergroupRow; ?>;

function addUsergroup() {
	var options = '';

	<?php foreach ($all_usergroups as $usergroup): ?>
	options += '<option value="<?php echo $usergroup['ID']; ?>"><?php echo $usergroup['NAME']; ?> -- <?php echo $usergroup['MODULE_NAME']; ?></option>';
	<?php endforeach; ?>

	var row = '<tbody id="usergroup-row' + usergroupRow + '">' +
		'<tr>' +
		'<td class="left"><select name="usergroups[' + usergroupRow + ']">' + options + '</select></td>' +
		'<td class="left"><a onclick="$(\'#usergroup-row' + usergroupRow + '\').remove();" class="button"><?php echo $button_remove; ?></a></td>' +
		'</tr>' +
		'</tbody>';

	$('#usergroup tfoot').before(row);

	usergroupRow++;
}
</script>

<script type="text/javascript">
$('.vtabs a').tabs();
</script>
