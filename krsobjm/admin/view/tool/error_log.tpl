<?php echo $header; ?>
<div id="content">
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb): ?>
		<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php endforeach; ?>
	</div>
	<?php if ($success): ?>
	<div class="success"><?php echo $success; ?></div>
	<?php endif; ?>
	<div class="box">
		<div class="heading">
			<h1><?php echo $heading_title; ?></h1>
			<div class="buttons"><a href="<?php echo $clear; ?>" class="button"><?php echo $button_clear; ?></a></div>
		</div>
		<div class="content">
			<textarea wrap="off" class="log-viewer"><?php echo $log; ?></textarea>
		</div>
	</div>
</div>
<?php echo $footer; ?>
