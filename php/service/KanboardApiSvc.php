<?php
namespace service;

use Base;
use Datto\JsonRpc\Http\Client;
use Datto\JsonRpc\Responses\ErrorResponse;
use ErrorException;
use Exception;

abstract class KanboardApiSvc
{

	private static function getAuthHeaders () : array
	{
		$f3 = Base::instance();
		$authentication = base64_encode($f3->get("kanboard.rpc.username").":".$f3->get("kanboard.rpc.token"));
		$headers = ["X-API-Auth" => "$authentication"];
		return $headers;
	}


	private static function getUrl () : string
	{
		$f3 = Base::instance();
		$kanboard_rpc_url = $f3->get("kanboard.url") . "/jsonrpc.php";
		return $kanboard_rpc_url;
	}


	public static function getClient () : Client
	{
		$kanboard_rpc_url = self::getUrl();
		$headers = self::getAuthHeaders();
		$client = new Client($kanboard_rpc_url, $headers);
		return $client;
	}
	
	
	public static function clientSendQuery ($method, $required_params, $params)
	{
		// check required params
		foreach($required_params as $param_name) {
			if(empty($params[$param_name])) {
				throw new ErrorException("missing required parameter : clientSendQuery ( method = method , param = $param_name");
			}
		}

		$client = self::getClient();
		$client->query($method, $params, $result);

		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "$method clientSendQuery EXCEPTION : " . $exception->getMessage() . PHP_EOL;
			return 0;
		}
		if($result instanceof ErrorResponse) { /** @var ErrorResponse $result */
			echo "$method ERROR RESPONSE : " . $result->getMessage() . PHP_EOL;
			return 0;
		}
		
		return $result;
	}
	
	
	public static function getVersion () : string
	{
		$client = self::getClient();
		$params = [];
		$client->query("getVersion", $params, $result);

		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "getVersion EXCEPTION : " . $exception->getMessage() . PHP_EOL;
			die;
		}

		return $result;
	}
	
	
	public static function getUserByName ($user_name)
	{
		$required_params = [
			"username",
		];
		$params = [
			"username" => $user_name,
		];

		$result = self::clientSendQuery("getUserByName", $required_params, $params);
		return $result;
	}


	public static function getMe ($user_name)
	{
		$required_params = [];
		$params = [];

		$result = self::clientSendQuery("getMe", $required_params, $params);
		return $result;
	}
	
	
	public static function getColumns ($project_id)
	{
		$required_params = [
			"project_id"
		];
		$params = [
			"project_id" => $project_id,
		];

		$result = self::clientSendQuery("getColumns", $required_params, $params);
		return $result;
	}

}
