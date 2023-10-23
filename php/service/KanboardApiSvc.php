<?php
namespace service;

use Base;
use Datto\JsonRpc\Http\Client;
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


    public static function getVersion ()
    {
		$f3 = Base::instance();

		$kanboard_rpc_url = $f3->get("kanboard.url") . "/jsonrpc.php";
		$headers = self::getAuthHeaders();
		$client = new Client($kanboard_rpc_url, $headers);
		$client->query("getVersion", [], $result); /** @var int $result */

		try {
			$client->send();
		}
		catch (Exception $exception) {
			echo "EXCEPTION message : " . $exception->getMessage();
		}
		return $result;
    }

}
