<?php
require 'vendor/autoload.php';

$f3 = \Base::instance();
$f3->set("f3", $f3);

$f3->config('conf/index.ini');

$db = new DB\SQL(
		'mysql:host='.$f3->get("db_out.host").';port='.$f3->get("db_out.port").';dbname='.$f3->get("db_out.schema"),
		$f3->get("db_out.user"), $f3->get("db_out.password")
		);
$f3->set("db", $db);

$f3->run();
