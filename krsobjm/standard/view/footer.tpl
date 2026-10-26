<?php echo $copyright; ?>

<?php echo $kajax; ?>

<script type="text/javascript"><!--
	$(document).ready(function() {
		actionKAJAX("<?php echo $menu; ?>", "menu=standard", "#menudinamico", 'post', {'block':false});

		actionKAJAX("<?php echo $login; ?>", "", null, null, {'block':false});
	});
--></script>

<!-- GUI CONTROL -->
<script type="text/javascript" src="js/gui/gui_control.js"></script>
