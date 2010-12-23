<?php

/**
 * Multiselect class.
 */
class Multiselect {

	public static function getString(&$arr, $values = null) {
		if (!is_array($arr)) {
			$arr = array($arr);
		}
		foreach ($arr as $k => &$v) {
			if (!is_string($v)) {
				unset($arr[$k]);
			}
			if (($values !== null) && !in_array($v, $values)) {
				unset($arr[$k]);
				continue;
			}
		}
		return array_values($arr);		
	}
	
}
