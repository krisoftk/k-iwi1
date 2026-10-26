<?php if(!$logged) { ?>
	
	<?php if ($warnig_timeout) { ?>
		<div class="alert alert-warning alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"><?php echo $button_close; ?></span></button>
  			<strong><?php echo $warnig; ?></strong> <?php echo $warnig_timeout; ?>
		</div>
	<?php } ?>
	
	<div class="row">
		<div class="col-lg-6 col-lg-offset-3 text-center">
			<img src="<?php echo makePath('img/user.png')?>" width="128" height="128" title="<?php echo $text_login_hint; ?>">
		</div>
	</div>
	<div class="row">
		<div class="col-lg-6 col-lg-offset-3">
			<form id="form_login">
				<div class="form-group">
					<label for="user"><?php echo $entry_username; ?></label>
					<input class="form-control" type="text" name="user" value="<?php echo $user ?>" />
				</div>
				<div class="form-group">
					<label for="password"><?php echo $entry_password; ?></label>
					<input class="form-control" type="password" name="password" value="<?php echo $password ?>" />
				</div>
				
				<input class="btn btn-default submit" id="login" type="button" value="<?php echo $button_login; ?>" onClick="javascript:actionKAJAX('<?php echo $login ?>', $('#form_login').serialize(), null, 'post')" />
			</form>
			
			<div class="text-danger">
				<?php 
					if (isset($error)) {
						echo $error;
					}
				?>
			</div>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<!-- /.row -->
<?php } else { ?>
	<div class="row">
		<div class="col-lg-12">
			<div class="page-header">
				<h1><?php echo $page_header; ?></h1>
			</div>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	
	<!-- /.row -->
	<div class="row">
		<div class="col-lg-12">
		
			<?php echo $user['FIRSTNAME'] . ' ' . $user['LASTNAME']; ?>
			<form id="form_login">
				<input id="login" type="BUTTON" value="<?php echo $button_logout; ?>" onClick="javascript:actionKAJAX('<?php echo $logout ?>', null, null, 'post')">
			</form>
			
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<!-- /.row -->
<?php } ?>
