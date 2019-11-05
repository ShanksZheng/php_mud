<?php
namespace SimpleMUD
{
require_once(ROOT_PATH."/BasicLib/FileStream.php");
require_once("EntityDatabase.php");
require_once("Room.php");

//房间数据库
class RoomDatabase extends EntityDatabase
{
	protected static $map = array();
	
	public static function LoadTemplate()
	{
		$fs = new \BasicLib\IStream(ROOT_PATH."/maps/default.map");
		$temp = '';
		$room = null;
		
		while( $fs->Good() )
		{
			$room = new Room();
			$room->LoadTemplate($fs);
			self::$map[$room->ID()] = $room;
		}
	}
	
	//注意，在调用该方法前需要调用LoadTemplate
	public static function LoadData()
	{
		$fs = new \BasicLib\IStream(ROOT_PATH."/maps/default.data");
		$temp = '';
		$roomid = 0;
		while( $fs->Good() )
		{
			//载入房间id
			$fs->GetString($temp);
			$fs->GetInt($roomid);
			//注意，如果该$map[$roomid]不存在会报错
			self::$map[$roomid]->LoadData($fs);
		}
	}
	
	public static function SaveData()
	{
		$file = new \BasicLib\File(ROOT_PATH."/maps/default.data", "w");
		foreach(self::$map as $v)
		{
			$str = $v->DataToString($v);
			$file->Write("[ROOMID] {$v->ID()}\n");
			$file->Write($str."\n");
		}
	}
};//end class RoomDatabase 

//RoomDatabase::LoadTemplate();
}//end namespace
