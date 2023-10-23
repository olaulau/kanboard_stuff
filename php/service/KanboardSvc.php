<?php
namespace service;

use Base;

abstract class KanboardSvc
{

    public static function addCadratinEstimate (array $data) : int
    {
		$f3 = Base::instance();
		$db_out = $f3->get("db_out"); /* @var $db_out \DB\SQL\Mapper */
		
        // prepare data
        $project_id = $f3->get("kanboard.project_id");
		$estimate_column_id = $f3->get("kanboard.estimate_column_id");
		$now = time();

        // create task
		$params = [];
		$params["project_id"] = $project_id;
		$params["title"] = $data["Désignation"];
		$params["description"] = $data["Edition éléments produit N°1"];
		
		$d = \DateTime::createFromFormat("d/m/Y", $data["Date pièce"]);
		if($d !== false) {
			$params["date_due"] = $d->format("Y-m-d");
		}
		
		$params["color_id"] = "yellow";
		$params["column_id"] = $estimate_column_id;
		$params["reference"] = $data["Numéro nu"];
		
		$task_id = KanboardApiSvc::createTask($params);

		// create comment
		/*
		$comment = new \DB\SQL\Mapper($db_out, "comments");
		$comment->task_id = $task->id;
		$comment->user_id = null;
		$comment->date_creation = $now;
		$comment->comment = json_encode($data, JSON_UNESCAPED_UNICODE);
		$comment->reference = null;
		$comment->date_modification = $now;
		$comment->save();
		*/

		return $task_id;
    }


	public static function addCadratinProduction (array $data)
    {
		
		$f3 = Base::instance();
		$db_out = $f3->get("db_out"); /* @var $db_out \DB\SQL\Mapper */

		// config ?
		$estimate_column_id = 1;
		$production_column_id = 2;

        // prepare data
        $project_id = $f3->get("kanboard.project_id");
		$now = time();
        $swimlane_wrapper = new \DB\SQL\Mapper($db_out, "swimlanes");
		$default_swimlane = $swimlane_wrapper->findone(["project_id = ? AND position = ?", $project_id, 1], []);

		// calculate position
		$last_position = 0;
		$task_wrapper = new \DB\SQL\Mapper($db_out, "tasks");
		$last_task = $task_wrapper->findone(["project_id = ? AND column_id = ?", $project_id, $production_column_id], ["order" => "position DESC"]);
		if(!empty($last_task)) {
			$last_position = $last_task->position;
		}
		
		// get estimate task
		$reference = $data["N/référence"];
		$task = $task_wrapper->findone(["project_id = ? AND column_id = ? AND reference = ?", $project_id, $estimate_column_id, $reference], []);
		if(empty($task)) {
			die("estimate task not found (reference = $reference)");
		}

        // move task
		$task->column_id = $production_column_id;
		$task->position = $last_position + 1;
		$task->date_modification = $now;
		$task->date_moved = $now;
		$task->save();

		// create comment
		$comment = new \DB\SQL\Mapper($db_out, "comments");
		$comment->task_id = $task->id;
		$comment->user_id = null;
		$comment->date_creation = $now;
		$comment->comment = json_encode($data, JSON_UNESCAPED_UNICODE);
		$comment->reference = null;
		$comment->date_modification = $now;
		$comment->save();
    }
	
}
