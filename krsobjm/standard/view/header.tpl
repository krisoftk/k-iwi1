<title><?php echo $title; ?></title>
<base href="<?php echo $base; ?>" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<?php if ($author) { ?>
<meta name="author" content="<?php echo $author; ?>">
<?php } ?>
<?php if ($description) { ?>
<meta name="description" content="<?php echo $description; ?>" />
<?php } ?>
<?php if ($keywords) { ?>
<meta name="keywords" content="<?php echo $keywords; ?>" />
<?php } ?>
<?php foreach ($links as $link) { ?>
<link href="<?php echo $link['href']; ?>" rel="<?php echo $link['rel']; ?>" />
<?php } ?>
<?php foreach ($styles as $style) { ?>
<link rel="<?php echo $style['rel']; ?>" type="text/css" href="<?php echo $style['href']; ?>" media="<?php echo $style['media']; ?>" />
<?php } ?>
<?php foreach ($scripts as $script) { ?>
<script type="text/javascript" src="<?php echo $script; ?>"></script>
<?php } ?>

<!-- JQUERY + PLUGINS -->
<script type="text/javascript" src="js/jquery-1.11.1.min.js"></script>
<script type="text/javascript" src="js/plugins/ui/jquery-ui-1.10.4.custom.min.js"></script>
<script type="text/javascript" src="js/plugins/timepicker/jquery-ui-timepicker-addon.js"></script>
<script type="text/javascript" src="js/plugins/fixedheadertable/jquery.fixedheadertable.js"></script>
<script type="text/javascript" src="js/plugins/bgiframe/jquery.bgiframe.js"></script>
<script type="text/javascript" src="js/plugins/filetree/jquery.filetree.js"></script>
<script type="text/javascript" src="js/plugins/printElement/jquery.printElement.js"></script>
<script type="text/javascript" src="js/plugins/tabs/jquery.tabs.js"></script>
<script type="text/javascript" src="js/plugins/filedownload/jquery.fileDownload.js"></script>
<script type="text/javascript" src="js/plugins/colorpicker/jquery.colorpicker.js"></script>

<!-- CKEDITOR -->
<script type="text/javascript" src="js/ckeditor/ckeditor.js"></script>

<!-- FILE UPLOAD -->
<script src="js/plugins/fileupload/js/jquery.fileupload.js"></script>
<script src="js/plugins/fileupload/js/jquery.fileupload-process.js"></script>
<script src="js/plugins/fileupload/js/jquery.fileupload-ui.js"></script>
<script src="js/plugins/fileupload/js/jquery.fileupload-jquery-ui.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="js/plugins/fileupload/js/jquery.iframe-transport.js"></script>

<!-- JS DEPRECATED -->
<script type="text/javascript" src="js/deprecated/function.js"></script>

<?php if ($kajax) {
	echo $kajax;
} ?>

<?php if ($extras) {
	echo $extras;
} ?>