<?php
namespace service;

use Base;
use DateTime;
use ErrorException;

abstract class KanboardSvc
{

	public static function addCadratinEstimate (array $csv) : int
	{
		// prepare data
		$f3 = Base::instance();
		$project_id = $f3->get("kanboard.project_id");
		$estimate_column_id = $f3->get("kanboard.estimate_column_id");
		
		// remove deleted estimates to avoid duplicate reference
		$tasks = KanboardTaskApiSvc::searchTasks("column:$estimate_column_id ref:" . $csv["Numéro nu"]);
		foreach($tasks as $task) {
			echo "removing deleted estimate task id = {$task["id"]} reference = {$task["reference"]}" . PHP_EOL;
			KanboardTaskApiSvc::removeTask($task["id"]);
		}
		
		// calculate color
		$cadratin_code_compta_color = $f3->get("cadratin.code_compta_color");
		foreach($cadratin_code_compta_color as $color => $codes_compta) {
			if(is_int($codes_compta)) {
				$codes_compta = [$codes_compta];
			}
			foreach($codes_compta as $code_compta) {
				if(strpos($csv["Code compta"], strval($code_compta)) === 0) {
					break 2;
				}
			}
		}
		
		// create task
		$params = [
			"project_id"	=> $project_id,
			"title"			=> "* " . $csv["Raison sociale"] . " " . "devis n° " . $csv["Numéro nu"],
			"description"	=> $csv["Désignation"],
			"color_id"		=> $color,
			"column_id"		=> $estimate_column_id,
			"reference"		=> $csv["Numéro nu"],
		];
		
		$date = \DateTime::createFromFormat("d/m/Y", $csv["Date pièce"]);
		if($date !== false) {
			$params["date_due"] = $date->format("Y-m-d") . " 00:00";
		}
		
		$task_id = KanboardTaskApiSvc::createTask($params);
		echo "task id = $task_id" . PHP_EOL;
		
		// query current user infos
		//@see https://docs.kanboard.org/v1/api/me_procedures/#getme
		$user_id = $f3->get("kanboard.rpc.user_id");
		/*
		$user_name = $f3->get("kanboard.rpc.username");
		user = KanboardApiSvc::getUserByName($user_name);
		*/
		
		// create comment
		if(!empty($csv["Edition éléments produit N°1"])) {
			$params = [
				"task_id" => $task_id,
				"user_id" => $user_id /*$user["id"]*/,
				"content" => $csv["Edition éléments produit N°1"],
			];
			$comment_id = KanboardTaskApiSvc::createComment($params);
			echo "comment id = $comment_id" . PHP_EOL;
		}
		
		return $task_id;
	}

	
	public static function addCadratinProduction (array $csv) : int
	{
		// prepare data
		$f3 = Base::instance();
		$project_id = $f3->get("kanboard.project_id");
		$estimate_column_id = $f3->get("kanboard.estimate_column_id");
		$production_column_id = $f3->get("kanboard.production_column_id");
		
		// find reference task
		$reference = $csv["N/référence"];
		$tasks = KanboardTaskApiSvc::searchTasks("column:$estimate_column_id ref:$reference");
		if($tasks === false) {
			throw new ErrorException("ERROR while searching for reference task");
		}
		if(count($tasks) === 0) {
			throw new ErrorException("can't find task reference = $reference");
		}
		if(count($tasks) > 1) {
			throw new ErrorException("too many tasks reference = $reference");
		}
		$estimate_task = $tasks[0];
		
		// close estimate task
		$res = KanboardTaskApiSvc::closeTask($estimate_task["id"]);
		
		// duplicate estimate task
		$production_task_id = KanboardTaskApiSvc::duplicateTaskToColumn($estimate_task["id"], $project_id, $production_column_id);
		echo "production task id = {$production_task_id}" . PHP_EOL;
		
		// get production task data
		$production_task = KanboardTaskApiSvc::getTaskById($production_task_id);
		
		// update task data
		$params["id"] = $production_task["id"];
		$params["title"] = "** {$csv["Raison sociale"]} devis n° {$reference}";
		if(!empty($csv["V/référence"])) {
			$params["title"] .= " cde n° {$csv["V/référence"]}";
		}
		if(!empty($production_task["Date de livraison prévision produit N°1"])) {
			$date = \DateTime::createFromFormat("d/m/Y", $csv["Date de livraison prévision produit N°1"]);
			if($date !== false) {
				$params["date_due"] = $date->format("Y-m-d") . " 00:00";
			}
		}
		KanboardTaskApiSvc::updateTask($params);
		
		return $estimate_task["id"];
	}
	
	
	public static function purgeEstimates () : bool
	{
		// prepare data
		$f3 = Base::instance();
		$estimate_column_id = $f3->get("kanboard.estimate_column_id");
		$estimate_months_expire = $f3->get("kanboard.estimate_months_expire");
		
		// get all opened estimate tasks
		$tasks = KanboardTaskApiSvc::searchTasks("status:open column:$estimate_column_id");
		
		// close old estimates
		$result = true;
		$now = new DateTime();
		foreach($tasks as $task) {
			if(!empty($task["date_due"])) {
				$d = new DateTime("@" . $task["date_due"]);
				if($d->diff($now)->m >= $estimate_months_expire) {
					echo "closing old task id = {$task["id"]}" . PHP_EOL;
					$res = KanboardTaskApiSvc::closeTask($task["id"]);
					if($res === false) {
						$result = false;
					}
				}
			}
		}
		
		return $result;
	}
}
