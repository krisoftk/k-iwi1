<?php
/*
 * PDF documents made of text placed on the page, over a background image:
 * the pre-printed forms of orders, invoices, delivery notes. Written in
 * plain PHP, it needs only zlib and iconv. Units are millimetres from the
 * top left corner, font sizes are points.
 *
 *   $pdf = new KPdf();
 *   $pdf->default_font_size = 8;
 *   $pdf->create();
 *   $pdf->setFont('couriernew');
 *   $pdf->setBackground('order.jpg');          // every page, fitted to the page
 *   $pdf->setHeader(array(                     // every page
 *       array('X' => 12, 'Y' => 48, 'SIZE' => 0, 'TEXT' => $customer, 'ALIGN' => 'L'),
 *       array('X' => 19, 'Y' => 98, 'SIZE' => 175, 'HEIGHT' => 3, 'TEXT' => $notes, 'ALIGN' => 'L'),
 *   ));
 *   $pdf->setFooter(array(array('X' => 180, 'Y' => 288, 'SIZE' => 0, 'TEXT' => 'PAGE {PAGENO} / {PAGES}', 'ALIGN' => 'L')));
 *   $pdf->setLastFooter(...);                  // last page only (totals)
 *   $pdf->fillBody($rows, $columns, 0, 125, 5, 22, 4);
 *   $pdf->save('order.pdf', 'I');              // I inline, D download, F file, S string
 *
 * A field is a cell SIZE mm wide (0 = up to the right edge) with the text
 * aligned in it (L, C, R); in the header, HEIGHT > 0 lets a long text wrap
 * on lines HEIGHT mm apart. fillBody() writes the rows, one column per
 * array('START' => x, 'SIZE' => width, 'ALIGN' => L/C/R), $rowH mm apart
 * from $startY, $maxRowPage per page: when they don't fit, each page holds
 * $maxRowPage - $offset of them and ends with "SEGUE" (continues).
 *
 * Fonts: the standard ones of every PDF reader, Courier (couriernew,
 * courier, monospace...) and Helvetica (helvetica, arial, sans-serif...);
 * text in the Windows-1252 set, so Italian accents and the euro sign.
 * Images: JPEG, and PNG without transparency.
 */
class KPdf {
	// properties of the old KPdf, same meaning
	public $mode = 'utf-8';
	public $format = 'A4';
	public $default_font_size = '';
	public $default_font = '';
	public $mgl = 0;
	public $mgr = 0;
	public $mgt = 0;
	public $mgb = 0;
	public $mgh = 0;
	public $mgf = 0;
	public $mirrorMargins = 0;
	public $keep_table_proportions = true;

	// "continues" at the bottom of the pages before the last one
	public $continuedText = 'SEGUE';

	const K = 2.8346456692913;   // points per millimetre
	const CELL_MARGIN = 1;       // mm of padding at the sides of a cell
	const IMAGE_DPI = 96;        // natural size of the images

	private static $formats = array(
		'A3' => array(297, 420), 'A4' => array(210, 297), 'A5' => array(148, 210),
		'LETTER' => array(215.9, 279.4), 'LEGAL' => array(215.9, 355.6),
	);

	private static $families = array(
		'courier' => 'Courier', 'couriernew' => 'Courier', 'freemono' => 'Courier', 'monospace' => 'Courier', 'mono' => 'Courier',
		'helvetica' => 'Helvetica', 'arial' => 'Helvetica', 'sans-serif' => 'Helvetica', 'sans' => 'Helvetica', 'dejavusans' => 'Helvetica', 'freesans' => 'Helvetica',
	);

	private $created = false;
	private $pageW;
	private $pageH;
	private $pages = array();      // content of each page
	private $page = -1;
	private $font = 'Courier';
	private $fontSize = 10;        // points
	private $fonts = array();      // base font => resource name
	private $images = array();     // file => image data
	private $background = null;
	private $logo = null;
	private $header = null;
	private $footer = null;
	private $lastFooter = null;

