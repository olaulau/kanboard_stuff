<?php
namespace controller;

use ErrorException;
use Exception;
use service\CadratinSvc;
use service\KanboardApiSvc;
use service\KanboardSvc;
use service\KanboardTaskApiSvc;

class IndexCtrl
{

	public static function beforeRoute ()
	{
		
	}
	
	
	public static function afterRoute ()
	{
		
	}

	
	public static function indexGET ($f3)
	{
		$PAGE = [
			"title" => "accueil",
		];
		$f3->set("PAGE", $PAGE);

		$view = new \View();
		echo $view->render('index.phtml');
	}
	
	
	public static function priorityGET ($f3)
	{
		$f3->set("PAGE.title", "Planning par ordre de délai");
		
		// get conf vars
		$project_id = $f3->get("kanboard.project_id");
		$invoice_column_id = $f3->get("kanboard.invoice_column_id");
		
		// get column's tasks from API
		$data = KanboardTaskApiSvc::searchTasks("project:$project_id");
		
		// get columns (indexed by id)
		$columns = KanboardApiSvc::getColumns($project_id);
		$columns = array_combine(array_column($columns, "id"), $columns);
		$f3->set("columns", $columns);
		
		// filter data
		$data = array_filter($data,
			function ($row) use ($invoice_column_id) {
				if(empty($row["date_due"])) {
					return false;
				}
				if($row["column_id"] == $invoice_column_id) {
					return false;
				}
				return true;
			}
		);
		
		// sort data
		$keys = array_column($data, 'date_due');
		array_multisort($keys, SORT_ASC, $data);
		
		// reformat data for output
		$data2 = [];
		foreach ($data as $row) {
			$date = new \DateTime ();
			$date->setTimestamp ( $row ['date_due'] );
			$date_str = $date->format ('Y-m-d');
			if (!isset ($data2 [$date_str])) {
				$data2 [$date_str] = [];
			}
			$data2 [$date_str] [] = $row;
		}
		ksort($data2);
		$f3->set("data2", $data2);
		
		// translations arrays
		$months = [
			'en' => [
				'January',
				'February',
				'March',
				'April',
				'May',
				'June',
				'July',
				'August',
				'September',
				'October',
				'November',
				'December',
			],
			'fr' => [
				'Janvier',
				'Février',
				'Mars',
				'Avril',
				'Mai',
				'Juin',
				'Juillet',
				'Août',
				'Septembre',
				'Octobre',
				'Novembre',
				'Décembre',
			]
		];
		$f3->set("months", $months);
		
		$days_of_week = [
			'en' => [
				'Sunday',
				'Monday',
				'Tuesday',
				'Wednesday',
				'Thursday',
				'Friday',
				'Saturday',
			],
			'fr' => [
				'Dimanche',
				'Lundi',
				'Mardi',
				'Mercredi',
				'Jeudi',
				'Vendredi',
				'Samedi',
			]
		];
		$f3->set("days_of_week", $days_of_week);
		
		$view = new \View();
		echo $view->render('priority.phtml');
	}
	

	public static function cadratinEstimateCLI ($f3)
	{
		$filename = $f3->get("PARAMS.filename");
		echo PHP_EOL;
		echo " devis : $filename" . PHP_EOL;
		
		$sort_subdir = CadratinSvc::$cadratin_done_subdir;
		try {
			$data = CadratinSvc::handleEstimateFile ($filename);
			
			$task_id = KanboardSvc::addCadratinEstimate ($data);
			if(empty($task_id)) {
				throw(new ErrorException("cadratin error"));
			}
		}
		catch(Exception $ex) {
			echo $ex->getMessage() . PHP_EOL;
			$sort_subdir = CadratinSvc::$cadratin_error_subdir;
		}
		finally {
			rename(
				"data/" . CadratinSvc::$cadratin_estimate_subdir . "/$filename",
				"data/" . CadratinSvc::$cadratin_estimate_subdir . "/$sort_subdir/$filename"
			);
		}
	}
	
	
	public static function cadratinProductionCLI ($f3)
	{
		$filename = $f3->get("PARAMS.filename");
		echo PHP_EOL;
		echo " prod : $filename" . PHP_EOL;
		
		$sort_subdir = CadratinSvc::$cadratin_done_subdir;
		try {
			$data = CadratinSvc::handleProdFile ($filename);
			
			$task_id = KanboardSvc::addCadratinProduction ($data);
			if(empty($task_id)) {
				throw(new ErrorException("cadratin error"));
			}
		}
		catch(Exception $ex) {
			echo $ex->getMessage() . PHP_EOL;
			$sort_subdir = CadratinSvc::$cadratin_error_subdir;
		}
		finally {
			rename(
				"data/" . CadratinSvc::$cadratin_production_subdir . "/$filename",
				"data/" . CadratinSvc::$cadratin_production_subdir . "/$sort_subdir/$filename"
			);
		}
	}
	
	
	public static function kanboardEstimatesPurgeCLI ($f3)
	{
		try {
			$res = KanboardSvc::closeOldEstimates();
			if($res !== true) {
				throw(new ErrorException("unknown error while tying to close old estimates"));
			}
		}
		catch(Exception $ex) {
			echo $ex->getMessage() . PHP_EOL;
		}
		
		try {
			$res = KanboardSvc::deleteDuplicateEstimates();
			if($res !== true) {
				throw(new ErrorException("unknown error while tying to delete duplicate estimates"));
			}
		}
		catch(Exception $ex) {
			echo $ex->getMessage() . PHP_EOL;
		}
	}
	
	
	public static function testGET ($f3) {
		$version = KanboardApiSvc::getVersion();
		echo "VERSION = $version";
	}
	
}
