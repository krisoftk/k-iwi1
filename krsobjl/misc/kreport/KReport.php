<?php
class KReport {
	
	private $registry;
	
	public function __construct($registry) {
		$this->registry = $registry;
	}
	
	public function link($prog_id, $name) {
		$url = $this->registry->get('url');
		return  $url->link('common/kreport', '&prog=' . $prog_id . '&report=' . $name, 'SSL');
	}
}