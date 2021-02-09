<?php
require 'vendor/autoload.php';

$f3 = \Base::instance();

$f3->config('conf/index.ini');

$db_in = new DB\SQL('sqlite:'.$f3->get("db_in.host"));
$f3->set("db_in", $db_in);

$db_out = new DB\SQL(
		'mysql:host='.$f3->get("db_out.host").';port='.$f3->get("db_out.port").';dbname='.$f3->get("db_out.schema"),
		$f3->get("db_out.user"), $f3->get("db_out.password")
		);
$f3->set("db_out", $db_out);

$f3->run();
