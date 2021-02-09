<?php
namespace controller;

class IndexCtrl
{

	public static function beforeRoute ()
	{
		
	}
    
	
	public static function afterRoute ()
	{
		
	}

	
	public static function indexGET ()
	{
		$f3 = \Base::instance();
		$db_in = $f3->get("db_in"); /* @var $db_in \DB\SQL\Mapper */
		$db_out = $f3->get("db_out");
		
		$in_lane_wrapper = new \DB\SQL\Mapper($db_in, "lane");
		$in_lanes = $in_lane_wrapper->find(['board_id = ?', 2], ["order" => "position ASC"]);
		foreach ($in_lanes as $in_lane) {
			echo "$in_lane->name <br/>";
		}
		die;
		
		
		
		$view = new \View();
		echo $view->render('index.phtml');
	}
	
	
}
