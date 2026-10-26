<?php echo $header; ?>
<div id="content">
	<div class="login-card">
		<div class="brandmark">
			<div class="mark">K</div>
			<div class="name">K-iwi<span><?php echo $text_login; ?></span></div>
		</div>
		<h2><?php echo $heading_title; ?></h2>
		<p class="sub"><?php echo $text_login; ?></p>

		<?php if ($success): ?>
		<div class="success"><?php echo $success; ?></div>
		<?php endif; ?>
		<?php if ($error_warning): ?>
		<div class="warning"><?php echo $error_warning; ?></div>
		<?php endif; ?>

		<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
			<div class="field">
				<label for="username"><?php echo $entry_username; ?></label>
				<input type="text" id="username" name="username" value="<?php echo $username; ?>" />
			</div>
			<div class="field">
				<label for="password"><?php echo $entry_password; ?></label>
				<input type="password" id="password" name="password" value="<?php echo $password; ?>" />
			</div>
			<a onclick="$('#form').submit();" class="button btn-primary"><?php echo $button_login; ?></a>
			<?php if ($redirect): ?>
			<input type="hidden" name="redirect" value="<?php echo $redirect; ?>" />
			<?php endif; ?>
		</form>

		<div class="login-foot">
			<span>K-iwi</span>
			<span>krisoft.it</span>
		</div>
	</div>
</div>
<script type="text/javascript">
$('#form input').on('keydown', function (e) {
	if (e.keyCode === 13) {
		$('#form').submit();
	}
});
</script>
<?php echo $footer; ?>
