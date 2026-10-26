<?php if(!$logged) {?>
	<form name="flogin" method="post" action="<?php echo $login ?>">
		<br />
		<img src="<?php echo makePath('img/user.png')?>" width="128" height="128" title="<?php echo $text_login_hint; ?>">
		<table>
			<tr>
	   			<td><?php echo $entry_username; ?></td><td><input type="text" name="user" value="<?php echo $user ?>"></td>
			</tr>
			<tr>
	   			<td><?php echo $entry_password; ?></td><td><input type="password" name="password" value="<?php echo $password ?>"></td>
			</tr>
		</table>
		<input id="login" type="button" value="<?php echo $button_login; ?>" onClick="javascript:actionKAJAX(flogin.action, flogin, null, flogin.method)">
	</form>
	<?php 
		if(isset($error)) {
			echo $error;
		}
	?>
<?php } else { ?>
	<?php echo $user['FIRSTNAME'] . ' ' . $user['LASTNAME']; ?>
	<form name="flogin" method="post" action="<?php echo $logout ?>">
		<input id="login" type="BUTTON" value="<?php echo $button_logout; ?>" onClick="javascript:actionKAJAX(flogin.action, flogin, null, flogin.method)">
	</form>
<?php } ?>
