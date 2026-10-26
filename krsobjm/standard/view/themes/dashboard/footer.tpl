<!-- Note: All SoundJS classes are listed here: -->
<script type="text/javascript" src="js/createjs/createjs/utils/Proxy.js"></script>
<script type="text/javascript" src="js/createjs/createjs/utils/IndexOf.js"></script>
<script type="text/javascript" src="js/createjs/createjs/events/Event.js"></script>
<script type="text/javascript" src="js/createjs/createjs/events/EventDispatcher.js"></script>
<script type="text/javascript" src="js/createjs/soundjs/Sound.js"></script>
<script type="text/javascript" src="js/createjs/soundjs/WebAudioPlugin.js"></script>
<script type="text/javascript" src="js/createjs/soundjs/HTMLAudioPlugin.js"></script>

<!-- JQUERY + PLUGINS -->
<script type="text/javascript" src="js/jquery-1.11.1.min.js"></script>
<script type="text/javascript" src="js/plugins/ui/jquery-ui-1.11.0.min.js"></script>
<script type="text/javascript" src="js/plugins/timepicker/jquery-ui-timepicker-addon.js"></script>
<script type="text/javascript" src="js/plugins/fixedheadertable/jquery.fixedheadertable.js"></script>
<script type="text/javascript" src="js/plugins/bgiframe/jquery.bgiframe.js"></script>
<script type="text/javascript" src="js/plugins/filetree/jquery.filetree.js"></script>
<script type="text/javascript" src="js/plugins/printElement/jquery.printElement.js"></script>
<script type="text/javascript" src="js/plugins/tabs/jquery.tabs.js"></script>
<script type="text/javascript" src="js/plugins/filedownload/jquery.fileDownload.js"></script>
<script type="text/javascript" src="js/plugins/colorpicker/jquery.colorpicker.js"></script>
<script type="text/javascript" src="js/plugins/metisMenu/jquery.metisMenu.js"></script>

<!-- JqPlot -->
<script type="text/javascript" src="js/jqplot/jquery.jqplot.min.js"></script>
<script type="text/javascript" src="js/jqplot/plugins/jqplot.pieRenderer.js"></script>
<script type="text/javascript" src="js/jqplot/plugins/jqplot.barRenderer.js"></script>

<!-- Moment -->
<script type="text/javascript" src="js/moment-with-locales.min.js"></script>

<!-- Bootstrap  + plugins-->
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/plugins/datetimepicker/bootstrap-datetimepicker.js"></script>

<!-- Data tables -->
<script type="text/javascript" src="js/plugins/dataTables/jquery.dataTables.js"></script>
<script type="text/javascript" src="js/plugins/dataTables/dataTables.bootstrap.js"></script>

<!-- CKEDITOR -->
<script type="text/javascript" src="js/ckeditor/ckeditor.js"></script>

<!-- FILE UPLOAD -->
<script type="text/javascript" src="js/plugins/fileupload/js/jquery.fileupload.js"></script>
<script type="text/javascript" src="js/plugins/fileupload/js/jquery.fileupload-process.js"></script>
<script type="text/javascript" src="js/plugins/fileupload/js/jquery.fileupload-ui.js"></script>
<script type="text/javascript" src="js/plugins/fileupload/js/jquery.fileupload-jquery-ui.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script type="text/javascript" src="js/plugins/fileupload/js/jquery.iframe-transport.js"></script>

<!-- Fix button jquery and bootstrap -->
<script type="text/javascript"><!--
if($.fn.button.noConflict) {
	var btn = $.fn.button.noConflict(); // reverts $.fn.button to jqueryui btn
	$.fn.btn = btn; // assigns bootstrap button functionality to $.fn.btn
}
--></script>

<?php if ($kajax) {
	echo $kajax;
} ?>

<script type="text/javascript"><!--
	$(document).ready(function() {
		actionKAJAX("<?php echo $menu; ?>", "menu=standard", "#menudinamico", 'post', {'block':false});

		actionKAJAX("<?php echo $login; ?>", "", null, null, {'block':false});
	});
--></script>

<!-- GUI CONTROL -->
<script type="text/javascript" src="js/gui/gui_control.js"></script>
