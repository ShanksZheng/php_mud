<?php
namespace SimpleMUD
{
require_once("Item.php");
require_once("EntityDatabase.php");
require_once(ROOT_PATH."/BasicLib/FileStream.php");

//物品数据库类
class ItemDatabase extends EntityDatabase
{
	const FILENAME = ROOT_PATH."/items/items.itm";
	
	protected static $map = array();
	
	public static function Load()
	{
		//管理员可以编辑数据库文件后重新加载，所以原来的数据需要删除
		self::$map = array();
		$fs = new \BasicLib\IStream(self::FILENAME);
		while( $fs->Good() )
		{
			$item = new Item();
			$item->Load($fs);
			self::$map[$item->ID()] = $item;
		}
	}
};//end class

//ItemDatabase::Load();
}//end namespace
