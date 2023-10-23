<?php
namespace service;

use Base;
use Datto\JsonRpc\Http\Client;
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


	protected static function getClient () : Client
	{
		$kanboard_rpc_url = self::getUrl();
		$headers = self::getAuthHeaders();
		$client = new Client($kanboard_rpc_url, $headers);
		return $client;
	}


    public static function getVersion () : string
    {
		$client = self::getClient();
		$client->query("getVersion", [], $result);

		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "EXCEPTION message : " . $exception->getMessage();
		}
		return $result;
    }


	public static function createTask (array $params)
	{
		if(empty($params["title"]) || empty($params["project_id"])) {
			throw new ErrorException(("missing required parameter"));
		}

		$client = self::getClient();
		$client->query("createTask", $params, $result);

		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "EXCEPTION message : " . $exception->getMessage();
		}
		return $result;
	}

}