	public function create() {
		$format = is_array($this->format) ? $this->format : strtoupper((string) $this->format);
		$landscape = false;

		if (is_string($format) && substr($format, -2) === '-L') {
			$format = substr($format, 0, -2);
			$landscape = true;
		}

		if (is_array($format)) {
			list($this->pageW, $this->pageH) = array((float) $format[0], (float) $format[1]);
		} else {
			list($this->pageW, $this->pageH) = isset(self::$formats[$format]) ? self::$formats[$format] : self::$formats['A4'];
		}

		if ($landscape) {
			list($this->pageW, $this->pageH) = array($this->pageH, $this->pageW);
		}

		$this->fontSize = $this->default_font_size !== '' && (float) $this->default_font_size > 0 ? (float) $this->default_font_size : 10;
		$this->font = $this->family($this->default_font !== '' ? $this->default_font : 'helvetica');
		$this->created = true;
	}

	public function setFont($family) {
		$this->checkCreate();

		$this->font = $this->family($family);
	}

	public function setFontSize($size) {
		$this->checkCreate();

		$this->fontSize = (float) $size;
	}

	public function setBackground($image) {
		$this->background = $image;
	}

	// the name of the old KPdf, kept for the code that uses it
	public function setBackgroud($image) {
		$this->setBackground($image);
	}

	public function setLogo($image, $x, $y, $w, $h) {
		$this->logo = array($image, $x, $y, $w, $h);
	}

	public function setHeader($fields) {
		$this->header = $fields;
	}

	public function setFooter($fields) {
		$this->footer = $fields;
	}

	public function setLastFooter($fields) {
		$this->lastFooter = $fields;
	}

	public function fillBody($rows, $columns, $startX = 0, $startY = 0, $rowH = 10, $maxRowPage = 10, $offset = 1) {
		$this->checkCreate();

		$rows = array_values((array) $rows);
		$count = count($rows);
		$perPage = max(1, $maxRowPage - $offset);

		// all on one page if they fit, otherwise $perPage on each page but
		// the last, which can hold up to $maxRowPage
		$pages = $count <= $maxRowPage ? 1 : (int) ceil(($count - $maxRowPage) / $perPage) + 1;

		for ($p = 1; $p <= $pages; $p++) {
			$this->addPage();
			$this->fields($this->header, $p, $pages, true);
			$this->fields($this->footer, $p, $pages, false);

			$first = ($p - 1) * $perPage;
			$last = $p < $pages ? $first + $perPage : $count;

			for ($y = $first, $h = 0; $y < $last; $y++, $h++) {
				foreach ((array) $rows[$y] as $i => $text) {
					if (!isset($columns[$i])) {
						continue;
					}

					$column = $columns[$i];
					$this->cell($startX + $column['START'], $startY + $h * $rowH, $column['SIZE'], 0, (string) $text, isset($column['ALIGN']) ? $column['ALIGN'] : 'L');
				}
			}

			// no rows: one page with header and footer only, as the old KPdf
			if ($p < $pages) {
				$this->cell($startX, $startY + $perPage * $rowH, 0, 0, $this->continuedText, 'L');
			} elseif ($count > 0) {
				$this->fields($this->lastFooter, $p, $pages, false);
			}
		}
	}

	// A text where you want it, on the current page (a new one if there's none)
	public function text($x, $y, $text, $width = 0, $align = 'L') {
		$this->checkCreate();

		if ($this->page < 0) {
			$this->addPage();
		}

		$this->cell($x, $y, $width, 0, (string) $text, $align);
	}

	public function addPage() {
		$this->checkCreate();

		$this->pages[] = '';
		$this->page = count($this->pages) - 1;

		if ($this->background) {
			$this->image($this->background, 0, 0, 0, 0);
		}

		if ($this->logo) {
			$this->image($this->logo[0], $this->logo[1], $this->logo[2], $this->logo[3], $this->logo[4]);
		}
	}

