<?php if ($pagination) { ?>
	<div class="links">
		<?php if ($current_page > 1) { ?>
			<a href="<?php echo $action_first; ?>"><?php echo $first; ?></a>
			<a href="<?php echo $action_prev; ?>"><?php echo $prev; ?></a>
		<?php } ?>
		
		<?php if ($num_pages > 1) { ?>
			<?php if ($start > 1) { ?>
				<span>...</span>
			<?php } ?>
			
			<?php foreach ($pages as $page) { ?>
				<?php if ($page['current']) { ?>
					<b><?php echo $page['number']; ?></b>
				<?php } else { ?>
					<a href="<?php echo $page['url']; ?>"><?php echo $page['number']; ?></a>
				<?php } ?>	
			<?php } ?>
			
			<?php if ($end < $num_pages) { ?>
				<span>...</span>
			<?php } ?>
		<?php } ?>
		
		<?php if ($current_page < $num_pages) { ?>
			<a href="<?php echo $action_next; ?>"><?php echo $next; ?></a>
			<a href="<?php echo $action_last; ?>"><?php echo $last; ?></a>
		<?php } ?>
	</div>
<?php } ?>

<div class="results"><?php echo $result; ?></div>
	
