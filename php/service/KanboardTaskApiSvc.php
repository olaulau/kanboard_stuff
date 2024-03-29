<?php
namespace service;

use Base;
use Datto\JsonRpc\Responses\ErrorResponse;
use ErrorException;
use Exception;

abstract class KanboardTaskApiSvc
{

	private static function getAllTasksFromProject (int $project_id) : array
	{
		$params = [
			"project_id" => $project_id,
		];
		
		$client = KanboardApiSvc::getClient();
		$client->query("getAllTasks", $params, $result);
		
		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "getAllTasksFromProject EXCEPTION : " . $exception->getMessage() . PHP_EOL;
		}
		if($result instanceof ErrorResponse) { /** @var ErrorResponse $result */
			echo "getAllTasksFromProject ERROR RESPONSE : " . $result->getMessage() . "<br/>" . PHP_EOL;
			return 0;
		}
		
		return $result;
	}
	
	
	public static function getTaskById (int $task_id)
	{
		$required_params = [
			"task_id",
		];
		$params = [
			"task_id" => $task_id,
		];
		$result = KanboardApiSvc::clientSendQuery("getTask", $required_params, $params);
		return $result;
	}
	
	
	public static function getTaskByReference (int $reference)
	{
		$f3 = Base::instance();
		$params = [
			"project_id"	=> $f3->get("kanboard.project_id"),
			"reference"		=> $reference,
		];
		
		$client = KanboardApiSvc::getClient();
		$client->query("getTaskByReference", $params, $result);
		
		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "getTaskByReference EXCEPTION : " . $exception->getMessage() . PHP_EOL;
			die;
		}
		if($result instanceof ErrorResponse) { /** @var ErrorResponse $result */
			echo "getTaskByReference ERROR RESPONSE : " . $result->getMessage() . "<br/>" . PHP_EOL;
			die;
		}
		
		return $result;
	}
	
	
	public static function searchTasks (string $query) : array
	{
		$f3 = Base::instance();
		$params = [
			"project_id"	=> $f3->get("kanboard.project_id"),
			"query"			=> $query,
		];
		
		$client = KanboardApiSvc::getClient();
		$client->query("searchTasks", $params, $result);
		
		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "searchTasks EXCEPTION : " . $exception->getMessage() . PHP_EOL;
		}
		if($result instanceof ErrorResponse) { /** @var ErrorResponse $result */
			echo "searchTasks ERROR RESPONSE : " . $result->getMessage() . "<br/>" . PHP_EOL;
			return 0;
		}
		
		return $result;
	}
	
	
	private static function getAllTasksFromColumn (int $column_id) : array
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
		$required_params = [
			"task_id",
		];
		$params = [
			"task_id" => $task_id,
		];
		$result = KanboardApiSvc::clientSendQuery("removeTask", $required_params, $params);
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
			throw new ErrorException("createTask : missing required parameter");
		}

		$client = KanboardApiSvc::getClient();
		$client->query("createTask", $params, $result);

		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "createTask EXCEPTION : " . $exception->getMessage() . PHP_EOL;
		}
		if($result instanceof ErrorResponse) { /** @var ErrorResponse $result */
			echo "createTask ERROR RESPONSE : " . $result->getMessage() . PHP_EOL;
			return 0;
		}
		if($result === false) {
			echo "unknown ERROR creating the task" . PHP_EOL;
		}
		
		return $result;
	}


	public static function createComment (array $params)
	{
		if(empty($params["task_id"]) || empty($params["user_id"]) || empty($params["content"])) {
			throw new ErrorException("createComment : missing required parameter");
		}

		$client = KanboardApiSvc::getClient();
		$client->query("createComment", $params, $result);

		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "createComment EXCEPTION : " . $exception->getMessage() . PHP_EOL;
		}
		if($result instanceof ErrorResponse) { /** @var ErrorResponse $result */
			echo "createComment ERROR RESPONSE : " . $result->getMessage() . "<br/>" . PHP_EOL;
			return 0;
		}

		return $result;
	}
	
	
	public static function moveTaskPosition (int $task_id, int $dest_column_id, int $position)
	{
		$f3 = Base::instance();
		$client = KanboardApiSvc::getClient();
		$params = [
			"project_id"	=> $f3->get("kanboard.project_id"),
			"task_id"		=> $task_id,
			"column_id"		=> $dest_column_id,
			"position"		=> $position,
			"swimlane_id"	=> null,
		];
		$client->query("moveTaskPosition", $params, $result);

		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "moveTaskPosition EXCEPTION : " . $exception->getMessage() . PHP_EOL;
		}
		if($result instanceof ErrorResponse) { /** @var ErrorResponse $result */
			echo "moveTaskPosition ERROR RESPONSE : " . $result->getMessage() . "<br/>" . PHP_EOL;
			return 0;
		}

		return $result;
	}
	
	
	public static function closeTask (int $task_id)
	{
		$params = [
			"task_id"		=> $task_id,
		];
		
		$result = KanboardApiSvc::clientSendQuery("closeTask", ["task_id"], $params);
		return $result;
	}


	public static function openTask (int $task_id)
	{
		$params = [
			"task_id"		=> $task_id,
		];
		
		$result = KanboardApiSvc::clientSendQuery("openTask", ["task_id"], $params);
		return $result;
	}
	
	
	public static function updateTask (array $params)
	{
		$required_params = [
			"id",
		];
		$result = KanboardApiSvc::clientSendQuery("updateTask", $required_params, $params);
		return $result;
	}


	public static function duplicateTaskToColumn ($task_id, $project_id, $column_id)
	{
		$required_params = [
			"task_id",
			"project_id",
		];
		$params = [
			"task_id" => 		$task_id,
			"project_id" => 	$project_id,
			"column_id" => 		$column_id,
		];
		$result = KanboardApiSvc::clientSendQuery("duplicateTaskToProject", $required_params, $params);
		return $result;
	}

}
