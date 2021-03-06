<?php
namespace WakePHP\Core\Traits;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;

/**
 * Datetime
 *
 * @package Core
 *
 * @author  Zorin Vasily <maintainer@daemon.io>
 */

trait Datetime {
	
	
	/**
	 * @param string $format
	 * @param integer $ts
	 * @return mixed
	 */
	public function date($format, $ts = null) { // @todo
		if ($ts === null) {
			$ts = time();
		}
		$t      = array();
		$format = preg_replace_callback('~%n2?~', function ($m) use (&$t) {
			$t[] = $m[0];
			return "\x01";
		}, $format);
		$r      = date($format, $ts);
		$r      = preg_replace_callback('~\x01~s', function ($m) use ($t, $ts) {
			static $i = 0;
			switch ($t[$i++]) {
				case "%n":
					return $this->monthes[date('n', $ts)];
				case "%n2":
					return $this->monthes2[date('n', $ts)];
			}
		}, $r);
		return $r;
	}

	/**
	 * @param $st
	 * @param $fin
	 * @return array
	 */
	public function date_period($st, $fin) {
		if ((is_int($st)) || (ctype_digit($st))) {
			$st = $this->date('d-m-Y-H-i-s', $st);
		}
		$st = explode('-', $st);
		if ((is_int($fin)) || (ctype_digit($fin))) {
			$fin = $this->date('d-m-Y-H-i-s', $fin);
		}
		$fin = explode('-', $fin);
		if (($seconds = $fin[5] - $st[5]) < 0) {
			$fin[4]--;
			$seconds += 60;
		}
		if (($minutes = $fin[4] - $st[4]) < 0) {
			$fin[3]--;
			$minutes += 60;
		}
		if (($hours = $fin[3] - $st[3]) < 0) {
			$fin[0]--;
			$hours += 24;
		}
		if (($days = $fin[0] - $st[0]) < 0) {
			$fin[1]--;
			$days += $this->date('t', mktime(1, 0, 0, $fin[1], $fin[0], $fin[2]));
		}
		if (($months = $fin[1] - $st[1]) < 0) {
			$fin[2]--;
			$months += 12;
		}
		$years = $fin[2] - $st[2];
		return array($seconds, $minutes, $hours, $days, $months, $years);
	}
	
	public function date_period_text_($date_start, $date_finish) {
		$result = $this->date_period($date_start,$date_finish);
		$str  = '';
 		if ($result[5] > 0) {
 			$str .= $result[5].' '.$this->gettext('_YEARS'). ' ';
 		}
 		if ($result[4] > 0) {$str .= $result[4].' '.$this->gettext('_MONTHS'). ' ';}
 		if ($result[3] > 0) {$str .= $result[3].' '.$this->gettext('_DAYS'). ' ';}
 		if ($result[2] > 0) {$str .= $result[2].' '.$this->gettext('_HOURS'). ' ';}
 		if ($result[1] > 0) {$str .= $result[1].' '.$this->gettext('_MINUTES'). ' ';}
 		if ($result[0] > 0 or $str == '') {$str .= $result[0].' '.$this->gettext('_SECONDS'). ' ';}
 		return trim($str);
	}
	public function date_period_text($date_start, $date_finish) {
		$result = $this->date_period($date_start,$date_finish);
		$str  = '';
 		if ($result[5] > 0) {
 			$str .= $result[5].' year(s) ';
 		}
 		if ($result[4] > 0) {$str .= $result[4].' month(s) ';}
 		if ($result[3] > 0) {$str .= $result[3].' day(s) ';}
 		if ($result[2] > 0) {$str .= $result[2].' hour(s) ';}
 		if ($result[1] > 0) {$str .= $result[1].' minute(s) ';}
 		if ($result[0] > 0 or $str === '') {$str .= $result[0].' second(s) ';}
 		return rtrim($str);
	}

	/**
	 * @param $str
	 * @return int
	 */
	public function strtotime($str) {
		return \WakePHP\Utils\Strtotime::parse($str);
	}
}