	// $dest: I shows it in the browser, D downloads it, F writes the file,
	// S returns it as a string
	public function save($file, $dest = 'I') {
		$this->checkCreate();

		if (!$this->pages) {
			$this->addPage();
		}

		$pdf = $this->build();
		$dest = strtoupper((string) $dest);

		if ($dest === 'S') {
			return $pdf;
		}

		if ($dest === 'F') {
			return file_put_contents($file, $pdf) !== false;
		}

		while (ob_get_level()) {
			ob_end_clean();
		}

		$name = basename(str_replace('\\', '/', (string) $file));
		$name = $name !== '' ? $name : 'document.pdf';
		$plain = preg_replace('/[^A-Za-z0-9._ -]/', '_', $name);

		header('Content-Type: application/pdf');
		header('Content-Disposition: ' . ($dest === 'D' ? 'attachment' : 'inline') . '; filename="' . $plain . '"; filename*=UTF-8\'\'' . rawurlencode($name));
		header('Content-Length: ' . strlen($pdf));
		header('Cache-Control: private, no-store');
		header('X-Content-Type-Options: nosniff');

		echo $pdf;

		return '';
	}

	// --- drawing -----------------------------------------------------------

	private function fields($fields, $pageno, $pages, $wrap) {
		if (!$fields) {
			return;
		}

		foreach ($fields as $field) {
			$text = str_replace(array('{PAGENO}', '{PAGES}'), array($pageno, $pages), (string) $field['TEXT']);
			$align = isset($field['ALIGN']) ? $field['ALIGN'] : 'L';
			$size = isset($field['SIZE']) ? $field['SIZE'] : 0;
			$height = isset($field['HEIGHT']) ? (float) $field['HEIGHT'] : 0;

			if ($wrap) {
				$this->multiCell($field['X'], $field['Y'], $size, $height, $text, $align);
			} else {
				$this->cell($field['X'], $field['Y'], $size, 0, $text, $align);
			}
		}
	}

	// One line in a cell $w mm wide (0: up to the right edge) and $h high:
	// the text sits on the cell's middle line, with 1 mm of padding.
	private function cell($x, $y, $w, $h, $text, $align) {
		$text = $this->encode(str_replace(array("\r", "\n"), ' ', $text));

		if ($text === '') {
			return;
		}

		if ($w <= 0) {
			$w = $this->pageW - $this->mgr - $x;
		}

		$width = $this->width($text);

		switch (strtoupper((string) $align)) {
			case 'R':
				$dx = $w - self::CELL_MARGIN - $width;
				break;
			case 'C':
				$dx = ($w - $width) / 2;
				break;
			default:
				$dx = self::CELL_MARGIN;
		}

		$baseline = $y + 0.5 * $h + 0.3 * ($this->fontSize / self::K);

		$this->pages[$this->page] .= sprintf("BT /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n",
			$this->fontResource(), $this->fontSize, ($x + $dx) * self::K, ($this->pageH - $baseline) * self::K, $this->escape($text));
	}

	// A text that wraps within $w mm, lines $h mm apart (with $h 0 they
	// follow each other at the font's height); line breaks in the text are
	// kept.
	private function multiCell($x, $y, $w, $h, $text, $align) {
		if ($w <= 0) {
			$w = $this->pageW - $this->mgr - $x;
		}

		$max = $w - 2 * self::CELL_MARGIN;
		$step = $h > 0 ? $h : $this->fontSize / self::K * 1.2;
		$line = 0;

		foreach (preg_split('/\r\n|\r|\n/', $text) as $paragraph) {
			$current = '';

			foreach (preg_split('/[ \t]+/', trim($paragraph)) as $word) {
				$try = $current === '' ? $word : $current . ' ' . $word;

				if ($this->width($this->encode($try)) <= $max) {
					$current = $try;

					continue;
				}

				if ($current !== '') {
					$this->cell($x, $y + $line++ * $step, $w, $h, $current, $align);
				}

				// a word longer than the line is cut where it doesn't fit
				while ($this->width($this->encode($word)) > $max && strlen($word) > 1) {
					$cut = $this->fit($word, $max);
					$this->cell($x, $y + $line++ * $step, $w, $h, $cut, $align);
					$word = (string) substr($word, strlen($cut));
				}

				$current = $word;
			}

			$this->cell($x, $y + $line++ * $step, $w, $h, $current, $align);
		}
	}

