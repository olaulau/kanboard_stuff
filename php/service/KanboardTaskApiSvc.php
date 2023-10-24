<?php
namespace service;

use Base;
use Datto\JsonRpc\Http\Client;
use Datto\JsonRpc\Responses\ErrorResponse;
use ErrorException;
use Exception;

abstract class KanboardTaskApiSvc
{

	public static function getAllTasksFromProject (int $project_id) : array
	{
		// if(empty($params["task_id"])) {
		// 	throw new ErrorException(("missing required parameter"));
		// }
		$params = [
			"project_id" => $project_id,
		];
		
		$client = KanboardApiSvc::getClient();
		$client->query("getAllTasks", $params, $result);
		
		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "EXCEPTION message : " . $exception->getMessage();
		}
		if($result instanceof ErrorResponse) { /** @var ErrorResponse $result */
			echo " ERROR RESPONSE message = " . $result->getMessage() . "<br/>" . PHP_EOL;
			return 0;
		}
		
		return $result;
	}
	
	
	public static function getAllTasksFromColumn (int $column_id) : array
	{
		$f3 = Base::instance();
		$all_tasks = self::getAllTasksFromProject($f3->get("kanboard.project_id"));

		$res = [];
		foreach($all_tasks as $task) {
			if($task["column_id"] === $column_id) {
				$res [] = $task;
			}
		}
		return $res;
	}
	
	
	public static function removeTask (int $task_id) : bool
	{
		$params = [
			"task_id" => $task_id,
		];
		$client = KanboardApiSvc::getClient();
		$client->query("removeTask", $params, $result);

		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "EXCEPTION message : " . $exception->getMessage();
		}
		if($result instanceof ErrorResponse) { /** @var ErrorResponse $result */
			echo " ERROR RESPONSE message = " . $result->getMessage() . "<br/>" . PHP_EOL;
			return 0;
		}
		
		return $result;
	}


	public static function removeAllTasksFromColumn (int $column_id)
	{
		$f3 = Base::instance();
		$estimate_column_id = $f3->get("kanboard.estimate_column_id");

		$tasks = KanboardTaskApiSvc::getAllTasksFromColumn($estimate_column_id);
		foreach($tasks as $task) {
			KanboardTaskApiSvc::removeTask($task["id"]);
		}
	}


	public static function createTask (array $params)
	{
		if(empty($params["title"]) || empty($params["project_id"])) {
			throw new ErrorException(("missing required parameter"));
		}

		$client = KanboardApiSvc::getClient();
		$client->query("createTask", $params, $result);

		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "EXCEPTION message : " . $exception->getMessage();
		}
		if($result instanceof ErrorResponse) { /** @var ErrorResponse $result */
			echo " ERROR RESPONSE message = " . $result->getMessage() . "<br/>" . PHP_EOL;
			return 0;
		}
		
		return $result;
	}


	public static function createComment (array $params)
	{
		if(empty($params["task_id"]) || empty($params["user_id"]) || empty($params["content"])) {
			throw new ErrorException(("missing required parameter"));
		}

		$client = KanboardApiSvc::getClient();
		$client->query("createComment", $params, $result);

		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "EXCEPTION message : " . $exception->getMessage();
		}
		if($result instanceof ErrorResponse) { /** @var ErrorResponse $result */
			echo " ERROR RESPONSE message = " . $result->getMessage() . "<br/>" . PHP_EOL;
			return 0;
		}

		return $result;
	}

}
