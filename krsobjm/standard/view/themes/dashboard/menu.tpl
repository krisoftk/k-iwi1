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

// titles go through the modules' menu.php files, as in the base menu.tpl
function create_menu($menu, $labels, $level = false) {
	$html_menu = '';
	
	foreach ($menu as $item) {
		if ($html_menu == '') {
			if ($level) {
				$html_menu = '<ul class="nav nav-second-level" id="side-menu">';
			} else {
				$html_menu = '<ul class="nav" id="side-menu">';
			}
		}
		
		$html_menu .= "<li>";
		
		$arrow = '';
		if (isset($item['children'])) {
			$arrow = '<span class="fa arrow"></span>';
		}
		
		$href = 'href="#"';
		if (isset($item['link'])) {
			$href = ' href="' . $item['link'] . '"';
		}
		
		$html_menu .= '<a' . $href . '>' . $labels->get($item['title']) . $arrow .'</a>';
		
		if (isset($item['children'])) {
			$html_menu .= create_menu($item['children'], $labels, true);
		}
		
		$html_menu .= "</li>";
	}
	
	if ($html_menu != '') {
		$html_menu .= '</ul>';
	}
	
	return $html_menu;
} 
?>
<script type="text/javascript"><!--
$(function() {

    $('#side-menu').metisMenu();

});

//Loads the correct sidebar on window load,
//collapses the sidebar on window resize.
$(function() {
    $(window).bind("load resize", function() {
        width = (this.window.innerWidth > 0) ? this.window.innerWidth : this.screen.width;
        if (width < 768) {
            $('div.sidebar-collapse').addClass('collapse');
        } else {
            $('div.sidebar-collapse').removeClass('collapse');
        }
    });
});
--></script>