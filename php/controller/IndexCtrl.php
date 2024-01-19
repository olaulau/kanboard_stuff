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
	
	
	public static function migrateGET ($f3)
	{
		$db_in = new \DB\SQL('sqlite:'.$f3->get("db_in.file"));
		$db_out = $f3->get("db"); /* @var $db_out \DB\SQL\Mapper */
		
		
		// check db's connected
		if(!$db_in->exists("lane")) {
			die("input database error");
		}
		if(!$db_out->exists("columns")) {
			die("output database error");
		}
		
		
		// remove output columns
		$out_columns_wrapper = new \DB\SQL\Mapper($db_out, "columns");
		$out_columns = $out_columns_wrapper->find(['project_id = ?', $f3->get("kanboard.project_id")], ["order" => "position DESC"]);
		foreach ($out_columns as $out_column) {
			$out_column->erase();
		}
		
		// copy columns
		$in_lane_wrapper = new \DB\SQL\Mapper($db_in, "lane");
		$in_lanes = $in_lane_wrapper->find(['board_id = ?', $f3->get("taskboard.board_id")], ["order" => "position ASC"]);
		$lane_to_column = [];
		foreach ($in_lanes as $i => $in_lane) {
			$column = new \DB\SQL\Mapper($db_out, "columns");
			$column->title = $in_lane->name;
			$column->position = $i+1;
			$column->project_id = $f3->get("kanboard.project_id");
			$column->save();
			$lane_to_column[$in_lane->id] = $column->id;
		}
		
		
		// copy tickets
		$in_item_wrapper = new \DB\SQL\Mapper($db_in, "item");
		$now = time();
		$colors = [
			"#c3f4b5" => "green",
			"#ffbaba" => "red",
			"#ffffe0" => "yellow",
			"#bee7f4" => "blue",
		];
		
		$out_swimlane_wrapper = new \DB\SQL\Mapper($db_out, "swimlanes");
		$default_swimlane = $out_swimlane_wrapper->findone(["project_id = ? AND position = ?", $f3->get("kanboard.project_id"), 1], []);
		
		foreach ($lane_to_column as  $lane_id => $column_id) {
			$in_items = $in_item_wrapper->find(['lane_id = ?', $lane_id], ["order" => "position ASC"]);
			foreach ($in_items as $i => $in_item) {
				$task = new \DB\SQL\Mapper($db_out, "tasks");
				$task->title = $in_item->title;
				$task->description = $in_item->description;
				$task->date_creation = $now;

				$task->date_due = null;
				$d = \DateTime::createFromFormat("m/d/Y", $in_item->due_date);
				if($d !== false) {
					$task->date_due = $d->getTimestamp();
				}
				
				$task->color_id = null;
				if( $in_item->color && !empty($colors[ $in_item->color ]) ) {
					$color = $colors[ $in_item->color ];
					$task->color_id = $color;
				}
				
				$task->project_id = $f3->get("kanboard.project_id");
				$task->column_id = $column_id;
				$task->position = $i+1;
				$task->date_modification = $now;
				$task->swimlane_id = $default_swimlane->id;
				$task->date_moved = $now;
				$task->save();
			}
		}
		
		die;
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
		die;
	}
	
}
