<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo $direction; ?>" lang="<?php echo $lang; ?>" xml:lang="<?php echo $lang; ?>">
<head>
	<title><?php echo $title; ?></title>
	<base href="<?php echo $base; ?>" />
	<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
	<?php if ($description): ?>
	<meta name="description" content="<?php echo $description; ?>" />
	<?php endif; ?>
	<?php if ($keywords): ?>
	<meta name="keywords" content="<?php echo $keywords; ?>" />
	<?php endif; ?>
	<?php foreach (array('css/stylesheet.css', 'css/jquery/ui/jquery-ui-1.8.12.custom.css') as $baseStylesheet): ?>
	<link rel="stylesheet" type="text/css" href="<?php echo makePath($baseStylesheet); ?>" />
	<?php endforeach; ?>
	<?php foreach ($links as $link): ?>
	<link href="<?php echo $link['href']; ?>" rel="<?php echo $link['rel']; ?>" />
	<?php endforeach; ?>
	<?php foreach ($styles as $style): ?>
	<link rel="<?php echo $style['rel']; ?>" type="text/css" href="<?php echo $style['href']; ?>" media="<?php echo $style['media']; ?>" />
	<?php endforeach; ?>
	<?php foreach (array(
		'js/jquery-1.11.1.min.js',
		'js/plugins/ui/jquery-ui-1.10.4.custom.min.js',
		'js/plugins/tabs/jquery.tabs.js',
	) as $baseScript): ?>
	<script type="text/javascript" src="<?php echo $baseScript; ?>"></script>
	<?php endforeach; ?>
	<?php foreach ($scripts as $script): ?>
	<script type="text/javascript" src="<?php echo $script; ?>"></script>
	<?php endforeach; ?>
	<script type="text/javascript">
	$(document).ready(function () {
		// chiede conferma prima di un'azione distruttiva (elimina/disinstalla)
		function needsConfirm(target) {
			return target && target.indexOf('delete') !== -1;
		}

		$('#form').on('submit', function () {
			if (needsConfirm($(this).attr('action')) && !confirm('<?php echo $text_confirm; ?>')) {
				return false;
			}
		});

		$('a').on('click', function () {
			var href = $(this).attr('href');

			if (href && href.indexOf('uninstall') !== -1 && !confirm('<?php echo $text_confirm; ?>')) {
				return false;
			}
		});

		highlightCurrentNavItem();
	});

	function currentRouteFromUrl() {
		var query = String(document.location).split('?')[1];

		if (!query) {
			return '';
		}

		var pairs = query.split('&');

		for (var i = 0; i < pairs.length; i++) {
			var pair = pairs[i].split('=');

			if (pair[0] && pair[0].toLowerCase() === 'route') {
				return decodeURIComponent(pair[1] || '');
			}
		}

		return '';
	}

	function highlightCurrentNavItem() {
		var route = currentRouteFromUrl();
		var section = route ? route.split('/').slice(0, 3).join('/') : 'admin/common/home';

		$('.nav-item[data-route="' + section + '"]').addClass('active');
	}
	</script>
</head>
<body>
<div class="shell<?php echo $logged ? '' : ' shell-bare'; ?>">
	<?php if ($logged): ?>
	<nav class="sidebar">
		<div class="brandmark" onclick="location='<?php echo $home; ?>'">
			<div class="mark">K</div>
			<div class="name">K-iwi<span><?php echo $heading_title; ?></span></div>
		</div>

		<a href="<?php echo $home; ?>" class="nav-item" data-route="admin/common/home"><?php echo $text_dashboard; ?></a>

		<p class="nav-label"><?php echo $text_users; ?></p>
		<a href="<?php echo $user; ?>" class="nav-item" data-route="admin/user/user"><?php echo $text_user; ?></a>
		<a href="<?php echo $user_group; ?>" class="nav-item" data-route="admin/user/group"><?php echo $text_user_group; ?></a>

		<p class="nav-label"><?php echo $text_extension; ?></p>
		<a href="<?php echo $module; ?>" class="nav-item" data-route="admin/extension/module"><?php echo $text_module; ?></a>
		<a href="<?php echo $report; ?>" class="nav-item" data-route="admin/extension/report"><?php echo $text_report; ?></a>

		<p class="nav-label"><?php echo $text_system; ?></p>
		<a href="<?php echo $setting; ?>" class="nav-item" data-route="admin/setting/setting"><?php echo $text_setting; ?></a>
		<a href="<?php echo $layout; ?>" class="nav-item"><?php echo $text_layout; ?></a>
		<a href="<?php echo $language; ?>" class="nav-item" data-route="admin/localisation/language"><?php echo $text_language; ?></a>

		<p class="nav-label"><?php echo $text_tools; ?></p>
		<a href="<?php echo $error_log; ?>" class="nav-item" data-route="admin/tool/error_log"><?php echo $text_error_log; ?></a>
		<a href="<?php echo $import_tables; ?>" class="nav-item" data-route="admin/tool/import_tables"><?php echo $text_import_tables; ?></a>
		<a href="<?php echo $generator; ?>" class="nav-item" data-route="admin/tool/generator"><?php echo $text_generator; ?></a>
		<a href="<?php echo $backup; ?>" class="nav-item"><?php echo $text_backup; ?></a>

		<p class="nav-label"><?php echo $text_help; ?></p>
		<a onclick="window.open('http://www.krisoft.it');" class="nav-item"><?php echo $text_framework; ?></a>
		<a onclick="window.open('http://www.krisoft.it');" class="nav-item"><?php echo $text_documentation; ?></a>
		<a onclick="window.open('http://www.krisoft.it');" class="nav-item"><?php echo $text_support; ?></a>
	</nav>
	<?php endif; ?>

	<div class="main">
		<?php if ($logged): ?>
		<div class="topbar">
			<div class="crumb"><?php echo $heading_title; ?></div>
			<div class="who">
				<?php echo $logged; ?>
				<a href="<?php echo $front_end; ?>" target="_blank"><?php echo $text_front_end; ?></a>
				<a href="<?php echo $logout; ?>"><?php echo $text_logout; ?></a>
			</div>
		</div>
		<?php endif; ?>
		<div class="main-body">