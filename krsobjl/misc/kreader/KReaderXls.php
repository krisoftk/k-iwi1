<?php
/*
 * XLS (Excel 97-2003, BIFF8) for KReader. The file is an OLE2 compound
 * document; its "Workbook" stream is a sequence of BIFF records: the global
 * part (shared strings, list of sheets) and then one part per sheet with
 * its cells. Formulas give the result saved with them. Files of Excel 95
 * and older (BIFF5, stream "Book") aren't supported.
 */
class KReaderXls {
	const SIGNATURE = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

	// OLE2 chain markers
	const FREE = 0xFFFFFFFF;
	const END_OF_CHAIN = 0xFFFFFFFE;

	// BIFF records
	const BOF = 0x0809;
	const EOF = 0x000A;
	const BOUNDSHEET = 0x0085;
	const SST = 0x00FC;
	const CONTINUE_ = 0x003C;
	const LABELSST = 0x00FD;
	const LABEL = 0x0204;
	const NUMBER = 0x0203;
	const RK = 0x027E;
	const MULRK = 0x00BD;
	const BOOLERR = 0x0205;
	const FORMULA = 0x0006;
	const STRING = 0x0207;

	private static $errors = array(0x00 => '#NULL!', 0x07 => '#DIV/0!', 0x0F => '#VALUE!', 0x17 => '#REF!', 0x1D => '#NAME?', 0x24 => '#NUM!', 0x2A => '#N/A');

	public static function read($file) {
		$data = file_get_contents($file);

		if ($data === false || strncmp($data, self::SIGNATURE, 8) !== 0) {
			return false;
		}

		$stream = self::workbookStream($data);

		if ($stream === false) {
			trigger_error('KReader: no Excel 97-2003 workbook in the file (Excel 95 and older are not supported)', E_USER_WARNING);

			return false;
		}

		return self::parse($stream);
	}

	// --- OLE2 compound document ---------------------------------------------

	private static function workbookStream($data) {
		$length = strlen($data);

		if ($length < 512) {
			return false;
		}

		$h = unpack('vminor/vmajor/vorder/vsectorShift/vminiShift', substr($data, 0x18, 10));
		$sectorSize = 1 << $h['sectorShift'];
		$miniSize = 1 << $h['miniShift'];

		if ($sectorSize !== 512 && $sectorSize !== 4096) {
			return false;
		}

		$h += unpack('VfatSectors/VdirStart/Vtx/Vcutoff/VminiFatStart/VminiFatSectors/VdifatStart/VdifatSectors', substr($data, 0x2C, 32));

		$sector = function ($n) use ($data, $sectorSize, $length) {
			$offset = ($n + 1) * $sectorSize;

			return $offset + $sectorSize <= $length ? substr($data, $offset, $sectorSize) : (string) substr($data, $offset);
		};

		// sectors of the FAT: 109 in the header, the others in the DIFAT chain
		$fatSectors = array_values(unpack('V109', substr($data, 0x4C, 436)));
		$next = $h['difatStart'];
		$per = $sectorSize / 4 - 1;

		for ($i = 0; $i < $h['difatSectors'] && $next < self::END_OF_CHAIN; $i++) {
			$entries = array_values(unpack('V' . ($per + 1), $sector($next)));
			$next = array_pop($entries);
			$fatSectors = array_merge($fatSectors, $entries);
		}

		$fat = array();

		foreach (array_slice($fatSectors, 0, $h['fatSectors']) as $n) {
			if ($n < self::END_OF_CHAIN) {
				$fat = array_merge($fat, array_values(unpack('V' . ($sectorSize / 4), $sector($n))));
			}
		}

		$chain = function ($start, $table, $read, $limit) {
			$out = '';
			$seen = array();

			for ($n = $start; $n < self::END_OF_CHAIN && isset($table[$n]) && !isset($seen[$n]) && count($seen) < $limit; $n = $table[$n]) {
				$seen[$n] = true;
				$out .= $read($n);
			}

			return $out;
		};

		$maxSectors = intdiv($length, $sectorSize) + 1;
		$directory = $chain($h['dirStart'], $fat, $sector, $maxSectors);

		$entries = array();

		for ($pos = 0; $pos + 128 <= strlen($directory); $pos += 128) {
			$e = unpack('vnameLen/Ctype', substr($directory, $pos + 64, 3));
			$e += unpack('Vstart/Vsize', substr($directory, $pos + 116, 8));
			$e['name'] = $e['nameLen'] >= 2 ? iconv('UTF-16LE', 'UTF-8', substr($directory, $pos, $e['nameLen'] - 2)) : '';
			$entries[] = $e;
		}

		if (!$entries || $entries[0]['type'] != 5) {
			return false;
		}

		foreach ($entries as $e) {
			if ($e['type'] != 2 || ($e['name'] !== 'Workbook' && $e['name'] !== 'WORKBOOK')) {
				continue;
			}

			if ($e['size'] >= $h['cutoff']) {
				return substr($chain($e['start'], $fat, $sector, $maxSectors), 0, $e['size']);
			}

			// small stream: in the mini stream, held by the root entry
			$miniStream = $chain($entries[0]['start'], $fat, $sector, $maxSectors);
			$miniFat = array();
			$raw = $chain($h['miniFatStart'], $fat, $sector, $maxSectors);

			if ($raw !== '') {
				$miniFat = array_values(unpack('V' . (strlen($raw) / 4), $raw));
			}

			$mini = function ($n) use ($miniStream, $miniSize) {
				return (string) substr($miniStream, $n * $miniSize, $miniSize);
			};

			return substr($chain($e['start'], $miniFat, $mini, count($miniFat) + 1), 0, $e['size']);
		}

		return false;
	}

