<?php

if ($logged) {
	if (!isset($menu)) {
		echo $text_no_menu;
	} else {
		echo create_menu($menu, $labels);
	}
} else {
	echo $text_not_logged;
}

function create_menu($menu, $labels) {
	$html_menu = '';
	
	foreach ($menu as $item) {
		if ($html_menu == '') {
			$html_menu = '<ul>';
		}
		
		$html_menu .= "<li>";
		
		if (isset($item['link'])) {
			$html_menu .= '<a href="' . $item['link'] . '">' . $labels->get($item['title']) . "</a>";
		} else {
			$html_menu .= '<a>' . $labels->get($item['title']) . "</a>";
		}
		
		if (isset($item['children'])) {
			$html_menu .= create_menu($item['children']);
		}
		
		$html_menu .= "</li>";
	}
	
	if ($html_menu != '') {
		$html_menu .= '</ul>';
	}
	
	return $html_menu;
} 
?>