<?php

class helper_date extends object {

	/**
	 * Date timestamp
	 *
	 * @var int
	 */
	protected $timestamp;

	protected $isNull = false;

	protected function afterinit() {
		$this->timestamp = $this->cfg->timestamp;
	}

	/**
	 * Set a new date or a date element
	 *
	 * @param string|int $date The value to set
	 * @param string $type The value type (timestamp, date, time or a key from getArray)
	 */
	public function set($date, $type='date', $len=null) {
		$this->isNull = true;
		if (is_null($date))
			$this->isNull = true;
		else if ($type == 'timestamp')
			$this->timestamp = $date;
		else if ($type == 'date')
			$this->timestamp = strtotime($date);
		else if ($type == 'formatDate') {
			$format = $this->cfg->getInArray('formatDate', $len);
			$elts = array('h', 'i', 's', 'o', 'YYYY', 'YY', 'MMM', 'MM', 'M', 'D', 'EEE', 'EE');
			$elts = array(
				'h'=>'([0-9]{1,2})',
				'i'=>'([0-9]{1,2})',
				's'=>'([0-9]{1,2})',
				'o'=>'([0-9]{4})',
				'YYYY'=>'([0-9]{4})',
				'YY'=>'([0-9]{2})',
				'MMM'=>'(.*)',
				'MM'=>'(.{3})',
				'M'=>'([0-9]{1,2})',
				'D'=>'([0-9]{1,2})'
            );
            $pos = array();
            $formatWork = $format;
			foreach($elts as $k=>$e) {
				$tmp = strpos($formatWork, $k);
				if ($tmp !== false) {
					$pos[$k] = $tmp;
					$formatWork = str_replace($k, str_repeat('Z', strlen($k)), $formatWork);
				}
			}
			$formatWork = str_replace(array_keys($elts), $elts, $format);
			asort($pos);
			preg_match('`'.$formatWork.'`', $date, $matches);
			if (!empty($matches)) {
				$ret = array();
				$i=1;
				$ret = array();
				foreach($pos as $k=>$v) {
					$key = null;
					switch($k) {
						case 'YYYY':
						case 'YY':
							$ret['y'] = $matches[$i];
							break;
						case 'MMM':
						case 'MM':
							$key = $k=='MMM'?'l':'m';
							$j = 1;
							foreach($this->cfg->month as $vv) {
								if (strtolower($vv[$key]) == strtolower($matches[$i])) {
									$ret['m'] = $j;
								}
								$j++;
							}
							break;
						case 'M':
							$ret['m'] = $matches[$i];
							break;
						case 'D':
							$ret['d'] = $matches[$i];
							break;
						default:
							$ret[$k] = $matches[$i];
							break;
					}
					$i++;
				}
				$this->setArray($ret);
			}
		} else if ($type == 'time') {
			$time = strtotime($date);
			$tmp = $this->getArray();
			$this->set($tmp['y'].'-'.$tmp['m'].'-'.$tmp['d'], 'date');
			$this->timestamp+= strtotime($date) - strtotime(date('Y-m-d'));
		} else {
			$tmp = $this->getArray();
			$tmp[$type] = $date;
			$this->setArray($tmp);
		}
	}

	/**
	 * Get an array describing the date
	 *
	 * @return array
	 */
	public function getArray() {
		return array(
		    'y'=>$this->get('y'),
		    'm'=>$this->get('m'),
		    'd'=>$this->get('d'),
		    'l'=>$this->get('l'),
		    'h'=>$this->get('h'),
		    'i'=>$this->get('i'),
		    's'=>$this->get('s'),
		    '0'=>$this->get('0')
		);
	}

	/**
	 * Set a new date element with an array
	 *
	 * @param array $values New values
	 * @see getArray
	 */
	public function setArray(array $values) {
		$tmp = array_merge($this->getArray(), $values);
		$this->timestamp = strtotime($tmp['y'].'-'.$tmp['m'].'-'.$tmp['d'].' '.$tmp['h'].':'.$tmp['i'].':'.$tmp['s']);
	}

	/**
	 * Get a date element
	 *
	 * @param sting $part Date type, could be any string used in the date PHP function
	 * @return string|int
	 */
	public function get($part=null) {
		if (is_null($part))
			return $this->timestamp;
		switch($part) {
			case 'y':
				return date('Y', $this->timestamp);
				break;
			default:
				return date($part, $this->timestamp);
				break;
		}
	}

	/**
	 * Format the date
	 *
	 * @param string $format Date format (date, time, or datetime)
	 * @param string $len Length needed (short, medium, long, full, fullMed or mysql)
	 * @return string The date formated
	 */
	public function format($type=null, $len=null) {
		if ($this->isNull)
			return null;
		if (is_null($type))
			$type = $this->cfg->getInArray('defaultFormat', 'type');
		if (is_null($len))
			$len = $this->cfg->getInArray('defaultFormat', 'len');

		$time = $this->getArray();
        $form = $this->cfg->getInArray('format'.ucfirst($type), $len);
        if ($type == 'datetime') {
            $form = str_replace(
                array('date', 'time'),
                array(
                    $this->cfg->getInArray('formatDate', $len),
                    $this->cfg->getInArray('formatTime', $len),
                ),
                $form);
        }

        $month = $this->cfg->getInArray('month', 'm'.intval($time['m']));
        $day = $this->cfg->getInArray('day', 'd'.intval($time['l']));
        $places = array(
            'h'=>str_pad($time['h'], 2, '0', STR_PAD_LEFT),
            'i'=>str_pad($time['i'], 2, '0', STR_PAD_LEFT),
            's'=>str_pad($time['s'], 2, '0', STR_PAD_LEFT),
            'o'=>($time['o'] <0? '-' : '+').str_pad($time['o'], 4, '0', STR_PAD_LEFT),
            'YYYY'=>$time['y'],
            'YY'=>substr($time['y'], -2),
            'MMM'=>$month['l'],
            'MM'=>$month['m'],
            'M'=>str_pad($time['m'], 2, '0', STR_PAD_LEFT),
            'D'=>str_pad($time['d'], 2, '0', STR_PAD_LEFT),
            'EEE'=>$day['l'],
            'EE'=>$day['m'],
        );
        return str_replace(array_keys($places), $places, $form);
	}

	/**
	 * Timeago in string to be shown
	 *
	 * @param helper_date|null $d Date to compare to or null to use the current time
	 * @return string The timeago string
	 */
	public function timeago(helper_date $d=null) {
		$timestamp = $this->get() - (is_null($d)? time() : $d->get());
		$timestampAbs = abs($timestamp);

		$timeago = $this->cfg->getInArray('timeago', ($timestamp < 0) ? '-' : '+');
		$tmp = array();
		$tmp['s'] = 1;
		$tmp['i'] = 60;
		$tmp['h'] = $tmp['i']*60;
		$tmp['d'] = $tmp['h']*24;
		$tmp['w'] = $tmp['d']*7;
		$tmp['m'] = $tmp['d']*30;
		$tmp['y'] = $tmp['d']*365;
		$tmp = array_reverse($tmp, true);

		foreach($tmp as $k=>$v) {
			$nb = intval($timestampAbs/$v);
			if ($nb == 1)
				return sprintf($timeago[$k]['one'], 1);
			else if ($nb > 1)
				return sprintf($timeago[$k]['mul'], $nb);
		}

		return $this->cfg->getInArray('timeago', 'now');
	}

	public function __toString() {
		return $this->format();
	}
}