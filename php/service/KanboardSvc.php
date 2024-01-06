<?php
namespace service;

use Base;
use DateTime;
use DB\SQL;
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
		$estimate_number = $csv["Numéro nu"];
		echo "devis n° {$estimate_number}" . PHP_EOL;
		$tasks = KanboardTaskApiSvc::searchTasks("column:$estimate_column_id ref:" . $estimate_number);
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
			"title"			=> "* {$csv["Raison sociale"]} devis n° {$estimate_number}",
			"description"	=> $csv["Désignation"],
			"color_id"		=> $color,
			"column_id"		=> $estimate_column_id,
			"reference"		=> $estimate_number,
		];
		
		$date = \DateTime::createFromFormat("d/m/Y", $csv["Date pièce"]);
		if($date !== false) {
			$params["date_due"] = $date->format("Y-m-d") . " 00:00";
		}
		else {
			echo "'Date pièce' is empty" . PHP_EOL;
		}
		
		$estimate_task_id = KanboardTaskApiSvc::createTask($params);
		echo "estimate task id = {$estimate_task_id}" . PHP_EOL;
		
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
				"task_id" => $estimate_task_id,
				"user_id" => $user_id /*$user["id"]*/,
				"content" => $csv["Edition éléments produit N°1"],
			];
			$comment_id = KanboardTaskApiSvc::createComment($params);
			echo "comment id = {$comment_id}" . PHP_EOL;
		}
		
		return $estimate_task_id;
	}

	
	public static function addCadratinProduction (array $csv) : int
	{
		// prepare data
		$f3 = Base::instance();
		$project_id = $f3->get("kanboard.project_id");
		$estimate_column_id = $f3->get("kanboard.estimate_column_id");
		$production_column_id = $f3->get("kanboard.production_column_id");
		
		// find reference task
		$estimate_number = $csv["N/référence"];
		echo "devis n° {$estimate_number}" . PHP_EOL;
		$tasks = KanboardTaskApiSvc::searchTasks("column:$estimate_column_id ref:$estimate_number");
		if($tasks === false) {
			throw new ErrorException("ERROR while searching for reference task");
		}
		if(count($tasks) > 1) {
			throw new ErrorException("BUG : too many tasks reference = $estimate_number");
		}
		if(count($tasks) === 0) { // didn't find estimate task
			// create empty estimate task
			$params = [
				"project_id"	=> $project_id,
				"title"			=> "* {$csv["Raison sociale"]} devis n° {$estimate_number}",
				"color_id"		=> "grey",
				"column_id"		=> $estimate_column_id,
				"reference"		=> $estimate_number,
			];
			
			$date = \DateTime::createFromFormat("d/m/Y", $csv["Date de livraison prévision produit N°1"]);
			if($date !== false) {
				$params["date_due"] = $date->format("Y-m-d") . " 00:00";
			}
			
			$estimate_task_id = KanboardTaskApiSvc::createTask($params);
			echo "empty estimate task id = {$estimate_task_id}" . PHP_EOL;
		}
		else {
			$estimate_task_id = $tasks[0]["id"];
		}
		
		// close estimate task
		$res = KanboardTaskApiSvc::closeTask($estimate_task_id);
		
		// create prod task from estimate duplication
		$production_task_id = KanboardTaskApiSvc::duplicateTaskToColumn($estimate_task_id, $project_id, $production_column_id);
		echo "production task id = {$production_task_id}" . PHP_EOL;
		
		// update task data
		$params = [
			"id"	=> $production_task_id,
			"title"	=> "** {$csv["Raison sociale"]} devis n° {$estimate_number}",
		];
		if(!empty($csv["V/référence"])) {
			$params["title"] .= " cde n° {$csv["V/référence"]}";
		}
		if(!empty($csv["Date de livraison prévision produit N°1"])) {
			$date = \DateTime::createFromFormat("d/m/Y", $csv["Date de livraison prévision produit N°1"]);
			if($date !== false) {
				$params["date_due"] = $date->format("Y-m-d") . " 00:00";
			}
		}
		KanboardTaskApiSvc::updateTask($params);
		
		// handle special cases
		if(count($tasks) === 0) {
			// send email
			$subject = $f3->get("DICT.cant_find_estimate_task.subject", $estimate_number);
			$task_url_prefix = $f3->get("kanboard.url") . "/?controller=TaskViewController&action=show&task_id=";
			$message = $f3->get("DICT.cant_find_estimate_task.message", ["{$task_url_prefix}{$estimate_task_id}", "{$task_url_prefix}{$production_task_id}"]);
			KanboardSvc::send_email($subject, nl2br($message));
			throw new ErrorException($subject);
		}
		
		return $production_task_id;
	}
	
	
	public static function closeOldEstimates () : bool
	{
		// prepare data
		$f3 = Base::instance();
		$estimate_column_id = $f3->get("kanboard.estimate_column_id");
		$estimate_days_expire = $f3->get("kanboard.estimate_days_expire");
		
		// get all opened estimate tasks
		$tasks = KanboardTaskApiSvc::searchTasks("status:open column:$estimate_column_id");
		
		// close old estimates
		$result = true;
		$now = new DateTime();
		foreach($tasks as $task) {
			if(!empty($task["date_creation"])) {
				$d = new DateTime("@" . $task["date_creation"]);
				if($d->diff($now)->d > $estimate_days_expire) {
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
	
	
	public static function deleteDuplicateEstimates () : bool
	{
		// prepare data
		$f3 = Base::instance();
		$estimate_column_id = $f3->get("kanboard.estimate_column_id");
		
		// get all estimates
		$estimates = KanboardTaskApiSvc::searchTasks("column:{$estimate_column_id}");
		
		// group by reference
		$tasks_grouped = [];
		foreach ($estimates as $task) {
			$tasks_grouped [$task["reference"]] [] = $task;
		}
		
		// remove useless groups from our selection (no reference, only 1 task per reference)
		unset($tasks_grouped['']);
		foreach($tasks_grouped as $reference => $group) {
			if(count($group) < 2) {
				unset($tasks_grouped[$reference]);
			}
		}
		
		// close duplicate tasks so that it remains only one of each reference
		$result = true;
		foreach($tasks_grouped as $group) {
			$nb = count($group);
			for($i=0; $i<$nb-1; $i++) {
				$task = $group[$i];
				echo "removing duplicate task id = {$task["id"]}" . PHP_EOL;
				$res = KanboardTaskApiSvc::removeTask($task["id"]);
				if($res === false) {
					$result = false;
				}
			}
		}
		
		return $result;
	}
	
	
	
	public static function send_email ($subject, $message, $attachements=[]) {
		$f3 = Base::instance();
		
		// send email to admin
		$smtp = new \SMTP ( $f3->get("smtp.host"), $f3->get("smtp.port"), $f3->get("smtp.scheme"), $f3->get("smtp.login"), $f3->get("smtp.password") );
		$smtp->set('Content-type', 'text/html; charset=UTF-8');
		$smtp->set('Errors-to', $f3->get("smtp.sender"));
		$smtp->set('From', $f3->get("smtp.sender"));
		$smtp->set('To', $f3->get("smtp.admin_address"));
		$smtp->set('Subject', "cadratin-kanboard : {$subject}");
		
		foreach($attachements as $attachment) {
			$smtp->attach($attachment);
		}

		$content = <<<EOT
<html>
<head>
</head>
<body>
<p>
Bonjour ;
</p>
<p>
{$message}
</p>
<p>
</p>
-- <br/>
<br/>
impbot
</body>
</html>
EOT;
		
		$res = $smtp->send($content, true);
		if($res !== true) {
			echo '<pre>' . $smtp->log() . '</pre>';
			die;
		}
	}
}
