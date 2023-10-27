<?php

namespace service;

use ErrorException;

abstract class CadratinSvc
{
	
	public static $line_return_char = "¶";
	public static $cadratin_export_dir = "data/CADRATIN export";
	public static $cadratin_estimate_subdir = "devis";
	public static $cadratin_production_subdir = "prod";
	public static $cadratin_done_subdir = "done";
	public static $cadratin_error_subdir = "error";

	
	private static function handleCsvFile(string $subdir, string $filename)
	{
		// open file
		$csv_file = self::$cadratin_export_dir  ."/$subdir/$filename";
		$csv = @fopen($csv_file, "r");
		if ($csv === FALSE) {
			throw new ErrorException("cannot read CSV file : $csv_file");
		}

		// read file
		$data = [];
		while (($row = fgetcsv($csv, null, ";")) !== FALSE) {
			$data [] = $row;
		}
		fclose($csv);
		if(count($data) !== 2) {
			throw new ErrorException("CSV file doesn't contain 2 rows");
		}

		// make values have correct line return
		foreach($data[1] as &$val) {
			$val = trim(str_replace(self::$line_return_char, PHP_EOL, $val));
		}

		// assemble rows into an associative array
		if(count($data[0]) !== count($data[1])) {
			throw new ErrorException("incorrect CSV format (not the same nomber of columns in both rows)");
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
