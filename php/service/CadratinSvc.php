<?php

namespace service;

abstract class CadratinSvc
{

	private static function handleCsvFile(int $id, string $subdir)
	{
		// config
		$line_return_char = "¶";
		$cadratin_export_dir = "data/CADRATIN export";
		$csv_file = "$cadratin_export_dir/$subdir/$id.csv";

		// open file
		$csv = @fopen($csv_file, "r");
		if ($csv === FALSE) {
			die("cannot read CSV file : $csv_file");
		}

		// read file
		$data = [];
		while (($row = fgetcsv($csv, null, ";")) !== FALSE) {
			$data [] = $row;
		}
		fclose($csv);
		if(count($data) !== 2) {
			die("CSV file doesn't containe the right number of rows");
		}

		// make values have correct line return
		foreach($data[1] as &$val) {
			$val = trim(str_replace($line_return_char, PHP_EOL, $val));
		}

		// assemble rows into an associative array
		$data = array_combine($data[0], $data[1]);

		return $data;
	}


 	public static function handleEstimateFile (int $id)
	{
		// config
		$cadratin_estimate_subdir = "devis";
		$cadratin_production_subdir = "prod";
		$subdir = $cadratin_estimate_subdir;
		
		return self::handleCsvFile($id, $subdir);
	}


	public static function handleProdFile (int $id)
	{
		// config
		$cadratin_estimate_subdir = "devis";
		$cadratin_production_subdir = "prod";
		$subdir = $cadratin_production_subdir;

		return self::handleCsvFile($id, $subdir);
	}

}