	// longest start of $text (UTF-8) that fits in $max mm
	private function fit($text, $max) {
		$chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
		$out = '';

		foreach ($chars as $char) {
			if ($out !== '' && $this->width($this->encode($out . $char)) > $max) {
				break;
			}

			$out .= $char;
		}

		return $out;
	}

	private function image($file, $x, $y, $w, $h) {
		$info = $this->loadImage($file);

		if (!$info) {
			return;
		}

		// natural size at 96 dpi; one side given: the other in proportion
		if ($w <= 0 && $h <= 0) {
			$w = $info['w'] * 25.4 / self::IMAGE_DPI;
			$h = $info['h'] * 25.4 / self::IMAGE_DPI;

			// larger than the page: fitted to it (backgrounds are scanned forms)
			$scale = min(1, ($this->pageW - $x) / $w, ($this->pageH - $y) / $h);
			$w *= $scale;
			$h *= $scale;
		} elseif ($w <= 0) {
			$w = $h * $info['w'] / $info['h'];
		} elseif ($h <= 0) {
			$h = $w * $info['h'] / $info['w'];
		}

		$this->pages[$this->page] .= sprintf("q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q\n",
			$w * self::K, $h * self::K, $x * self::K, ($this->pageH - $y - $h) * self::K, $info['name']);
	}

	private function loadImage($file) {
		if (isset($this->images[$file])) {
			return $this->images[$file];
		}

		$data = is_string($file) && is_file($file) ? file_get_contents($file) : false;

		if ($data === false) {
			trigger_error('KPdf: image ' . $file . ' not found', E_USER_WARNING);

			return $this->images[$file] = false;
		}

		$info = strncmp($data, "\xFF\xD8", 2) === 0 ? $this->jpeg($data) : (strncmp($data, "\x89PNG\r\n\x1A\n", 8) === 0 ? $this->png($data) : false);

		if (!$info) {
			trigger_error('KPdf: ' . $file . ' is not a JPEG or a PNG without transparency', E_USER_WARNING);

			return $this->images[$file] = false;
		}

		$info['name'] = 'Im' . (count($this->images) + 1);

		return $this->images[$file] = $info;
	}

	// a JPEG goes into the PDF as it is: only its size and colours are read
	private function jpeg($data) {
		$pos = 2;
		$length = strlen($data);

		while ($pos + 4 <= $length) {
			if (ord($data[$pos]) !== 0xFF) {
				return false;
			}

			$marker = ord($data[$pos + 1]);

			if ($marker === 0xFF) {
				$pos++;

				continue;
			}

			$size = unpack('n', substr($data, $pos + 2, 2))[1];

			// start of frame (baseline, extended, progressive)
			if (in_array($marker, array(0xC0, 0xC1, 0xC2, 0xC3, 0xC5, 0xC6, 0xC7, 0xC9, 0xCA, 0xCB, 0xCD, 0xCE, 0xCF), true)) {
				$f = unpack('Cbits/nh/nw/Ccomponents', substr($data, $pos + 4, 6));
				$spaces = array(1 => '/DeviceGray', 3 => '/DeviceRGB', 4 => '/DeviceCMYK');

				if (!isset($spaces[$f['components']])) {
					return false;
				}

				return array(
					'w' => $f['w'], 'h' => $f['h'], 'data' => $data,
					'dict' => sprintf('/Width %d /Height %d /ColorSpace %s /BitsPerComponent %d /Filter /DCTDecode%s',
						$f['w'], $f['h'], $spaces[$f['components']], $f['bits'],
						// Adobe CMYK JPEGs are stored inverted
						$f['components'] == 4 ? ' /Decode [1 0 1 0 1 0 1 0]' : ''),
				);
			}

			$pos += 2 + $size;
		}

		return false;
	}

