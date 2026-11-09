<?php
class Pagination {
	public $total = 0;
	public $page = 1;
	public $limit = 20;
	public $num_links = 10;
	public $url = '';
	public $text = 'Showing {start} to {end} of {total} ({pages} Pages)';	
	public $theme = '';
	
	public $next = '&gt;';
	public $prev = '&lt;';
	public $first = '|&lt;';
	public $last = '&gt;|';
	
	private $data = array();
	 
	public function render() {
		$total = $this->total;
		
		if ($this->page < 1) {
			$page = 1;
		} else {
			$page = $this->page;
		}
		
		if (!(int)$this->limit) {
			$limit = 10;
		} else {
			$limit = $this->limit;
		}
		
		$num_links = $this->num_links;
		$num_pages = ceil($total / $limit);
		
		$pagination = false;
		
		if ($page > 1) {
			$pagination = true;
    	}

    	$pages = array();
    	$start = 0;
    	$end = 0;
    	
		if ($num_pages > 1) {
			if ($num_pages <= $num_links) {
				$start = 1;
				$end = $num_pages;
			} else {
				$start = $page - floor($num_links / 2);
				$end = $page + floor($num_links / 2);
			
				if ($start < 1) {
					$end += abs($start) + 1;
					$start = 1;
				}
						
				if ($end > $num_pages) {
					$start -= ($end - $num_pages);
					$end = $num_pages;
				}
			}

			$pages = array();
			for ($i = $start; $i <= $end; $i++) {
				$pagination_number = array();
				
				$pagination_number['number'] = $i;
				if ($page == $i) {
					$pagination_number['current'] = true;
					$pagination_number['url'] = null;
				} else {
					$pagination_number['current'] = false;
					$pagination_number['url'] = str_replace('{page}', $i, $this->url); 
				}
				
				$pages[] = $pagination_number;
			}
		}
		
   		if ($page < $num_pages) {
			$pagination = true;
		}
		
		$find = array(
			'{start}',
			'{end}',
			'{total}',
			'{pages}'
		);
		
		// rows shown on this page; a page past the last one (e.g. after a
		// deletion) shows nothing: "0 to 0", not "26 to 25"
		$first_row = (($page - 1) * $limit) + 1;
		$shown = ($total > 0 && $first_row <= $total);

		$replace = array(
			$shown ? $first_row : 0,
			$shown ? min($page * $limit, $total) : 0,
			$total, 
			$num_pages
		);
		
		$this->data = array(
			"pagination" 	=> $pagination,
			"current_page"	=> $page,
			"pages"			=> $pages,
			"num_pages"		=> $num_pages,
			"start"			=> $start,
			"end"			=> $end,
			"next"			=> $this->next,
			"prev"			=> $this->prev,
			"first"			=> $this->first,
			"last"			=> $this->last,
			"result"		=> str_replace($find, $replace, $this->text),
			"action_next"	=> str_replace('{page}', $page + 1, $this->url),
			"action_prev"	=> str_replace('{page}', $page - 1, $this->url),
			"action_first"	=> str_replace('{page}', 1, $this->url),
			"action_last"	=> str_replace('{page}', $num_pages, $this->url)
		);
		
		$template = 'standard/view/pagination.tpl';
		if (file_exists(DIR_TEMPLATE . $this->theme . '/pagination.tpl')) {
			$template = 'standard/view/themes/' . $this->theme . '/pagination.tpl';
		}
		
		if (file_exists(DIR_MODULE . $template)) {
				
			extract($this->data);
				
			ob_start();
		
			require(DIR_MODULE . $template);
		
			$output = ob_get_contents();
		
			ob_end_clean();
		
			return $output;
		} else {
			trigger_error('Error: Could not load template ' . DIR_MODULE . $template . '!');
			exit();
		}
	}
}
?>