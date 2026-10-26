<?php if (isset($_SERVER['HTTP_USER_AGENT']) && !strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6')) echo '<?xml version="1.0" encoding="UTF-8"?>'. "\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo $direction; ?>" lang="<?php echo $lang; ?>" xml:lang="<?php echo $lang; ?>">
<head>
	<?php echo $header; ?>
</head>    
<body>
	<div class="logo"></div>
	
	<div class="titolo">
		<?php echo $heading_title; ?>
		<br />
		<a href="<?php action('print')?>"><img src="<?php echo makePath('img/printer.png')?>" width="20" height="20" alt="<?php echo $text_print_alt; ?>" title="<?php echo $text_print; ?>" /></a>
		<a href="<?php action('pdf')?>"><img src="<?php echo makePath('img/pdf.png')?>" width="20" height="20" alt="<?php echo $text_pdf_alt; ?>" title="<?php echo $text_pdf; ?>" /></a>
		<a href="<?php action('mail')?>"><img src="<?php echo makePath('img/mail.png')?>" width="20" height="20" alt="<?php echo $text_mail_alt; ?>" title="<?php echo $text_mail; ?>" /></a>
	</div>
	
	<div class="info" id="information">		
	</div>
	
	<div class="menu" id="menudinamico">
		<script type="text/javascript">
			actionKAJAX("<?php echo $menu; ?>", "menu=standard", "menudinamico");
		</script>
	</div>
	
	<div class="corpo" id="corposito">
		<script type="text/javascript">
			actionKAJAX("<?php echo $login; ?>");
		</script>
	</div>
	
	<div class="foot" id="sitefoot">
		<?php echo $footer; ?>
	</div>
	<div id=dialog></div>
</body>
</html>
