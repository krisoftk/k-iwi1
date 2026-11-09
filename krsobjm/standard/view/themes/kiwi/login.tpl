<?php if (!$logged) { ?>
<div class="kw-login">
	<div class="kw-login-card">
		<?php if ($warnig_timeout) { ?>
		<div class="alert alert-warning" role="alert"><strong><?php echo $warnig; ?></strong> <?php echo $warnig_timeout; ?></div>
		<?php } ?>

		<div class="kw-login-head">
			<span class="kw-login-icon" aria-hidden="true"><i class="fa fa-user"></i></span>
			<p class="kw-muted"><?php echo $text_login_hint; ?></p>
		</div>

		<!-- submitted through KAJAX, Enter included -->
		<form id="form_login" onsubmit="actionKAJAX('<?php echo $login; ?>', $('#form_login').serialize(), null, 'post'); return false;">
			<div class="form-group">
				<label for="login-user"><?php echo $entry_username; ?></label>
				<input class="form-control" type="text" id="login-user" name="user" value="<?php echo $user; ?>" autocomplete="username" autofocus />
			</div>
			<div class="form-group">
				<label for="login-password"><?php echo $entry_password; ?></label>
				<input class="form-control" type="password" id="login-password" name="password" value="<?php echo $password; ?>" autocomplete="current-password" />
			</div>

			<?php if (isset($error)) { ?>
			<div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
			<?php } ?>

			<button class="btn btn-primary btn-block" id="login" type="submit"><?php echo $button_login; ?></button>
		</form>
	</div>
</div>
<?php } else { ?>
<div class="page-header">
	<div class="container-fluid">
		<h1><?php echo $page_header; ?></h1>
	</div>
</div>

<div class="container-fluid">
	<div class="panel panel-default kw-welcome">
		<div class="panel-body">
			<span class="kw-avatar kw-avatar-lg" aria-hidden="true"><?php echo htmlspecialchars(preg_match('/\S/u', $user['FIRSTNAME'] . $user['USERNAME'], $initial) ? $initial[0] : '', ENT_QUOTES, 'UTF-8'); ?></span>
			<div>
				<strong><?php echo htmlspecialchars($user['FIRSTNAME'] . ' ' . $user['LASTNAME'], ENT_QUOTES, 'UTF-8'); ?></strong>
				<div class="kw-muted"><?php echo htmlspecialchars($user['USERNAME'], ENT_QUOTES, 'UTF-8'); ?></div>
			</div>
			<form id="form_login">
				<button class="btn btn-default" id="login" type="button" onclick="actionKAJAX('<?php echo $logout; ?>', null, null, 'post');"><?php echo $button_logout; ?></button>
			</form>
		</div>
	</div>
</div>
<?php } ?>
