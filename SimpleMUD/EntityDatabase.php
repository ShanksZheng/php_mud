<?php
namespace SimpleMUD
{

//实体数据库基类
abstract class EntityDatabase
{
	//这个map只是摆设，子类必须覆盖该map
	protected static $map = array();
	
	
	//基于id获取实体
	public static function get(int $id)
	{
		//要访问子类的同名静态属性，应使用staic而不是self
		if( !isset(static::$map[$id]) )
			return null;
		return static::$map[$id];
	}
	
	//基于名称部分匹配的实体
	public static function find(string $name)
	{
		foreach(static::$map as $v)
		{
			if( $v->Match($name) )
				return $v;
		}
		
		return null;
	}
	
	//基于名称完全匹配的实体
	public static function findfull(string $name)
	{
		foreach(static::$map as $v)
		{
			if( $v->MatchFull($name) )
				return $v;
		}
		
		return null;
	}
	
	//基于id查找实体是否存在
	public static function hasFromID(int $id)
	{
		return isset(static::$map[$id]);
	}
	
	//基于名称部分匹配的实体是否存在
	public static function has(string $name)
	{
		return (self::find($name) != null) ?true :false;
	}
	
	//基于名称完全匹配的实体是否存在
	public static function hasfull(string $name)
	{
		return (self::findfull($name) != null) ?true :false;
	}
	
	//
	public static function getall()
	{
		return static::$map;
	}
	
	//获得数组size
	public static function size()
	{
		return count(static::$map);
	}
	
	//获得未被使用的键
	public static function FindOpenID()
	{
		if( count(static::$map) == 0 )
			return 1;
		
		//end(static::$map);
		//if( count(static::$map) == key(static::$map)+1 )
		//	return 1;
			
		$openid = 1;
		foreach(static::$map as $k => $v)
		{
			if($openid == $k)
				$openid++;
			else
				break;
		}
		
		return $openid;
	}
	
	//遍历map对符合条件的item执行func
	public static function OperateOnIf($func, $qualify)
	{
		foreach(static::$map as $v)
		{
			if( $qualify($v) )
				$func($v);
		}
	}
	
	//遍历map对item执行func
	public static function OperateOn($func)
	{
		foreach(static::$map as $v)
		{
			$func($v);
		}
	}
};


}//end namespace