	// a PNG's compressed data goes into the PDF as it is, with its predictors
	private function png($data) {
		$h = unpack('Nw/Nh/Cbits/Ctype/Ccompression/Cfilter/Cinterlace', substr($data, 16, 13));

		if ($h['interlace'] || $h['bits'] > 8 || in_array($h['type'], array(4, 6), true)) {
			return false;   // interlaced, 16 bit or with an alpha channel
		}

		$colors = array(0 => 1, 2 => 3, 3 => 1);
		$pos = 33;
		$idat = '';
		$palette = '';

		while ($pos + 8 <= strlen($data)) {
			$chunk = unpack('Nlength/a4type', substr($data, $pos, 8));
			$body = substr($data, $pos + 8, $chunk['length']);

			if ($chunk['type'] === 'PLTE') {
				$palette = $body;
			} elseif ($chunk['type'] === 'IDAT') {
				$idat .= $body;
			} elseif ($chunk['type'] === 'tRNS') {
				return false;
			} elseif ($chunk['type'] === 'IEND') {
				break;
			}

			$pos += 12 + $chunk['length'];
		}

		if ($h['type'] == 3) {
			$space = sprintf('[/Indexed /DeviceRGB %d <%s>]', strlen($palette) / 3 - 1, bin2hex($palette));
		} else {
			$space = $h['type'] == 0 ? '/DeviceGray' : '/DeviceRGB';
		}

		return array(
			'w' => $h['w'], 'h' => $h['h'], 'data' => $idat,
			'dict' => sprintf('/Width %d /Height %d /ColorSpace %s /BitsPerComponent %d /Filter /FlateDecode /DecodeParms << /Predictor 15 /Colors %d /BitsPerComponent %d /Columns %d >>',
				$h['w'], $h['h'], $space, $h['bits'], $colors[$h['type']], $h['bits'], $h['w']),
		);
	}

	// --- text --------------------------------------------------------------

	private function family($family) {
		$key = strtolower(preg_replace('/[\s_]+/', '', (string) $family));

		return isset(self::$families[$key]) ? self::$families[$key] : 'Helvetica';
	}

	private function fontResource() {
		if (!isset($this->fonts[$this->font])) {
			$this->fonts[$this->font] = 'F' . (count($this->fonts) + 1);
		}

		return $this->fonts[$this->font];
	}

	// UTF-8 to Windows-1252, the encoding of the standard fonts
	private function encode($text) {
		$out = @iconv('UTF-8', 'Windows-1252//TRANSLIT', (string) $text);

		return $out === false ? (string) @iconv('UTF-8', 'Windows-1252//IGNORE', (string) $text) : $out;
	}