	// --- BIFF8 ---------------------------------------------------------------

	private static function parse($stream) {
		$length = strlen($stream);
		$pos = 0;

		// globals: from the first BOF to its EOF
		$strings = array();
		$sheets = array();
		$first = true;

		while ($pos + 4 <= $length) {
			$r = unpack('vid/vlen', substr($stream, $pos, 4));
			$body = substr($stream, $pos + 4, $r['len']);
			$pos += 4 + $r['len'];

			if ($first) {
				if ($r['id'] != self::BOF || strlen($body) < 4) {
					return false;
				}

				$bof = unpack('vversion/vtype', $body);

				if ($bof['version'] != 0x0600) {
					trigger_error('KReader: Excel 95 and older files are not supported', E_USER_WARNING);

					return false;
				}

				$first = false;

				continue;
			}

			if ($r['id'] == self::EOF) {
				break;
			}

			if ($r['id'] == self::BOUNDSHEET) {
				$s = unpack('Voffset/Cstate/Ctype/Clen/Cflags', $body);

				if ($s['type'] == 0) {
					$sheets[] = array('offset' => $s['offset'], 'name' => self::chars(substr($body, 8), $s['len'], $s['flags'] & 1));
				}
			} elseif ($r['id'] == self::SST) {
				$parts = array($body);

				while ($pos + 4 <= $length) {
					$c = unpack('vid/vlen', substr($stream, $pos, 4));

					if ($c['id'] != self::CONTINUE_) {
						break;
					}

					$parts[] = substr($stream, $pos + 4, $c['len']);
					$pos += 4 + $c['len'];
				}

				$strings = self::sst($parts);
			}
		}

		$book = new KReaderBook();

		foreach ($sheets as $sheet) {
			$book->addSheet(new KReaderSheet($sheet['name'], self::cells($stream, $sheet['offset'], $strings)));
		}

		return $book->getSheetCount() ? $book : false;
	}

	private static function cells($stream, $pos, $strings) {
		$length = strlen($stream);
		$rows = array();
		$pending = null;   // formula waiting for its STRING record

		if (substr($stream, $pos, 2) !== pack('v', self::BOF)) {
			return $rows;
		}

		$pos += 4 + unpack('v', substr($stream, $pos + 2, 2))[1];

		while ($pos + 4 <= $length) {
			$r = unpack('vid/vlen', substr($stream, $pos, 4));
			$body = substr($stream, $pos + 4, $r['len']);
			$pos += 4 + $r['len'];

			switch ($r['id']) {
				case self::EOF:
					return $rows;

				case self::LABELSST:
					$c = unpack('vrow/vcol/vxf/Vindex', $body);
					$rows[$c['row'] + 1][$c['col']] = isset($strings[$c['index']]) ? $strings[$c['index']] : '';
					break;

				case self::LABEL:
					$c = unpack('vrow/vcol/vxf/vlen/Cflags', $body);
					$rows[$c['row'] + 1][$c['col']] = self::chars(substr($body, 9), $c['len'], $c['flags'] & 1);
					break;

				case self::NUMBER:
					$c = unpack('vrow/vcol/vxf/enumber', $body);
					$rows[$c['row'] + 1][$c['col']] = (float) $c['number'];
					break;

				case self::RK:
					$c = unpack('vrow/vcol/vxf/Vrk', $body);
					$rows[$c['row'] + 1][$c['col']] = self::rk($c['rk']);
					break;

				case self::MULRK:
					$c = unpack('vrow/vcol', $body);
					$count = intdiv(strlen($body) - 6, 6);

					for ($i = 0; $i < $count; $i++) {
						$rows[$c['row'] + 1][$c['col'] + $i] = self::rk(unpack('V', substr($body, 4 + $i * 6 + 2, 4))[1]);
					}
					break;

				case self::BOOLERR:
					$c = unpack('vrow/vcol/vxf/Cvalue/Cerror', $body);
					$rows[$c['row'] + 1][$c['col']] = $c['error'] ? (isset(self::$errors[$c['value']]) ? self::$errors[$c['value']] : '#N/A') : (bool) $c['value'];
					break;

				case self::FORMULA:
					$c = unpack('vrow/vcol/vxf', $body);
					$result = substr($body, 6, 8);

					if (substr($result, 6, 2) === "\xFF\xFF") {
						$kind = ord($result[0]);

						if ($kind == 0) {
							$pending = array($c['row'] + 1, $c['col']);
						} elseif ($kind == 1) {
							$rows[$c['row'] + 1][$c['col']] = (bool) ord($result[2]);
						} elseif ($kind == 2) {
							$code = ord($result[2]);
							$rows[$c['row'] + 1][$c['col']] = isset(self::$errors[$code]) ? self::$errors[$code] : '#N/A';
						} else {
							$rows[$c['row'] + 1][$c['col']] = '';
						}
					} else {
						$rows[$c['row'] + 1][$c['col']] = (float) unpack('e', $result)[1];
					}
					break;

				case self::STRING:
					if ($pending !== null && strlen($body) >= 3) {
						$s = unpack('vlen/Cflags', $body);
						$rows[$pending[0]][$pending[1]] = self::chars(substr($body, 3), $s['len'], $s['flags'] & 1);
					}

					$pending = null;
					break;
			}
		}

		return $rows;
	}

