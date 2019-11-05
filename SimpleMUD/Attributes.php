<?php
namespace SimpleMUD
{
	

//通用函数----------------------------------------------------------------
function StrToConst(string $str, array $strs)
{
	$str = mb_strtoupper($str);
	for($i=0; $i<count($strs); $i++)
	{
		if($str == $strs[$i])
			return $i;
	}
	return 0;
}


function ConstToStr(int $const, array $strs)
{
	return $strs[$const];
}
//通用函数----------------------------------------------------------------
	

//玩家属性----------------------------------------------------------------
//属性名个数
define('NUMATTRIBUTES', 9);

//属性常量
define('STRENGTH', 0);
define('HEALTH', 1);
define('AGILITY', 2);
define('MAXHITPOINTS', 3);
define('ACCURACY', 4);
define('DODGING', 5);
define('STRIKEDAMAGE', 6);
define('DAMAGEABSORB', 7);
define('HPREGEN', 8);

//属性常量对应字符串
$ATTRIBUTESTRINGS = array(
	'STRENGTH',
	'HEALTH',
	'AGILITY',
	'MAXHITPOINTS',
	'ACCURACY',
	'DODGING',
	'STRIKEDAMAGE',
	'DAMAGEABSORB',
	'HPREGEN'
);

//根据属性名称获取对应常量
function GetAttribute(string $str)
{
	global $ATTRIBUTESTRINGS;
	return StrToConst($str, $ATTRIBUTESTRINGS);
}

//根据属性名称常量获取对应字符串
function GetAttributeString(int $const)
{
	global $ATTRIBUTESTRINGS;
	return ConstToStr($const, $ATTRIBUTESTRINGS);
}

/*
//玩家属性集合类
class AttributeSet
{
	protected $attributes = null;
	
	//初始化数组
	function __construct()
	{
		$attributes = new \SplFixedArray(NUMATTRIBUTES);
		foreach($attributes as $k => $v)
		{
			$attributes[$k] = 0;
		}
	}
	
	function get(int $const)
	{
		return $attributes[$const];
	}
	
	function set(int $const, int $value)
	{
		$attributes[$const] = $value;
	}
};
* */
//玩家属性----------------------------------------------------------------


//玩家权限----------------------------------------------------------------
define("NUMPLAYERRANKTYPES", 3);

define("REGULAR", 0);
define("GOD", 1);
define("ADMIN", 2);

$PLAYERRANKSTRINGS = array(
	"REGULAR",
	"GOD",
	"ADMIN"
);

function GetRank(string $str)
{
	global $PLAYERRANKSTRINGS;
	return StrToConst($str, $PLAYERRANKSTRINGS);
}

function GetRankString(int $const)
{
	global $PLAYERRANKSTRINGS;
	return ConstToStr($const, $PLAYERRANKSTRINGS);	
}
//玩家权限----------------------------------------------------------------


//物品属性----------------------------------------------------------------
define("NUMITEMTYPES", 3);

define("WEAPON", 0);
define("ARMOR", 1);
define("HEALING", 2);

$ITEMSTRINGS = array(
	"WEAPON",
	"ARMOR",
	"HEALING",
);

function GetItemType(string $str)
{
	global $ITEMSTRINGS;
	return StrToConst($str, $ITEMSTRINGS);
}

function GetItemTypeString(int $const)
{
	global $ITEMSTRINGS;
	return ConstToStr($const, $ITEMSTRINGS);	
}
//物品属性----------------------------------------------------------------


//房间类型----------------------------------------------------------------
define("NUMROOMTYPES", 3);

define("PLAINROOM", 0);
define("TRAININGROOM", 1);
define("STORE", 2);

$ROOMTYPESTRINGS = array(
	"PLAINROOM",
	"TRAININGROOM",
	"STORE"
);

function GetRoomType(string $str)
{
	global $ROOMTYPESTRINGS;
	return StrToConst($str, $ROOMTYPESTRINGS);
}

function GetRoomTypeString(int $const)
{
	global $ROOMTYPESTRINGS;
	return ConstToStr($const, $ROOMTYPESTRINGS);	
}
//房间类型----------------------------------------------------------------


//房间方向----------------------------------------------------------------
define("NUMDIRECTIONS", 4);

define("NORTH", 0);
define("EAST", 1);
define("SOUTH", 2);
define("WEST", 3);

$DIRECTIONSTRINGS = array(
	"NORTH",	//北
	"EAST",		//东
	"SOUTH",	//南
	"WEST"		//西
);

//取得指定方向的相反方向
function OppsiteDirection(int $dir)
{
	return ($dir+2) % 4;
}

function GetDirection(string $str)
{
	global $DIRECTIONSTRINGS;
	return StrToConst($str, $DIRECTIONSTRINGS);
}

function GetDirectionString(int $const)
{
	global $DIRECTIONSTRINGS;
	return ConstToStr($const, $DIRECTIONSTRINGS);	
}
//房间方向----------------------------------------------------------------
}//end namespace
