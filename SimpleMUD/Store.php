<?php
namespace SimpleMUD
{
require_once(ROOT_PATH."/BasicLib/function.php");
require_once(ROOT_PATH."/BasicLib/FileStream.php");
require_once("Entity.php");


//商店类
class Store extends Entity
{
	use tStore;
	
	protected $items = array();		//物品列表(只存放id)
	
	//获取名称完全或部分匹配的物品
	function Find(string $name)
	{
		foreach($this->items as $k => $v)
		{
			if( $this->items->matchFull($name) )
				return $v;
		}
		foreach($this->items as $k => $v)
		{
			if( $this->items->match($name) )
				return $v;
		}
		
		return null;
	}
	
	//
	function Items()
	{
		return $this->item;
	}
	
	//
	function Size()
	{
		return count($this->items);
	}
	
	//物品是否在列表中
	function Has(int $id)
	{
		foreach($this->items as $k => $v)
		{
			if( $v->ID() == $id )
				return true;
		}
		return false;
	}
};//end class Store


trait tStore
{
	function Load(\BasicLib\IStream $fs)
	{
		$temp = '';
		$fs->GetString($temp); $fs->GetInt($this->id);
		$fs->GetString($temp); $fs->GetLine($this->name);
		
		$fs->GetString($temp);
		while( $fs->GetInt2($temp) != 0 )
		{
			array_push($this->items, $temp);
		}
	}
};//end trait tStore


}//end namespace
