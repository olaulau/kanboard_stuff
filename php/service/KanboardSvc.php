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

		// calculate color
		$cadratin_code_compta_color = $f3->get("cadratin.code_compta_color");
		foreach($cadratin_code_compta_color as $color => $codes_compta) {
			if(is_int($codes_compta)) {
				$codes_compta = [$codes_compta];
			}
			foreach($codes_compta as $code_compta) {
				if(strpos($data["Code compta"], $code_compta) === 0) {
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
		echo "task id = $task_id <br/>" . PHP_EOL;
		
		// query current user infos
		$user_name = $f3->get("kanboard.rpc.username");
		$user = KanboardApiSvc::getUserByName($user_name);
		
		// create comment
		$params = [
			"task_id" => $task_id,
			"user_id" => $user["id"],
			"content" => $data["Edition éléments produit N°1"],
		];
		$comment_id = KanboardTaskApiSvc::createComment($params);
		echo "comment id = $comment_id <br/>" . PHP_EOL;

		return $task_id;
	}


	public static function addCadratinProduction (array $data) : int
	{
		// prepare data
		$f3 = Base::instance();
		$production_column_id = $f3->get("kanboard.production_column_id");

		// find reference task
		$reference = $data["N/référence"];
		$task = KanboardTaskApiSvc::getTaskByReference($reference);
		if(empty($task)) {
			throw new ErrorException("can't find task reference = $reference");
		}
		echo "task id = {$task["id"]} <br/>" . PHP_EOL;
		
		// calculate new position
		$prod_tasks = KanboardTaskApiSvc::searchTasks("column:$production_column_id");
		$max_production_position = 0;
		foreach($prod_tasks as $prod_task) {
			if($prod_task["position"] > $max_production_position) {
				$max_production_position = $prod_task["position"];
			}
		}
		
		// move task
		KanboardTaskApiSvc::moveTaskPosition($task["id"], $production_column_id, $max_production_position+1);
		
		/*
		// query current user infos
		$user_name = $f3->get("kanboard.rpc.username");
		$user = KanboardApiSvc::getUserByName($user_name);
		
		// create comment
		$params = [
			"task_id" => $task["id"],
			"user_id" => $user["id"],
			"content" => json_encode($data, JSON_UNESCAPED_UNICODE),
		];
		$comment_id = KanboardTaskApiSvc::createComment($params);
		echo "comment id = $comment_id <br/>" . PHP_EOL;
		*/
		
		return $task["id"];
	}
	
}
