<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $direction; ?>">

<head><?php echo $header; ?></head>

<!-- Guests only see the login card: the sidebar and the user menu appear
     once logged in (the login reloads the whole page). -->
<body class="kw<?php echo $logged ? '' : ' kw-guest'; ?>">

	<div class="kw-shell">
		<aside class="kw-sidebar" id="kw-sidebar">
			<a class="kw-brand" href="">
				<span class="kw-mark"><?php echo htmlspecialchars(preg_match('/\S/u', strip_tags($heading_title), $initial) ? $initial[0] : 'K', ENT_QUOTES, 'UTF-8'); ?></span>
				<span class="kw-name"><?php echo $heading_title; ?></span>
			</a>
			<nav class="kw-nav" id="menudinamico" aria-label="<?php echo $heading_title; ?>"></nav>
		</aside>

		<div class="kw-main">
			<header class="kw-topbar">
				<button type="button" class="kw-menu-toggle" aria-controls="kw-sidebar" aria-expanded="false">
					<span class="sr-only"><?php echo $text_toggle_navigation; ?></span>
					<i class="fa fa-bars" aria-hidden="true"></i>
				</button>
				<div class="kw-info" id="info"></div>
				<?php if ($logged) { ?>
				<div class="dropdown kw-user">
					<button type="button" class="kw-user-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<span class="kw-avatar" aria-hidden="true"><?php echo htmlspecialchars(preg_match('/\S/u', $user_name, $initial) ? $initial[0] : '', ENT_QUOTES, 'UTF-8'); ?></span>
						<span class="kw-user-name"><?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?></span>
						<i class="fa fa-angle-down" aria-hidden="true"></i>
					</button>
					<ul class="dropdown-menu dropdown-menu-right">
						<li><a href="#" id="link-change-password"><i class="fa fa-key fa-fw"></i> <?php echo $text_change_password; ?></a></li>
						<li><a href="#" onclick="actionKAJAX('<?php echo $action_logout; ?>', null, null, 'post'); return false;"><i class="fa fa-sign-out fa-fw"></i> <?php echo $text_logout; ?></a></li>
					</ul>
				</div>
				<?php } ?>
			</header>

			<main class="kw-body">
				<?php if (!$logged) { ?>
				<div class="kw-guest-brand">
					<span class="kw-mark"><?php echo htmlspecialchars(preg_match('/\S/u', strip_tags($heading_title), $initial) ? $initial[0] : 'K', ENT_QUOTES, 'UTF-8'); ?></span>
					<span class="kw-name"><?php echo $heading_title; ?></span>
				</div>
				<?php } ?>
				<div class="content" id="content"></div>
			</main>

			<?php if ($copyright) { ?>
			<footer class="kw-foot"><?php echo $copyright; ?></footer>
			<?php } ?>
		</div>
	</div>
	<div class="kw-backdrop" aria-hidden="true"></div>

	<!-- Change Password modal: present only once in the shell (this
	     template is loaded once per session, the content of the pages
	     below is then loaded via AJAX into #content) -->
	<div class="modal fade" id="modal-change-password" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title"><?php echo $text_change_password; ?></h4>
				</div>
				<div class="modal-body">
					<div class="alert alert-danger" id="change-password-error" style="display:none;"></div>
					<div class="form-group">
						<label for="change-password-current"><?php echo $entry_current_password; ?></label>
						<input type="password" class="form-control" id="change-password-current" autocomplete="current-password" />
					</div>
					<div class="form-group">
						<label for="change-password-new"><?php echo $entry_new_password; ?></label>
						<input type="password" class="form-control" id="change-password-new" autocomplete="new-password" />
					</div>
					<div class="form-group">
						<label for="change-password-confirm"><?php echo $entry_confirm_password; ?></label>
						<input type="password" class="form-control" id="change-password-confirm" autocomplete="new-password" />
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $button_cancel; ?></button>
					<button type="button" class="btn btn-primary" id="button-conferma-change-password"><?php echo $text_change_password; ?></button>
				</div>
			</div>
		</div>
	</div>

	<?php echo $footer; ?>

	<script type="text/javascript"><!--
		$('#link-change-password').click(function() {
			$('#change-password-current').val('');
			$('#change-password-new').val('');
			$('#change-password-confirm').val('');
			$('#change-password-error').hide().text('');
			$('#modal-change-password').modal('show');
			return false;
		});

		$('#button-conferma-change-password').click(function() {
			var $btn = $(this);
			$btn.prop('disabled', true);

			$.ajax({
				url: '<?php echo $action_change_password; ?>',
				type: 'POST',
				dataType: 'json',
				data: {
					'current_password': $('#change-password-current').val(),
					'new_password': $('#change-password-new').val(),
					'confirm_password': $('#change-password-confirm').val()
				},
				success: function(response) {
					$btn.prop('disabled', false);
					if (response.error) {
						$('#change-password-error').text(response.error).show();
					} else {
						$('#modal-change-password').one('hidden.bs.modal', function() {
							alert(response.success);
						});
						$('#modal-change-password').modal('hide');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					$('#change-password-error').text(<?php echo json_encode(html_entity_decode($error_communication, ENT_QUOTES, 'UTF-8')); ?>).show();
				}
			});

			return false;
		});
	//--></script>
</body>

</html>
