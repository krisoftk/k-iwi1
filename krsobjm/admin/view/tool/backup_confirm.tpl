<?php echo $header; ?>
<div id="content">
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb): ?>
		<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php endforeach; ?>
	</div>
	<?php if ($error_warning): ?>
	<div class="warning"><?php echo $error_warning; ?></div>
	<?php endif; ?>
	<?php if ($success): ?>
	<div class="success"><?php echo $success; ?></div>
	<?php endif; ?>
	<div class="box">
		<div class="heading">
			<h1><?php echo $text_confirm_title; ?></h1>
		</div>
		<div class="content">
			<div class="attention"><?php echo $text_target; ?> <?php echo $text_confirm_help; ?></div>
			<?php if ($other_database): ?>
			<div class="attention"><?php echo $other_database; ?></div>
			<?php endif; ?>
			<table class="form">
				<tr>
					<td><?php echo $text_file; ?></td>
					<td><code><?php echo $file; ?></code></td>
				</tr>
				<tr>
					<td><?php echo $text_created_on; ?></td>
					<td><?php echo $created; ?></td>
				</tr>
				<tr>
					<td><?php echo $text_from_database; ?></td>
					<td><?php echo $database; ?> <small class="muted"><?php echo $text_server; ?> <?php echo $server; ?></small></td>
				</tr>
				<tr>
					<td><?php echo $text_replaced; ?> (<?php echo count($replaced); ?>)</td>
					<td><?php echo $replaced ? implode(', ', $replaced) : $text_none; ?></td>
				</tr>
				<tr>
					<td><?php echo $text_untouched; ?> (<?php echo count($untouched); ?>)</td>
					<td><?php echo $untouched ? implode(', ', $untouched) : $text_none; ?></td>
				</tr>
			</table>
			<form action="<?php echo $action; ?>" method="post">
				<input type="hidden" name="file" value="<?php echo $file; ?>" />
				<p><label><input type="checkbox" name="agree" value="1" /> <?php echo $text_agree; ?></label></p>
				<p class="buttons">
					<button type="submit" class="button"><?php echo $button_restore; ?></button>
					<a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a>
				</p>
			</form>
		</div>
	</div>
</div>
<?php echo $footer; ?>
