<?php
namespace SimpleMUD
{
require_once(ROOT_PATH."/BasicLib/FileStream.php");	
require_once("EntityDatabase.php");
require_once("Store.php");

//商店数据库
class StoreDatabase extends EntityDatabase
{
	const FILENAME = ROOT_PATH."/stores/stores.str";
	
	public static $map = array();
	
	//加载数据
	public static function Load()
	{
		$store = null;
		$fs = new \BasicLib\IStream(self::FILENAME);
		while( $fs->Good() )
		{
			$store = new Store();
			$store->Load($fs);
			self::$map[$store->ID()] = $store;
		}
	}
};//end class StoreDatabase


//StoreDatabase::Load();
//var_dump(StoreDatabase::$map);
}//end namespace
