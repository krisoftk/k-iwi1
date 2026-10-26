<base href="<?php echo $base; ?>" />

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

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

<title><?php echo $title; ?></title>

<!-- Bootstrap -->
<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="font-awesome/css/font-awesome.min.css" rel="stylesheet">

<!-- SB Admin CSS - Include with every page -->
<link href="themes/dashboard/css/sb-admin.css" rel="stylesheet">

<?php if ($extras) {
	echo $extras;
} ?>