<?php
// Same structure as the admin sidebar: items with children become sections
// that open and close (see js/kiwi.js), the others plain links. The titles
// go through the modules' menu.php files, as in the base menu.tpl.
if (!function_exists('kiwi_menu')) {
	function kiwi_menu($menu, $labels, &$group) {
		$html = '';

		foreach ($menu as $item) {
			$title = esc($labels->get($item['title']));
			$link = !empty($item['link']) ? $item['link'] : '';

			if (empty($item['children'])) {
				$html .= '<a class="kw-nav-item" href="' . ($link !== '' ? $link : '#') . '">' . $title . '</a>';

				continue;
			}

			$id = 'kw-nav-' . (++$group);

			$html .= '<div class="kw-nav-group" data-group="' . $group . '">';
			$html .= '<button type="button" class="kw-nav-toggle" aria-expanded="true" aria-controls="' . $id . '"><span>' . $title . '</span>'
				. '<svg class="kw-chevron" viewBox="0 0 16 16" width="12" height="12" aria-hidden="true"><path d="M6 3.5 10.5 8 6 12.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
			$html .= '<div class="kw-nav-items" id="' . $id . '"><div class="kw-nav-inner">';

			// a section with its own program: the program is its first entry
			if ($link !== '') {
				$html .= '<a class="kw-nav-item" href="' . $link . '">' . $title . '</a>';
			}

			$html .= kiwi_menu($item['children'], $labels, $group);
			$html .= '</div></div></div>';
		}

		return $html;
	}
}

if (!$logged) {
	echo '<p class="kw-nav-empty">' . $text_not_logged . '</p>';
} elseif (empty($menu)) {
	echo '<p class="kw-nav-empty">' . $text_no_menu . '</p>';
} else {
	$group = 0;

	echo kiwi_menu($menu, $labels, $group);
}
?>
<script type="text/javascript"><!--
if (window.kiwiNav) {
	kiwiNav.init();
}
//--></script>
