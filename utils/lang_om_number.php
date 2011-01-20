<?php

/**
 * lang_om_number function
 */
function lang_om_number($number, $subject, $mode = 0, $withoutnum = false) {
	$titles = explode('|',$subject);
	$lang = Daemon::$req->lang;
  if ($lang == 'en') {
		return ((!$withoutnum)?number_format($number).' ':'').(($number == 1)?$titles[0]:$titles[1]);
	}
  elseif ($lang == 'ru' or $lang == 'cz') {
		$cases = array(2,0,1,1,1,2);
		$n = ($number%100>4 && $number%100<20)?2:$cases[min($number%10,5)];
		if ($mode == 1) {
			if ($n == 0) {$n = 3;}
			elseif ($n == 1) {$n = 4;}
			elseif ($n == 2) {$n = 4;}
		}
		return ((!$withoutnum)?number_format($number).' ':'').$titles[$n];
	}
}

