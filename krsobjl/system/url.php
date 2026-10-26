<?php
/**
 * Builds the application URLs (INDEX + ?route=...&args), choosing the http
 * or https base depending on the third parameter of link().
 */
class Url {
	private $base;
	private $secureBase;
	private $rewriters = array();

	public function __construct($base, $secureBase = '') {
		$this->base = $base;
		$this->secureBase = $secureBase;
	}

	public function link($route = '', $args = '', $connection = 'NONSSL') {
		$url = ($connection === 'NONSSL') ? $this->base : $this->secureBase;

		$url .= INDEX;

		if ($route !== '') {
			$url .= '?route=' . $route;
		}

		if ($args !== '') {
			$url .= '&' . ltrim($args, '&');
		}

		foreach ($this->rewriters as $rewriter) {
			$url = $rewriter->rewrite($url);
		}

		return $url;
	}

	public function addRewrite($rewriter) {
		$this->rewriters[] = $rewriter;
	}
}
?>
