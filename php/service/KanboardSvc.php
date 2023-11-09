<?php
namespace service;

use Base;
use ErrorException;

abstract class KanboardSvc
{

	public static function addCadratinEstimate (array $data) : int
	{
		// prepare data
		$f3 = Base::instance();
		$project_id = $f3->get("kanboard.project_id");
		$estimate_column_id = $f3->get("kanboard.estimate_column_id");
		
		// remove old tasks (deleted estimates) to avoid duplicate reference
		$tasks = KanboardTaskApiSvc::searchTasks("status:open column:$estimate_column_id ref:" . $data["Numéro nu"]);
		foreach($tasks as $task) {
			echo "removing old task id = {$task["id"]} reference = {$task["Numéro nu"]}" . PHP_EOL;
			KanboardTaskApiSvc::removeTask($task["id"]);
		}
		
		// calculate color
		$cadratin_code_compta_color = $f3->get("cadratin.code_compta_color");
		foreach($cadratin_code_compta_color as $color => $codes_compta) {
			if(is_int($codes_compta)) {
				$codes_compta = [$codes_compta];
			}
			foreach($codes_compta as $code_compta) {
				if(strpos($data["Code compta"], strval($code_compta)) === 0) {
					break 2;
				}
			}
		}
		
		// create task
		$params = [
			"project_id"	=> $project_id,
			"title"			=> $data["Raison sociale"],
			"description"	=> $data["Désignation"],
			"color_id"		=> $color,
			"column_id"		=> $estimate_column_id,
			"reference"		=> $data["Numéro nu"],
		];
		
		$date = \DateTime::createFromFormat("d/m/Y", $data["Date pièce"]);
		if($date !== false) {
			$params["date_due"] = $date->format("Y-m-d");
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
		if(!empty($data["Edition éléments produit N°1"])) {
			$params = [
				"task_id" => $task_id,
				"user_id" => $user_id /*$user["id"]*/,
				"content" => $data["Edition éléments produit N°1"],
			];
			$comment_id = KanboardTaskApiSvc::createComment($params);
			echo "comment id = $comment_id" . PHP_EOL;
		}
		
		return $task_id;
	}

	
	public static function addCadratinProduction (array $data) : int
	{
		// prepare data
		$f3 = Base::instance();
		$estimate_column_id = $f3->get("kanboard.estimate_column_id");
		$production_column_id = $f3->get("kanboard.production_column_id");
		
		// find reference task
		$reference = $data["N/référence"];
		
		$tasks = KanboardTaskApiSvc::searchTasks("status:open column:$estimate_column_id ref:$reference");
		if($tasks === false) {
			throw new ErrorException("ERROR while searching for reference task");
		}
		if(count($tasks) === 0) {
			throw new ErrorException("can't find task reference = $reference");
		}
		if(count($tasks) > 1) {
			throw new ErrorException("too many tasks reference = $reference");
		}
		
		$task = $tasks[0];
		echo "task id = {$task["id"]}" . PHP_EOL;
		
		// calculate new position
		$prod_tasks = KanboardTaskApiSvc::searchTasks("status:open column:$production_column_id");
		$max_production_position = 0;
		foreach($prod_tasks as $prod_task) {
			if($prod_task["position"] > $max_production_position) {
				$max_production_position = $prod_task["position"];
			}
		}
		
		// move task
		$res = KanboardTaskApiSvc::moveTaskPosition($task["id"], $production_column_id, $max_production_position+1);
		return $task["id"];
	}
	
}
