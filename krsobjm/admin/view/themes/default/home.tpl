<?php echo $header; ?>
<div id="content">
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb): ?>
		<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php endforeach; ?>
	</div>
	<?php if ($error_logs): ?>
	<div class="warning"><?php echo $error_logs; ?></div>
	<?php endif; ?>
	<?php if ($text_install): ?>
	<div class="attention"><?php echo $text_install; ?></div>
	<?php endif; ?>
	<div class="box">
		<div class="heading">
			<h1><?php echo $heading_title; ?></h1>
		</div>
		<div class="content"></div>
	</div>
</div>
<?php echo $footer; ?>