	private function escape($text) {
		return strtr($text, array('\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => '\\r'));
	}

	// width in mm of a Windows-1252 string in the current font and size
	private function width($text) {
		$units = 0;

		if ($this->font === 'Courier') {
			$units = 600 * strlen($text);
		} else {
			$widths = self::helvetica();

			for ($i = 0, $n = strlen($text); $i < $n; $i++) {
				$units += $widths[ord($text[$i])];
			}
		}

		return $units * $this->fontSize / 1000 / self::K;
	}

	// Helvetica: widths of the Windows-1252 characters, in 1/1000 of the size
	private static function helvetica() {
		static $widths = null;

		if ($widths === null) {
			$widths = array_fill(0, 256, 556);
			$ascii = array(278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
				556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
				1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
				667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
				333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
				556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584);

			foreach ($ascii as $i => $w) {
				$widths[32 + $i] = $w;
			}

			$latin = array(
				0xC0 => array(667, 667, 667, 667, 667, 667, 1000, 722, 667, 667, 667, 667, 278, 278, 278, 278),
				0xD0 => array(722, 722, 778, 778, 778, 778, 778, 584, 778, 722, 722, 722, 722, 667, 667, 611),
				0xE0 => array(556, 556, 556, 556, 556, 556, 889, 500, 556, 556, 556, 556, 278, 278, 278, 278),
				0xF0 => array(556, 556, 556, 556, 556, 556, 556, 584, 611, 556, 556, 556, 556, 500, 556, 500),
			);

			foreach ($latin as $start => $list) {
				foreach ($list as $i => $w) {
					$widths[$start + $i] = $w;
				}
			}

			foreach (array(0x80 => 556, 0x91 => 222, 0x92 => 222, 0x93 => 333, 0x94 => 333, 0x95 => 350, 0x96 => 556, 0x97 => 1000, 0xA0 => 278, 0xB0 => 400, 0xA9 => 737, 0xAE => 737) as $code => $w) {
				$widths[$code] = $w;
			}
		}

		return $widths;
	}

	// --- file --------------------------------------------------------------

	private function build() {
		$objects = array();   // number => body
		$n = 2;               // 1 catalog, 2 page tree

		$fontObjects = array();

		foreach ($this->fonts as $base => $name) {
			$fontObjects[$name] = ++$n;
			$objects[$n] = "<< /Type /Font /Subtype /Type1 /BaseFont /$base /Encoding /WinAnsiEncoding >>";
		}

		$imageObjects = array();

		foreach ($this->images as $info) {
			if ($info) {
				$imageObjects[$info['name']] = ++$n;
				$objects[$n] = array('<< /Type /XObject /Subtype /Image ' . $info['dict'] . ' /Length ' . strlen($info['data']) . ' >>', $info['data']);
			}
		}

		$resources = '<< /ProcSet [/PDF /Text /ImageB /ImageC /ImageI]';

		if ($fontObjects) {
			$resources .= ' /Font <<';

			foreach ($fontObjects as $name => $obj) {
				$resources .= " /$name $obj 0 R";
			}

			$resources .= ' >>';
		}

		if ($imageObjects) {
			$resources .= ' /XObject <<';

			foreach ($imageObjects as $name => $obj) {
				$resources .= " /$name $obj 0 R";
			}

			$resources .= ' >>';
		}

		$resources .= ' >>';
		$kids = array();

		foreach ($this->pages as $content) {
			$stream = gzcompress($content);
			$objects[++$n] = array('<< /Filter /FlateDecode /Length ' . strlen($stream) . ' >>', $stream);
			$kids[] = ++$n;
			$objects[$n] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources %s /Contents %d 0 R >>',
				$this->pageW * self::K, $this->pageH * self::K, $resources, $n - 1);
		}

		$objects[1] = '<< /Type /Catalog /Pages 2 0 R /OpenAction [' . $kids[0] . ' 0 R /FitH null] >>';
		$objects[2] = '<< /Type /Pages /Kids [' . implode(' 0 R ', $kids) . ' 0 R] /Count ' . count($kids) . ' >>';
		$objects[++$n] = '<< /Producer (K-iwi KPdf) /CreationDate (D:' . date('YmdHis') . ') >>';
		$info = $n;
		ksort($objects);

		$pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
		$offsets = array();

		foreach ($objects as $number => $body) {
			$offsets[$number] = strlen($pdf);
			$pdf .= $number . " 0 obj\n" . (is_array($body) ? $body[0] . "\nstream\n" . $body[1] . "\nendstream" : $body) . "\nendobj\n";
		}

		$xref = strlen($pdf);
		$pdf .= "xref\n0 " . ($n + 1) . "\n0000000000 65535 f \n";

		for ($i = 1; $i <= $n; $i++) {
			$pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
		}

		return $pdf . "trailer\n<< /Size " . ($n + 1) . " /Root 1 0 R /Info $info 0 R >>\nstartxref\n$xref\n%%EOF\n";
	}

	private function checkCreate() {
		if (!$this->created) {
			trigger_error('KPdf: call create() first', E_USER_ERROR);
		}
	}
}
?>
