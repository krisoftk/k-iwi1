<?php echo $header; ?>
<div id="content">
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb): ?>
		<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php endforeach; ?>
	</div>
	<?php if ($readonly_warning): ?>
	<div class="warning"><?php echo $readonly_warning; ?></div>
	<?php endif; ?>
	<?php if ($error_warning): ?>
	<div class="warning"><?php echo $error_warning; ?></div>
	<?php endif; ?>
	<?php if ($success): ?>
	<div class="success"><?php echo $success; ?></div>
	<?php endif; ?>
	<div class="box">
		<div class="heading">
			<h1><?php echo $heading_title; ?></h1>
		</div>
		<div class="content">
			<p class="muted"><?php echo $text_help; ?></p>
			<?php foreach ($areas as $area): ?>
			<h2 class="theme-area"><?php echo $area['title']; ?> <small class="muted"><?php echo $area['key']; ?></small></h2>
			<?php if ($area['unknown']): ?>
			<div class="attention"><?php echo $area['unknown']; ?></div>
			<?php endif; ?>
			<div class="theme-grid">
				<?php foreach ($area['themes'] as $theme): ?>
				<div class="theme-card<?php echo $theme['active'] ? ' is-active' : ''; ?>">
					<div class="theme-preview">
						<?php if ($theme['preview']): ?>
						<img src="<?php echo $theme['preview']; ?>" alt="" loading="lazy" />
						<?php else: ?>
						<span class="muted"><?php echo $text_no_preview; ?></span>
						<?php endif; ?>
					</div>
					<div class="theme-body">
						<div class="theme-title">
							<strong><?php echo $theme['name']; ?></strong>
							<?php if ($theme['active']): ?><span class="theme-badge"><?php echo $text_active; ?></span><?php endif; ?>
						</div>
						<small class="muted"><?php echo sprintf($text_templates, $theme['templates']); ?></small>
						<?php if ($theme['problems']): ?>
						<ul class="theme-problems">
							<?php foreach ($theme['problems'] as $problem): ?>
							<li><?php echo $problem; ?></li>
							<?php endforeach; ?>
						</ul>
						<?php else: ?>
						<div class="theme-ok"><?php echo $text_complete; ?></div>
						<?php endif; ?>
						<?php if ($writable && !$theme['active']): ?>
						<form action="<?php echo $action; ?>" method="post"<?php echo $theme['problems'] ? ' onsubmit="return confirm(\'' . htmlspecialchars(addslashes($text_confirm_incomplete), ENT_QUOTES, 'UTF-8') . '\');"' : ''; ?>>
							<input type="hidden" name="area" value="<?php echo $area['area']; ?>" />
							<input type="hidden" name="theme" value="<?php echo $theme['name']; ?>" />
							<button type="submit" class="button"><?php echo $button_activate; ?></button>
						</form>
						<?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
<?php echo $footer; ?>
