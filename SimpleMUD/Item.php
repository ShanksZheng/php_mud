<?php
namespace SimpleMUD
{
require_once("Entity.php");
require_once("Attributes.php");
require_once(ROOT_PATH."/BasicLib/FileStream.php");

//物品类(武器，防具，消耗品都属于物品)
class Item extends Entity
{
	use tItem;
	
	protected $type = 0;				//物品类型
	protected $min = 0;					//最小值
	protected $max = 0;					//最大值
	protected $speed = 0;				//速度，只对武器有效
	protected $price = 0;				//价格
	protected $attributes = null;		//对玩家的属性影响
	
	function __construct()
	{
		$this->attributes = new \SplFixedArray(NUMATTRIBUTES);
	}
	
	function Type()
	{
		return $this->type;
	}
	
	function GetAttr(int $att)
	{
		return $this->attributes[$att];
	}
	
	function Min()
	{
		return $this->min;
	}
	
	function Max()
	{
		return $this->max;
	}
	
	function Speed()
	{
		return $this->speed;
	}
	
	function Price()
	{
		return $this->price;
	}
};


trait tItem
{
	function Load(\BasicLib\IStream $fs)
	{
		$temp = '';
		
		$fs->GetString($temp); $fs->GetInt($this->id);
		$fs->GetString($temp); $fs->GetLine($this->name);
		$fs->GetString($temp); $fs->GetString($temp);
		$this->type = GetItemType($temp);
		$fs->GetString($temp); $fs->GetInt($this->min);
		$fs->GetString($temp); $fs->GetInt($this->max);
		$fs->GetString($temp); $fs->GetInt($this->speed);
		$fs->GetString($temp); $fs->GetInt($this->price);
		
		foreach($this->attributes as $k => $v)
		{
			$fs->GetString($temp);$fs->GetInt($temp);
			$this->attributes[$k] = $temp;
		}
	}
};

}
