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
		// asks for confirmation before a destructive action (delete/uninstall)
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

		return $('.nav-item[data-route="' + section + '"]').addClass('active');
	}

	// Sidebar sections that open and close. Closed by default, except the one
	// holding the current page; the sections the user opens by hand stay open
	// from page to page (localStorage, only a convenience: without it the
	// menu still works). The section opened only because it holds the current
	// page isn't remembered, so it closes again on the next page.
	var NAV_STORAGE_KEY = 'kiwi.admin.nav.open';

	function savedOpenGroups() {
		try {
			return JSON.parse(window.localStorage.getItem(NAV_STORAGE_KEY)) || [];
		} catch (e) {
			return [];
		}
	}

	function rememberGroup(group, open) {
		var saved = $.grep(savedOpenGroups(), function (name) { return name !== group; });

		if (open) {
			saved.push(group);
		}

		try {
			window.localStorage.setItem(NAV_STORAGE_KEY, JSON.stringify(saved));
		} catch (e) {
		}
	}

	function setGroupOpen($group, open) {
		$group.toggleClass('open', open);
		$group.children('.nav-group-toggle').attr('aria-expanded', open ? 'true' : 'false');
	}

	function initCollapsibleNav() {
		var $sidebar = $('.sidebar');
		var saved = savedOpenGroups();
		var $active = highlightCurrentNavItem();

		$sidebar.find('.nav-group').each(function () {
			var $group = $(this);
			var holdsActive = $group.find($active).length > 0;

			$group.toggleClass('has-active', holdsActive);
			setGroupOpen($group, holdsActive || $.inArray($group.data('group'), saved) !== -1);
		});

		$sidebar.addClass('nav-collapsible');

		// transitions only after the first paint, so the initial state doesn't animate
		window.setTimeout(function () {
			$sidebar.addClass('nav-animated');
		}, 0);

		$sidebar.on('click', '.nav-group-toggle', function () {
			var $group = $(this).closest('.nav-group');
			var open = !$group.hasClass('open');

			setGroupOpen($group, open);
			rememberGroup($group.data('group'), open);
		});
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

		<div class="nav-group" data-group="users">
			<button type="button" class="nav-group-toggle" aria-expanded="true" aria-controls="nav-users"><span><?php echo $text_users; ?></span><svg class="nav-chevron" viewBox="0 0 16 16" width="12" height="12" aria-hidden="true"><path d="M6 3.5 10.5 8 6 12.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
			<div class="nav-group-items" id="nav-users"><div class="nav-group-inner">
				<a href="<?php echo $user; ?>" class="nav-item" data-route="admin/user/user"><?php echo $text_user; ?></a>
				<a href="<?php echo $user_group; ?>" class="nav-item" data-route="admin/user/group"><?php echo $text_user_group; ?></a>
			</div></div>
		</div>
		<div class="nav-group" data-group="extension">
			<button type="button" class="nav-group-toggle" aria-expanded="true" aria-controls="nav-extension"><span><?php echo $text_extension; ?></span><svg class="nav-chevron" viewBox="0 0 16 16" width="12" height="12" aria-hidden="true"><path d="M6 3.5 10.5 8 6 12.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
			<div class="nav-group-items" id="nav-extension"><div class="nav-group-inner">
				<a href="<?php echo $module; ?>" class="nav-item" data-route="admin/extension/module"><?php echo $text_module; ?></a>
				<a href="<?php echo $report; ?>" class="nav-item" data-route="admin/extension/report"><?php echo $text_report; ?></a>
			</div></div>
		</div>
		<div class="nav-group" data-group="system">
			<button type="button" class="nav-group-toggle" aria-expanded="true" aria-controls="nav-system"><span><?php echo $text_system; ?></span><svg class="nav-chevron" viewBox="0 0 16 16" width="12" height="12" aria-hidden="true"><path d="M6 3.5 10.5 8 6 12.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
			<div class="nav-group-items" id="nav-system"><div class="nav-group-inner">
				<a href="<?php echo $setting; ?>" class="nav-item" data-route="admin/setting/setting"><?php echo $text_setting; ?></a>
				<a href="<?php echo $layout; ?>" class="nav-item"><?php echo $text_layout; ?></a>
				<a href="<?php echo $language; ?>" class="nav-item" data-route="admin/localisation/language"><?php echo $text_language; ?></a>
			</div></div>
		</div>
		<div class="nav-group" data-group="tools">
			<button type="button" class="nav-group-toggle" aria-expanded="true" aria-controls="nav-tools"><span><?php echo $text_tools; ?></span><svg class="nav-chevron" viewBox="0 0 16 16" width="12" height="12" aria-hidden="true"><path d="M6 3.5 10.5 8 6 12.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
			<div class="nav-group-items" id="nav-tools"><div class="nav-group-inner">
				<a href="<?php echo $error_log; ?>" class="nav-item" data-route="admin/tool/error_log"><?php echo $text_error_log; ?></a>
				<a href="<?php echo $import_tables; ?>" class="nav-item" data-route="admin/tool/import_tables"><?php echo $text_import_tables; ?></a>
				<a href="<?php echo $generator; ?>" class="nav-item" data-route="admin/tool/generator"><?php echo $text_generator; ?></a>
				<a href="<?php echo $backup; ?>" class="nav-item"><?php echo $text_backup; ?></a>
			</div></div>
		</div>
		<div class="nav-group" data-group="help">
			<button type="button" class="nav-group-toggle" aria-expanded="true" aria-controls="nav-help"><span><?php echo $text_help; ?></span><svg class="nav-chevron" viewBox="0 0 16 16" width="12" height="12" aria-hidden="true"><path d="M6 3.5 10.5 8 6 12.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
			<div class="nav-group-items" id="nav-help"><div class="nav-group-inner">
				<a onclick="window.open('http://www.krisoft.it');" class="nav-item"><?php echo $text_framework; ?></a>
				<a onclick="window.open('http://www.krisoft.it');" class="nav-item"><?php echo $text_documentation; ?></a>
				<a onclick="window.open('http://www.krisoft.it');" class="nav-item"><?php echo $text_support; ?></a>
			</div></div>
		</div>
	</nav>
	<script type="text/javascript">
	// Runs right after the sidebar markup, before the page is painted, so the
	// closed sections never flash open. Without JavaScript every section stays
	// open (the collapsing rules only apply under .nav-collapsible).
	initCollapsibleNav();
	</script>
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