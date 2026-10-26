<div class="row">
	<div class="col-sm-4">
		<div class="dataTables_info" role="alert" aria-live="polite" aria-relevant="all">
			<div class="results"><?php echo $result; ?></div>
		</div>
	</div>

	<?php if ($pagination) { ?>
		<div class="col-sm-8">
			<div class="dataTables_paginate paging_simple_numbers">
				<ul class="pagination">
					<?php if ($current_page > 1) { ?>
						<li class="paginate_button previous" tabindex="0"><a href="javascript:void(0);" onclick="<?php echo $action_first; ?>"><?php echo $first; ?></a></li>
						<li class="paginate_button previous" tabindex="0"><a href="javascript:void(0);" onclick="<?php echo $action_prev; ?>"><?php echo $prev; ?></a></li>
					<?php } ?>
					
					<?php if ($num_pages > 1) { ?>
						<?php if ($start > 1) { ?>
							<li class="paginate_button disabled" tabindex="0"><span>...</span></li>
						<?php } ?>
						
						<?php foreach ($pages as $page) { ?>
							<?php if ($page['current']) { ?>
								<li class="paginate_button active" tabindex="0"><span><?php echo $page['number']; ?><span class="sr-only">(current)</span></span></li>
							<?php } else { ?>
								<li class="paginate_button" tabindex="0"><a href="javascript:void(0);" onclick="<?php echo $page['url']; ?>"><?php echo $page['number']; ?></a></li>
							<?php } ?>	
						<?php } ?>
						
						<?php if ($end < $num_pages) { ?>
							<li class="paginate_button disabled" tabindex="0"><span>...</span></li>
						<?php } ?>
					<?php } ?>
					
					<?php if ($current_page < $num_pages) { ?>
						<li class="paginate_button next" tabindex="0"><a href="javascript:void(0);" onclick="<?php echo $action_next; ?>"><?php echo $next; ?></a></li>
						<li class="paginate_button next" tabindex="0"><a href="javascript:void(0);" onclick="<?php echo $action_last; ?>"><?php echo $last; ?></a></li>
					<?php } ?>
				</ul>
			</div>
		</div>
	<?php } ?>
</div>




	
