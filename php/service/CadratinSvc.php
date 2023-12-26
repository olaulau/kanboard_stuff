<?php

namespace service;

use Base;
use ErrorException;

abstract class CadratinSvc
{
	
	public static $csv_field_separator = ";";
	public static $csv_field_line_return = "¶";
	public static $cadratin_estimate_subdir = "devis";
	public static $cadratin_production_subdir = "prod";
	public static $cadratin_done_subdir = "done";
	public static $cadratin_error_subdir = "error";
	
	
	private static function handleCsvFile(string $subdir, string $filename)
	{
		// open file
		$f3 = Base::instance();
		$csv_file = "data/$subdir/$filename";
		$csv = @fopen($csv_file, "r");
		if ($csv === FALSE) {
			throw new ErrorException("cannot read CSV file : $csv_file");
		}
		
		// read file
		$data = [];
		while (($row = fgetcsv($csv, null, self::$csv_field_separator)) !== FALSE) {
			$data [] = $row;
		}
		fclose($csv);
		if(count($data) !== 2) {
			throw new ErrorException("CSV file doesn't contain 2 rows");
		}
		
		// make values have correct line return
		foreach($data[1] as &$val) {
			$val = trim(str_replace(self::$csv_field_line_return, PHP_EOL, $val));
		}
		
		// assemble rows into an associative array
		if(count($data[0]) !== count($data[1])) { // not same number of columns in both rows
			$subjet = "incorrect CSV format";
			$message = <<< EOT
			incorrect CSV format (not the same number of columns in both rows)
			probably a field separator ('
			EOT;
			$message .= self::$csv_field_separator;
			$message .= <<< EOT
			') in the e-mail field !
			Please :
			- first fix the estimate in cadratin
			- then duplicate it
			- finally remove the original one
			EOT;
			$attachments = [$csv_file];
			KanboardSvc::send_email($subjet, nl2br($message), $attachments);
			throw new ErrorException($message);
		}
		$data = array_combine($data[0], $data[1]);

		return $data;
	}


 	public static function handleEstimateFile (string $filename)
	{
		return self::handleCsvFile(self::$cadratin_estimate_subdir, $filename);
	}


	public static function handleProdFile (string $filename)
	{
		return self::handleCsvFile(self::$cadratin_production_subdir, $filename);
	}

}