	// Shared string table. A string can go on in the next CONTINUE record:
	// when its characters do, that record starts with a new options byte
	// (8 or 16 bit characters from there on).
	private static function sst($parts) {
		$strings = array();
		$data = $parts[0];
		$part = 0;
		$pos = 8;
		$total = unpack('V', substr($data, 4, 4))[1];

		$next = function () use (&$data, &$part, &$pos, $parts) {
			$part++;

			if (!isset($parts[$part])) {
				return false;
			}

			$data = $parts[$part];
			$pos = 0;

			return true;
		};

		for ($i = 0; $i < $total; $i++) {
			if ($pos >= strlen($data) && !$next()) {
				break;
			}

			if ($pos + 3 > strlen($data)) {
				break;
			}

			$h = unpack('vchars/Cflags', substr($data, $pos, 3));
			$pos += 3;
			$runs = 0;
			$ext = 0;

			if ($h['flags'] & 0x08) {
				$runs = unpack('v', substr($data, $pos, 2))[1];
				$pos += 2;
			}

			if ($h['flags'] & 0x04) {
				$ext = unpack('V', substr($data, $pos, 4))[1];
				$pos += 4;
			}

			$wide = $h['flags'] & 1;
			$need = $h['chars'];
			$text = '';

			while (true) {
				$avail = $wide ? intdiv(strlen($data) - $pos, 2) : strlen($data) - $pos;
				$take = min($need, max(0, $avail));
				$text .= self::chars(substr($data, $pos, $wide ? $take * 2 : $take), $take, $wide);
				$pos += $wide ? $take * 2 : $take;
				$need -= $take;

				if ($need <= 0 || !$next()) {
					break;
				}

				$wide = ord($data[0]) & 1;
				$pos = 1;
			}

			$strings[] = $text;

			// formatting runs and phonetic data: skipped, possibly across records
			$skip = 4 * $runs + $ext;

			while ($skip > 0) {
				$avail = strlen($data) - $pos;

				if ($skip <= $avail) {
					$pos += $skip;
					$skip = 0;
				} else {
					$skip -= $avail;

					if (!$next()) {
						break;
					}
				}
			}
		}

		return $strings;
	}

	// characters: 16 bit (UTF-16LE) or 8 bit (the low byte of UTF-16, Latin-1)
	private static function chars($bytes, $count, $wide) {
		if ($wide) {
			return (string) iconv('UTF-16LE', 'UTF-8//IGNORE', substr($bytes, 0, $count * 2));
		}

		return (string) iconv('ISO-8859-1', 'UTF-8', substr($bytes, 0, $count));
	}

	// RK number: a 30-bit integer or the top 30 bits of a double, maybe / 100
	private static function rk($rk) {
		if ($rk & 0x02) {
			$value = ($rk & 0x80000000) ? (($rk >> 2) - 0x40000000) : ($rk >> 2);
		} else {
			$value = unpack('e', pack('VV', 0, $rk & 0xFFFFFFFC))[1];
		}

		if ($rk & 0x01) {
			$value /= 100;
		}

		return (float) $value;
	}
}
?>
