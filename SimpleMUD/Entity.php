<?php

namespace SimpleMUD
{


//游戏实体类
abstract class Entity
{
	protected $name = '';	
	protected $id = 0;

	
	//获取name
	public function Name(){ return $this->name; }
	//设置name
	public function SetName($arg){ $this->name = $arg; }
	//获取id
	public function ID(){ return $this->id; }
	//设置id
	public function SetID($arg){ $this->id = $arg; }	
	
	//获取转换为小写的name
	public function CompName()
	{
		return mb_strtolower($this->name);
	}
	
	//将str与name进行完全匹配
	public function MatchFull(string $str)
	{
		return $this->CompName() == mb_strtolower($str);
	}
	
	//将str与name进行部分匹配
	public function Match(string $str)
	{
		$pos = mb_strpos($this->name, $str);
		if($pos === false)
			return false;
		else
			return true;
	}
};// end class


//用于Entity完全匹配的仿函数
class MatchEntityFull
{
	private $str= '';
	
	function __construct(string $str)
	{
		$this->str = $str;
	}
	
	function __invoke(Entity $entity)
	{
		return $entity->MatchFull($this->str);
	}
}

//用于Entity部分匹配的仿函数
class MatchEntity
{
	private $str= '';
	
	function __construct(string $str)
	{
		$this->str = $str;
	}
	
	function __invoke(Entity $entity)
	{
		return $entity->Match($this->str);
	}
}


}// end namespace
