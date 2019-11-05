<?php
namespace SimpleMUD
{
require_once("Entity.php");
require_once("Attributes.php");
require_once("RoomDatabase.php");


//房间类
class Room extends Entity
{
	use tRoom;
	
	//他们保存在maps/defalt.map文件中
	protected $type = 0;					//房间的类型
	protected $data = 0;					//如果它是一个商店，则为商店ID
	protected $description = "UNDEFINED";	//房间的描述信息
	protected $rooms = null;				//房间4个方向连接的房间(存放房间id不是对象)
	
	protected $spawnWhich = 0;				//产生哪个敌人
	protected $maxEnemys = 0;				//最多多少敌人
	
	//以下是易失性数据，他们保存在maps/default.data文件中
	protected $items = array();				//房间中的物品
	protected $money = 0;					//地板上的金钱
	
	//以下是临时数据，他们不会保存在数据库
	protected $players = array();			//房间中的玩家
	protected $enemys = array();			//房间中的敌人
	
	function __construct()
	{
		$this->rooms = new \SplFixedArray(NUMDIRECTIONS);
		//数组存放数值时，必须初始化
		foreach($this->rooms as $k => $v)
			$this->rooms[$k] = 0;
	}
	
	function Type()
	{
		return $this->type;
	}
	
	function Data()
	{
		return $this->data;
	}
	
	function Description()
	{
		return $this->description;
	}
	
	//返回给定方向上与本房间相邻的房间
	function Adjacent(int $dir)
	{
		return RoomDatabase::get($this->rooms[$dir]);
	}
	
	function spawnWhich()
	{
		return $this->spawnWhich;
	}
	
	function Items()
	{
		return $this->items;
	}
	
	function Money()
	{
		return $this->money;
	}
	
	function Enemys()
	{
		return $this->enemys;
	}
	
	function MaxEnemys()
	{
		return $this->maxEnemys;
	}
	
	function Players()
	{
		return $this->players;
	}
	
	function RandPlayer()
	{
		if( count($this->players) > 0 )
			return array_rand($this->players, 1);
		return null;
	}
	
	function AddPlayer(Player $player)
	{
		array_push($this->players, $player);
	}
	
	function RemovePlayer(Player $player)
	{
		$key = array_search($player, $this->players);
		array_unset($this->players, $key);
	}
	
	function FindItem(string $name)
	{
		foreach($this->items as $k => $v)
		{
			if($v->Name() == $name)
				return $this->items[$k];
		}
		
		return null;
	}
	
	function AddItem(Item $item)
	{
		array_push($this->items, $item);
	}
	
	function RemoveItem(Item $item)
	{
		$key = array_search($item, $this->items);
		array_unset($this->items, $key);
	}
	
	function FindEnemy(string $name)
	{
		foreach($this->enemys as $k => $v)
		{
			if($v->Name() == $name)
				return $this->enemys[$k];
		}
		
		return null;		
	}
	
	function AddEnemy(Enemy $enemy)
	{
		array_push($this->enemys, $enemy);
	}
	
	function RemoveEnemy(Enemy $enemy)
	{
		$key = array_search($enemy, $this->enemys);
		array_unset($this->enemys, $key);
	}
	
	function AddMoney(int $money)
	{
		$this->money += $money;
	}
};//end class Room


trait tRoom
{
	function LoadTemplate(\BasicLib\IStream $fs)
	{
		$temp = '';
		$fs->GetString($temp); $fs->GetInt($this->id); 
		$fs->GetString($temp); $fs->GetLine($this->name); 
		$fs->GetString($temp); $fs->GetLine($this->description); 
		$fs->GetString($temp); $fs->GetString($temp);
		$this->type = GetRoomType($temp); 
		$fs->GetString($temp); $fs->GetInt($this->data); 
		
		foreach($this->rooms as $k => $v)
		{
			$fs->GetString($temp);
			$fs->GetInt($temp);
			$this->rooms[$k] = $temp;
		}
		
		$fs->GetString($temp); $fs->GetInt($temp);
		$this->spawnWhich = EnemyTemplateDatabase::get($temp);
		$fs->GetString($temp); $fs->GetInt($this->maxEnemys);	
	}
	
	function LoadData(\BasicLib\IStream $fs)
	{
		$temp = '';
		$item = 0;
		$fs->GetString($temp);
		while( $fs->GetInt2($item) != 0)
		{
			array_push($this->items, $item);
		}
		
		$fs->GetString($temp);
		$fs->GetInt($money);
	}
	
	function DataToString()
	{
		$str = "[ITEMS] ";
		foreach($this->items as $k => $v)
		{
			$str .= "{$v} ";
		}
		$str .= "0\n";
		
		$str .= "[MONEY] {$this->money}\n";
		
		return $str;
	}
};//end trait tRoom


}//end namespace
