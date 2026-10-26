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
			<h1><?php echo $heading_title; ?></h1>
			<div class="buttons">
				<a href="<?php echo $insert; ?>" class="button"><?php echo $button_insert; ?></a>
				<a onclick="$('form').submit();" class="button"><?php echo $button_delete; ?></a>
			</div>
		</div>
		<div class="content">
			<form action="<?php echo $delete; ?>" method="post" enctype="multipart/form-data" id="form">
				<table class="list">
					<thead>
						<tr>
							<td width="1" style="text-align: center;"><input type="checkbox" onclick="$('input[name*=\'selected\']').attr('checked', this.checked);" /></td>
							<td class="left"><a href="<?php echo $sort_prog_id; ?>" class="<?php echo ($sort === 'PROG_ID') ? strtolower($order) : ''; ?>"><?php echo $column_prog; ?></a></td>
							<td class="left"><a href="<?php echo $sort_name; ?>" class="<?php echo ($sort === 'NAME') ? strtolower($order) : ''; ?>"><?php echo $column_name; ?></a></td>
							<td class="left"><?php echo $column_description; ?></td>
							<td class="right"><?php echo $column_action; ?></td>
						</tr>
					</thead>
					<tbody>
						<?php if ($reports): ?>
						<?php foreach ($reports as $report): ?>
						<tr>
							<td style="text-align: center;"><input type="checkbox" name="selected[]" value="<?php echo $report['report_id']; ?>"<?php echo $report['selected'] ? ' checked="checked"' : ''; ?> /></td>
							<td class="left"><?php echo $report['prog_id']; ?></td>
							<td class="left"><?php echo $report['name']; ?></td>
							<td class="left"><?php echo $report['description']; ?></td>
							<td class="right">
								<?php foreach ($report['action'] as $action): ?>
								[ <a href="<?php echo $action['href']; ?>"><?php echo $action['text']; ?></a> ]
								<?php endforeach; ?>
							</td>
						</tr>
						<?php endforeach; ?>
						<?php else: ?>
						<tr>
							<td class="center" colspan="5"><?php echo $text_no_results; ?></td>
						</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</form>
			<div class="pagination"><?php echo $pagination; ?></div>
		</div>
	</div>
</div>
<?php echo $footer; ?>
