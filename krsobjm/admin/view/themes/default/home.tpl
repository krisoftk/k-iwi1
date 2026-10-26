<?php echo $header; ?>
<div id="content">
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb): ?>
		<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php endforeach; ?>
	</div>
	<?php foreach (array($error_install, $error_logs) as $warning): ?>
	<?php if ($warning): ?>
	<div class="warning"><?php echo $warning; ?></div>
	<?php endif; ?>
	<?php endforeach; ?>
	<div class="box">
		<div class="heading">
			<h1><?php echo $heading_title; ?></h1>
		</div>
		<div class="content"></div>
	</div>
</div>
<?php echo $footer; ?>
