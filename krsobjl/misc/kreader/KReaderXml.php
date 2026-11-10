<?php
/*
 * A small XML reader for the spreadsheet files, written so KReader doesn't
 * need the xml/SimpleXML extensions. It is enough for well-formed documents
 * like the ones Excel and LibreOffice write: no DTD, entities only the
 * standard ones and the numeric references.
 *
 * tokens() returns, in document order:
 *   array('open',  'name', array(attributes), selfClosing)
 *   array('close', 'name')
 *   array('text',  'decoded text')
 * Names keep their prefix ("ss:Data"); local() drops it.
 */
class KReaderXml {
	public static function tokens($xml) {
		$xml = (string) $xml;

		// a UTF-16 document becomes UTF-8 first
		if (strncmp($xml, "\xFF\xFE", 2) === 0 || strncmp($xml, "\xFE\xFF", 2) === 0) {
			$xml = iconv(strncmp($xml, "\xFF\xFE", 2) === 0 ? 'UTF-16LE' : 'UTF-16BE', 'UTF-8', substr($xml, 2));
		}

		if (strncmp($xml, "\xEF\xBB\xBF", 3) === 0) {
			$xml = substr($xml, 3);
		}

		$tokens = array();
		$length = strlen($xml);
		$pos = 0;

		while ($pos < $length) {
			$lt = strpos($xml, '<', $pos);

			if ($lt === false) {
				$lt = $length;
			}

			if ($lt > $pos) {
				$tokens[] = array('text', self::decode(substr($xml, $pos, $lt - $pos)));
			}

			if ($lt >= $length) {
				break;
			}

			if (substr_compare($xml, '<!--', $lt, 4) === 0) {
				$end = strpos($xml, '-->', $lt + 4);
				$pos = $end === false ? $length : $end + 3;
			} elseif (substr_compare($xml, '<![CDATA[', $lt, 9) === 0) {
				$end = strpos($xml, ']]>', $lt + 9);
				$end = $end === false ? $length : $end;
				$tokens[] = array('text', substr($xml, $lt + 9, $end - $lt - 9));
				$pos = $end + 3;
			} elseif ($xml[$lt + 1] === '?' || $xml[$lt + 1] === '!') {
				$end = strpos($xml, '>', $lt);
				$pos = $end === false ? $length : $end + 1;
			} else {
				$end = self::tagEnd($xml, $lt);

				if ($end === false) {
					break;
				}

				$tag = substr($xml, $lt + 1, $end - $lt - 1);
				$pos = $end + 1;

				if ($tag !== '' && $tag[0] === '/') {
					$tokens[] = array('close', trim(substr($tag, 1)));
				} else {
					$self = substr($tag, -1) === '/';

					if ($self) {
						$tag = substr($tag, 0, -1);
					}

					preg_match('/^\s*([^\s\/>]+)/', $tag, $m);
					$name = isset($m[1]) ? $m[1] : '';
					$tokens[] = array('open', $name, self::attributes(substr($tag, strlen($m[0] ?? ''))), $self);
				}
			}
		}

		return $tokens;
	}

	// local name, without the namespace prefix
	public static function local($name) {
		$colon = strrpos($name, ':');

		return $colon === false ? $name : substr($name, $colon + 1);
	}

	// attribute by local name
	public static function attr($attributes, $name, $default = null) {
		foreach ($attributes as $key => $value) {
			if ($key === $name || self::local($key) === $name) {
				return $value;
			}
		}

		return $default;
	}

	public static function decode($text) {
		if (strpos($text, '&') === false) {
			return $text;
		}

		return preg_replace_callback('/&(#x[0-9A-Fa-f]+|#[0-9]+|amp|lt|gt|quot|apos);/', function ($m) {
			switch ($m[1]) {
				case 'amp': return '&';
				case 'lt': return '<';
				case 'gt': return '>';
				case 'quot': return '"';
				case 'apos': return "'";
			}

			$code = $m[1][1] === 'x' ? hexdec(substr($m[1], 2)) : (int) substr($m[1], 1);

			return KReaderXml::utf8($code);
		}, $text);
	}

	public static function utf8($code) {
		if ($code < 0x80) {
			return chr($code);
		}

		if ($code < 0x800) {
			return chr(0xC0 | ($code >> 6)) . chr(0x80 | ($code & 0x3F));
		}

		if ($code < 0x10000) {
			return chr(0xE0 | ($code >> 12)) . chr(0x80 | (($code >> 6) & 0x3F)) . chr(0x80 | ($code & 0x3F));
		}

		if ($code < 0x110000) {
			return chr(0xF0 | ($code >> 18)) . chr(0x80 | (($code >> 12) & 0x3F)) . chr(0x80 | (($code >> 6) & 0x3F)) . chr(0x80 | ($code & 0x3F));
		}

		return '';
	}

	// position of the ">" closing the tag that starts at $lt, skipping the
	// ones inside quoted attribute values
	private static function tagEnd($xml, $lt) {
		if (!preg_match('/\G<(?:[^"\'>]++|"[^"]*+"|\'[^\']*+\')*+>/', $xml, $m, 0, $lt)) {
			return false;
		}

		return $lt + strlen($m[0]) - 1;
	}

	private static function attributes($text) {
		$attributes = array();

		if (preg_match_all('/([^\s=]+)\s*=\s*("([^"]*)"|\'([^\']*)\')/', $text, $all, PREG_SET_ORDER)) {
			foreach ($all as $m) {
				$attributes[$m[1]] = self::decode(isset($m[4]) ? $m[4] : $m[3]);
			}
		}

		return $attributes;
	}
}
?>
