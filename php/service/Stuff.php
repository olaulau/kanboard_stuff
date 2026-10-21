<?php
namespace service;

use Base;

abstract class Stuff
{

	public static function get_config_from_code_compta (string $searched_code_compta) : array
	{
		$f3 = Base::instance();
		$cadratins = $f3->get("cadratin");
		foreach ($cadratins as $entreprise => $cadratin) {
			foreach ($cadratin ["code_compta_color"] as $color => $codes_compta) {
				if (is_int($codes_compta)) {
					$codes_compta = [$codes_compta];
				}
				foreach ($codes_compta as $code_compta) {
					if (strpos ($searched_code_compta, strval ($code_compta)) === 0) {
						break 3;
					}
				}
			}
		}
		return [$color, $cadratin];
	}

}
