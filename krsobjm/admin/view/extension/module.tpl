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
	<?php if ($error): ?>
	<div class="warning"><?php echo $error; ?></div>
	<?php endif; ?>
	<div class="box">
		<div class="heading">
			<h1><?php echo $heading_title; ?></h1>
		</div>
		<div class="content">
			<table class="list">
				<thead>
					<tr>
						<td class="left"><?php echo $column_name; ?></td>
						<td class="right"><?php echo $column_action; ?></td>
					</tr>
				</thead>
				<tbody>
					<?php if ($extensions): ?>
					<?php foreach ($extensions as $extension): ?>
					<tr>
						<td class="left"><?php echo $extension['name']; ?></td>
						<td class="right">
							<?php foreach ($extension['action'] as $action): ?>
							[ <a href="<?php echo $action['href']; ?>"><?php echo $action['text']; ?></a> ]
							<?php endforeach; ?>
						</td>
					</tr>
					<?php endforeach; ?>
					<?php else: ?>
					<tr>
						<td class="center" colspan="8"><?php echo $text_no_results; ?></td>
					</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php echo $footer; ?>
