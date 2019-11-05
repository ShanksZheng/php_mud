<?php
namespace SimpleMUD
{
require_once(dirname(__FILE__)."/../SocketLib/ConnectionHandler.php");
require_once(dirname(__FILE__)."/../SocketLib/Connection.php");
use \SocketLib\ConnectionHandler;
use \SocketLib\Connection;

//训练处理器
class Train extends ConnectionHandler
{
	protected $player = null;
	
	//
	public function __construct(Connection $conn, Player $player)
	{
		parent::__construct($conn);
		$this->player = $player;
	}
	
	//
	public function Handle(string $data)
	{
		$p = $this->player;
		
		if($data == "quit")
		{
			//将玩家保存到磁盘
			PlayerDatabase::SavePlayer($p);
			//返回上一个处理程序
			$p->Conn()->RemoveHandler();
			return;
		}
		
		$n = $data[0];
		if($n>='1' && $n<='3')	//确保数字是1,2或者3
		{
			$statPoints = $p->StatPoints();
			if( $statPoints > 0 )	//检查用户点数
			{
				//减去一个点数
				$p->SetStatPoints(--$statPoints);
				//在attribute枚举中，力量，健康和敏捷枚举分别给定了0,1,2
				//n-'1'分别对应这3个属性的下标
				$p->AddToBaseAttr($n-'1', 1);	//增加点数到基础属性
			}
		}
		$this->PrintStats(true);
	}
	
	//
	public function Enter()
	{
		$p = $this->player;
		$p->SetActive(false);	//使玩家“不活动”
		
		if( $p->Newbie() )
		{
			$p->SendString(magenta.bold."Welcome to SimpleMUD, ".$p->Name().
			"!\r\nYou must train your character with your desired stats,\r\n".
			"before you enter the realm.\r\n\r\n");
			
			$p->SetNewbie(false);
		}
		
		$this->PrintStats(false);
	}
	
	//
	public function Leave(){}
	
	//
	public function Hungup()
	{
		PlayerDatabase::Logout($this->player);
	}
	
	//
	public function Flooded()
	{
		PlayerDatabase::Logout($this->player);
	}
	
	//
	public function PrintStats(bool $clear = true)
	{
		$p = $this->player;
		if($clear)
			$p->SendString(clearscreen);
		
		$p->SendString(white.bold.
			"-------------------------- Your Stats --------------------------\r\n".
			"Player:			".$p->Name()."\r\n".
			"Level:				".$p->Level()."\r\n".
			"Stat Points Left:	".$p->StatPoints()."\r\n".
			"Strength:			".$p->GetAttr(STRENGTH)."\r\n".
			"Health:			".$p->GetAttr(HEALTH)."\r\n".
			"Agility:			".$p->GetAttr(AGILITY)."\r\n".
			bold.
			"----------------------------------------------------------------\r\n".
			"Enter 1, 2, or 3 to add a stat point, or \"quit\" to enter the realm: "
		);
	}
};//end class Train

}//end namespace
