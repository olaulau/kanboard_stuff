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
		$headers = ["Authorization" => "Basic $authentication"];
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


	public static function getVersion () : string
	{
		$client = self::getClient();
		$params = [];
		$client->query("getVersion", $params, $result);

		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "EXCEPTION message : " . $exception->getMessage();
		}

		return $result;
	}
	
	
	public static function getUserByName ($user_name) : array
	{
		$client = self::getClient();
		$params = [
			"username" => $user_name,
		];
		$client->query("getUserByName", $params, $result);

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
