<?php
namespace SimpleMUD
{
require_once(ROOT_PATH."/SocketLib/ConnectionHandler.php");
require_once(ROOT_PATH."/BasicLib/Time.php");
require_once(ROOT_PATH."/SocketLib/Connection.php");
require_once(ROOT_PATH."/BasicLib/function.php");
require_once("Train.php");
require_once("Attributes.php");
use \SocketLib\ConnectionHandler;
use \SocketLib\Connection;
use function \BasicLib\seconds;

//game处理器类
class Game extends ConnectionHandler
{
	protected $player = null;
	protected $lastCommand = '';
	protected static $timer = null;
	protected static $running = false;
	
	
	function __construct(Connection $conn, Player $player)
	{
		parent::__construct($conn);
		$this->player = $player;
	}
	
	
	function Handle(string $data)
	{
		$p = $this->player;
		
		//检查玩家是否想重复一个命令
		if($data == "/")
			$data = $this->lastCommand;
		else
			$lastCommand = $data;
		
		//格式化命令
		$words = explode(" ", $data);
		$firstword = strtolower($words[0]);
		
		
		//玩家命令-----------------------------------------------------------
		if($firstword == "chat" || $firstword == ":")
		{
			$text = remove_word($data, 0);
			self::SendGame(magenta.bold.$p->Name()." chats: ".white.$text);
			return;
		}
		
		if($firstword == "experience" || $firstword == "exp")
		{
			$p->SendString( $this->PrintExperience() );
			return;
		}
		
		if($firstword == "help" || $firstword == "commands")
		{
			$p->SendString( $this->PrintHelp() );
			return;
		}
		
		if($firstword == "inventory" || $firstword == "inv")
		{
			$p->SendString( $this->PrintInventory() );
			return;
		}
		
		if($firstword == "quit")
		{
			$this->conn->Close();
			$this->LogoutMessage($p->Name()."has left the realm.");
			return;
		}
		
		if($firstword == "remove")
		{
			$this->RemoveItem( parse_word($data, 1) );
			return;
		}
		
		if($firstword == "stats" || $firstword == "st")
		{
			$p->SendString( $this->PrintStats() );
			return;
		}
		
		if($firstword == "time")
		{
			$time = time();
			$p->SendString( bold.cyan.
				"The current system time is: ".date("h-i-s", $time).
				" on ".date("Y-m-d", $time).
				"\r\nThe system has been up for: ".
				self::$timer->GetString()
			);
			return;
		}
		
		if($firstword == "use")
		{
			$this->UseItem( remove_word($data, 0) );
		}
		
		if($firstword == "whisper")
		{
			$name = parse_word($data, 1);
			$message = remove_word( remove_word($data, 0), 0 );
			$this->Whisper($message, $name);
			return;
		}
		
		if($firstword == "who")
		{
			$str = strtolower( self::PrintWhoList( parse_word($data, 1) ) );
			$p->SendString($str);
			return;
		}
		
		if($firstword == "look" || $firstword == "1")
		{
			$p->SendString( self::PrintRoom( $p->CurrentRoom() ) );
			return;
		}
		
		if($firstword == "north" || $firstword == "n")
		{
			$this->Move(NORTH);
			return;
		}
		
		if($firstword == "east" || $firstword == "e")
		{
			$this->Move(EAST);
			return;
		}
		
		if($firstword == "south" || $firstword == "s")
		{
			$this->Move(SOUTH);
			return;
		}
		
		if($firstword == "west" || $firstword == "w")
		{
			$this->Move(WEST);
			return;			
		}
		
		if($firstword == "get" || $firstword == "take")
		{
			$this->GetItem( remove_word($data, 0) );
			return;
		}
		
		if($firstword == "drop")
		{
			$this->DropItem( remove_word($data, 0) );
			return;			
		}
		
		if($firstword == "train")
		{
			if( $p->CurrentRoom()->Type() )
			{
				$p->SendString(red.bold."You cannot train here!");
				return;
			}
			
			if( $p->Train() )
			{
				$p->SendString( green.bold."You are now level ".$p->Level() );
			}
			else
			{
				$p->SendString(red.bold."You don't have enough experience to train!");
			}
			
			return;
		}
		
		if($firstword == "editstats")
		{
			if($p->CurrentRoom()->Type() != TRAININGROOM)
			{
				$p->SendString(red.bold."You cannot edit your stats here!");
			}
			
			$this->GotoTrain();
			return;
		}
		
		if($firstword == "list")
		{
			if($p->CurrentRoom()->Type() != STORE)
			{
				$p->SendString(red.bold."You're not a store!");
				return;
			}
			
			$p->SendString( StoreList( $p->CurrentRoom()->Data() ) );
			return;
		}
		
		if($firstword == "buy")
		{
			if($p->CurrentRoom()->Type() != STORE)
			{
				$p->SendString(red.bold."You're not in a store!");
				return;
			}
			
			$this->Buy( remove_word($data, 0) );
			return;
		}
		
		if($firstword == "sell")
		{
			if($p->CurrentRoom()->Type() != STORE)
			{
				$p->SendString(red.bold."You're not in a store!");
				return;
			}
			
			$this->Sell( remove_word($data, 0) );
			return;
		}
		
		if($firstword == "attack" || $firstword == "a")
		{
			$this->PlayerAttack( remove_word($data, 0) );
			return;
		}
		
		//上帝命令----------------------------------------------------------
		if($firstword == "kick" && $p->Rank() >= GOD)
		{
			$target = PlayerDatabase::FindLoggedin( parse_word($data, 1) );
			if($target == null)
			{
				$p->SendString(red.bold."Player not online!");
				return;
			}
			
			if(  $p->Rank() < $target->Rank() )
			{
				$p->SendString(red.bold."You can't kick that player!");
				return;
			}
			
			$target->Conn()->Close();
			self::LogoutMessage($target->Name()."has been kicked by ".
				$p->Name()."!!!");
				
			return;
		}
		
		//管理员命令---------------------------------------------------------
		if($firstword == "annouce" && $p->Rank() >= ADMIN)
		{
			$this->Announce( remove_word($data, 0) );
			return;
		}
		
		if($firstword == "changerank" && $p->Rank() >= ADMIN)
		{
			$name = parse_word($data, 1);
			
			$target = PlayerDatabase::find($name);
			if($target == null)
			{
				$p->SendString(red.bold."could not find user ".$name);
				return;
			}
			
			$rank = GetRank( parse_word($data, 2) );
			$target->SetRank($rank);
			self::SendGame(
				green.bold.$target->Name()."'s rank has been changed to: ".
				GetRankString($rank)
			);
			
			return;
		}
		
		if($firstword == "reload" && $p->Rank() >= ADMIN)
		{
			$db = strtolower( parse_word($data, 1) );
			
			//!未完成
			if($db == "items")
			{
				ItemDatabase::Load();
				$p->SendString(bold.cyan."Item Database Reloaded!");
			}
			else
			{
				$p->SendString(bold.red."Invalid Database Name");
			}
			return;
		}
		
		if($firstword == "shutdown" && $p->Rank() >= ADMIN)
		{
			self::Announce("SYSTEM IS SHOUING DOWN");
			self::SetRunning(false);
		}
		
		//命令无法识别，发送到房间
		self::SendRoom( bold.$p->Name()." says: ".dim.$data, 
			$p->CurrentRoom() );
	}
	
	//连接进入处理
	function Enter()
	{
		global $USERLOG;
		$p = $this->player;
		
		$USERLOG->Log( $this->conn->GetRemoteIPAddress()." = User".
			$p->Name()." entering Game state.");
			
		$this->lastCommand = "";
		
		$p->SetActive(true);
		$p->SetLoggedIn(true);
		
		self::SendGame(bold.green.$p->Name()." has entered the realm.");
		
		//如果玩家是新手，则跳转到训练处理器
		if( $p->Newbie() )
			$this->GotoTrain();
	}
	
	//连接离开处理，将玩家设置为离线状态
	function Leave()
	{
		//设置玩家为非活动状态
		$this->player->SetActive(false);
		
		//如果连接已关闭，从数据库中设置玩家为离线状态
		if( $this->conn->Closed() )
			PlayerDatabase::Logout($this->player);
	}
	
	//通知其他玩家当前玩家因连接断开被踢出
	function Hungup()
	{
		self::LogoutMessage($this->player->Name()."has disappeared from the realm.");
	}
	
	//通知其他玩家当前玩家因泛洪被踢出
	function Flooded()
	{
		self::LogoutMessage($this->player->Name()."has been kicked for flooding!");
	}
	
	//跳转到训练处理器
	function GotoTrain()
	{
		$p = $this->player;
		$p->SetActive(false);
		$p->Conn()->AddHandler( new Train( $this->conn, $p ) );
		self::LogoutMessage( $p->Name()." leaves to edit stats" );
	}
	
	//向所有已登陆玩家发送字符串
	static function SendGlobal(string $str)
	{
		//static $playerSend = new PlayerSend();
		//static $playerLoggedin = new PlayerLoggedin();
		PlayerDatabase::OperateOnIf(new PlayerSend($str), new PlayerLoggedin());		
	}
	
	//向所有活动玩家发送字符串
	static function SendGame(string $str)
	{
		PlayerDatabase::OperateOnIf(new PlayerSend($str), new PlayerActive());
	}
	
	//向已登陆玩家发送退出公告
	static function LogoutMessage(string $reason)
	{
		self::SendGame(red.bold.$reason);
	}
	
	//向已登陆玩家发送公告
	static function Announce(string $announcement)
	{
		self::SendGlobal(cyan.bold."System Announcement: ".$announcement);
	}
	
	//当前玩家向指定玩家发送私聊
	function Whisper(string $str, string $player)
	{
		$to = PlayerDatabase::FindActive($player);
		if($to == null)
		{
			$this->player->SendString(red.bold."Error, cannot find user.");
		}
		else
		{
			$to->SendString(
				yellow."You whisper to you:".$this->player->Name().
				": ".reset.$str );
			$this->player->SendString(
				yellow."You whisper to ".$to->Name().": ".reset.$str);
		}
	}
	
	static function PrintWhoList(string $who)
	{
		$str = white.bold.
			"----------------------------------------------------------------------------\r\n".
			"Name             | Level     | Activity | Rank\r\n".
			"----------------------------------------------------------------------------\r\n";
		
		$wholist = new Wholist();
		if($who == "all")
		{
			PlayerDatabase::OperateOn($wholist);
		}
		else
		{
			PlayerDatabase::OperateOnIf( $wholist, new PlayerLoggedin() );
		}
		
		$str .= $wholist->str;
		$str .= "----------------------------------------------------------------------------\r\n";
		
		return $str;
	}
	
	static function PrintHelp(int $rank = REGULAR)
	{
		$help = white.bold.
			"--------------------------------- Command List ---------------------------------\r\n".
			" /                          - Repeats your last command exactly.\r\n".
			" chat <mesg>                - Sends message to everyone in the game\r\n".
			" experience                 - Shows your experience statistics\r\n".
			" help                       - Shows this menu\r\n".
			" inventory                  - Shows a list of your items\r\n".
			" quit                       - Allows you to leave the realm.\r\n".
			" remove <'weapon'/'armor'>  - removes your weapon or armor\r\n".
			" stats                      - Shows all of your statistics\r\n".
			" time                       - shows the current system time.\r\n".
			" use <item>                 - use an item in your inventory\r\n".
			" whisper <who> <msg>        - Sends message to one person\r\n".
			" who                        - Shows a list of everyone online\r\n".
			" who all                    - Shows a list of everyone\r\n".
			" look                       - Shows you the contents of a room\r\n".
			" north/east/south/west      - Moves in a direction\r\n".
			" get/drop <item>            - Picks up or drops an item on the ground\r\n".
			" train                      - Train to the next level (TR)\r\n".
			" editstats                  - Edit your statistics (TR)\r\n".
			" list                       - Lists items in a store (ST)\r\n".
			" buy/sell <item>            - Buy or Sell an item in a store (ST)\r\n".
			" attack <enemy>             - Attack an enemy\r\n";


		$god = yellow.bold.
			"--------------------------------- God Commands ---------------------------------\r\n".
			" kick <who>                 - kicks a user from the realm\r\n";

		$admin = green.bold.
			"-------------------------------- Admin Commands --------------------------------\r\n".
			" announce <msg>             - Makes a global system announcement\r\n".
			" changerank <who> <rank>    - Changes the rank of a player\r\n".
			" reload <db>                - Reloads the requested database\r\n".
			" shutdown                   - Shuts the server down\r\n";

		$end = white.bold.
			"--------------------------------------------------------------------------------";


		if( $rank == REGULAR )
			return $help.$end;
		else if( $rank == GOD )
			return $help.$god.$end;
		else if( $rank == ADMIN )
			return $help.$god.$admin.$end;
		else
			return "ERROR";
	}
	
	//打印玩家的状态
	function PrintStats()
	{
		$p = $this->player;

		return white.bold.
			"---------------------------------- Your Stats ----------------------------------\r\n".
			" Name:          ".$p->Name()."\r\n".
			" Rank:          ".$p->Rank()."\r\n".
			" HP/Max:        ".$p->HitPoints()."/" .$p->GetAttr(MAXHITPOINTS).
			"  (".(int)( 100*$p->HitPoints()/$p->GetAttr(MAXHITPOINTS) )."%)\r\n".
			$this->PrintExperience()."\r\n".
			" Strength:      ".sprintf( "%16s", $p->GetAttr(STRENGTH) ).
			" Accuracy:      ".$p->GetAttr(ACCURACY)."\r\n".
			" Health:        ".sprintf( "%16s", $p->GetAttr(HEALTH) ).
			" Dodging:       ".$p->GetAttr( DODGING )."\r\n".
			" Agility:       ".sprintf( "%16s", $p->GetAttr( AGILITY ) ).
			" Strike Damage: ".$p->GetAttr( STRIKEDAMAGE )."\r\n".
			" StatPoints:    ".sprintf( "%16s", $p->StatPoints() ).
			" Damage Absorb: ".$p->GetAttr( DAMAGEABSORB )."\r\n".
			"--------------------------------------------------------------------------------";
	}
	
	//打印玩家经验值
	function PrintExperience()
	{
		$p = $this->player;
		
		return white.bold.
			" Level:		".$p->Level()."\r\n".
			" Experience:	".$p->Experience()."/".
			$p->NeedForLevel( $p->Level()+1 )." (".
			(int)( 100 * $p->Experience() / $p->NeedForLevel($p->Level()+1) )."%)";

	}
	
	//打印玩家的物品清单
	function PrintInventory()
	{
		$p = $this->player;
		
		$itemlist = white.bold.
			"-------------------------------- Your Inventory --------------------------------\r\n".
			" Items:  ";
			
		for($i=0; $i<PLAYERITEMS; $i++)
		{
			if( $p->GetItem($i) != null )
			{
				$itemlist .= $p->GetItem($i)->Name().", ";
			}
		}
		$itemlist = rtrim($itemlist, ", ");
		$itemlist .= "\r\n";
		
		$itemlist .= "Weapon: ";
		if( $p->Weapon() == 0 )
			$itemlist .= "NONE!";
		else
			$itemlist .= $p->Weapon()->Name();
			
		$itemlist .= "\r\n Armor: ";
		if( $p->Armor() == 0 )
			$itemlist .= "NONE!";
		else
			$itemlist .= $p->Armor()->Name();
			
		$itemlist .= "\r\n Money: $".$p->Money();
		$itemlist .= "\r\n--------------------------------------------------------------------------------";
		
		return $itemlist;
	}
	
	
	//查找并使用背包中的物品
	function UseItem(string $item)
	{
		$p = $this->player;
		$idx = $p->GetItemIndex($item);
		
		if($idx == -1)
		{
			$p->SendString(red.bold."Could not find that item!");
			return false;
		}
		
		$itm = $p->GetItem($idx);
		
		switch( $itm->Type() )
		{
			case WEAPON:
				$p->UseWeapon($idx);
				return true;
			break;
			
			case ARMOR:
				$p->UseArmor($idx);
				return true;
			break;
			
			case HEALING:
				$p->AddBonuses($itm);
				$p->AddHitpoints( mt_rand( $itm->Min(), $itm->MAX() ) );
				$p->DropItem($idx);
				return true;
		}
		
		return false;
	}
	
	//移除物品或防具
	function RemoveItem(string $item)
	{
		$p = $this->player;
		$itm = strtolower($item);
		
		if($item == "weapon" && $p->Weapon() != null)
		{
			$p->RemoveWeapon();
			return true;
		}
		
		if($item == "armor" && $p->Armor() != null)
		{
			$p->RemoveArmor();
			return true;
		}
		
		$p->SendString(red.bold."Could not Remove item!");
		return false;
	}
	
	static function GetTimer() { return self::$timer; }
	static function SetTimer($arg) { self::$timer = $arg; }
	static function Running() { return self::$running; }
	static function SetRunning($arg) { self::$running = $arg; }
	
	//打印房间信息
	static function PrintRoom(Room $room)
	{
		$desc = "\r\n".bold.white.$room->Name()."\r\n";
		$temp = '';
		$count;
		
		$desc .= bold.magenta.$room->Description()."\r\n";
		$desc .= bold.green."exits: ";
		
		global $DIRECTIONSTRINGS;
		for($i=0; $i<NUMDIRECTIONS; $i++)
		{
			if($room->Adjacent($i) != null)
				$desc .= $DIRECTIONSTRINGS[$i]." ";
		}
		$desc .= "\r\n";
		
		//ITEMS
		$temp = bold.yellow."you see: ";
		$count = 0;
		if($room->Money() > 0)
		{
			$count++;
			$temp .= "$".$room->Money().", ";
		}
		$items = $room->Items();
		if( count($items) )
			foreach($items as $v)
			{
				$count++;
				$temp .= $v->Name().", ";
			}
		if($count > 0)
			$temp = rtrim($temp, ", ");
		$desc .= $temp."\r\n";
		
		
		//PLAYERS
		$temp = bold.cyan."Players: ";
		$count = 0;
		$players = $room->Players();
		if( count($players) )
			foreach($players as $v)
			{
				$count++;
				$temp .= $v->Name().", ";
			}
		if($count > 0)
			$temp = rtrim($temp, ", ");
		$desc .= $temp."\r\n";
		
		
		//ENEMYS
		$temp = bold.red."enemys: ";
		$count = 0;
		$enemys = $room->Enemys();
		if( count($enemys) )
			foreach($enemys as $v)
			{
				$count++;
				$temp .= $v->Name().", ";
			}
		if($count > 0)
			$temp = rtrim($temp, ", ");
		$desc .= $temp."\r\n";		
		
		return $desc;		
	}
	
	//向房间中的玩家发送信息
	static function SendRoom(string $text, Room $room)
	{
		$players = $room->Players();
		$playerSend = new PlayerSend($text);
		if( count($players) )
			foreach($players as $v)
			{
				$playerSend($v);
			}
	}
	
	//移动
	function Move(int $direction)
	{
		$p = $this->player;
		$next = $p->CurrentRoom()->Adjacent($direction);
		$prev = $p->CurrentRoom();
		
		global $DIRECTIONSTRINGS;
		
		if($next == null)
		{
			self::SendRoom(
				red.$p->Name()." bumps into the wall to the ".
				$DIRECTIONSTRINGS[$direction]."!!!",
				$p->CurrentRoom()
			);
			return;
		}
		
		$prev->RemovePlayer($p);
		
		self::SendRoom(
			green.$p->Name()." leaves to the".
			$DIRECTIONSTRINGS[$direction].".",
			$prev
		);
		self::SendRoom(
			green.$p->Name()." enters from the".
			$DIRECTIONSTRINGS[$direction].".",
			$next
		);		
		$p->SendString(green."you walk ".$DIRECTIONSTRINGS[$direction].".");
		
		$p->SetCurrentRoom($next);
		$next->AddPlayer($p);
		$p->SendString( self::PrintRoom($next) );
	}
	
	//获取金钱或物品
	function GetItem(string $item)
	{
		$p = $this->player;
		
		if($item[0] == '$')
		{
			$item = substr($item, 2);
			$m = (int)$item;	
			
			//确保房间有足够的金钱
			if( $m > $p->CurrentRoom()->Money() )		
				$p->SendString(red.bold."There isn't that much here!");
			else
			{
				$p->AddMoney($m);
				$p->CurrentRoom()->AddMoney(-$m);
				self::SendRoom(
					cyan.bold.$p->Name()."picks up $ ".$m.".",
					$p->CurrentRoom()
				);
			}
			
			return;
		}
		
		$itm = $p-CurrentRoom()->FindItem($item);
		if($itm == null)
		{
			$p->SendString(red.bold."You don't see that here!");
			return;
		}
		
		if( $p->PickUpItem($itm) )
		{
			$p->SendString(red.bold.$p->Name()."You can't carry that much!");
			return;
		}
		
		$p->CurrentRoom()->RemoveItem($itm);
		self::SendRoom(
			cyan.bold.$p->Name()."picks up".$itm->Name().".",
			$p->CurrentRoom()
		);
	}
	
	//丢弃金钱或物品
	function DropItem(string $item)
	{
		$p = $this->player;
		
		if($item[0] == '$')
		{
			$item = substr($item, 2);
			$m = (int)$item;
			
			//确保背包里有足够的金钱
			if( $m > $p->Money() )
				$p->SendString(red.bold."you don't have that much!");
			else
			{
				$p->AddMoney(-$m);
				$p->CurrentRoom()->AddMoney($m);
				self::SendRoom(
					cyan.bold.$p->Name()." drops $".$m.".",
					$p->CurrentRoom()
				);
			}
			return;
		}
		
		$index = $p->GetItemIndex($item);
		if($index == -1)
		{
			$p->SendString(red.bold."You don't have that!");
			return;
		}
		
		self::SendRoom(
			cyan.bold.$p->Name()."drops ".$p->GetItem($index)->Name().".",
			$p->CurrentRoom()
		);
		$p->CurrentRoom()->AddItem( $p->GetItem($index) );
		$p->DropItem($index);		
	}
	
	//打印商店列表
	static function StoreList(int $store)
	{
		$p = $this->player;
		$s = StoreDatabase::get($store);
		if($s == null)
		{
			$p->SendString("Store does not exist!");
			return;
		}
		
		$output = white.bold.
			"-----------------------------------------------------------------------------------";
		$output .= " Welcome to ".$s->Name()."!\r\n";
		$output .= "-----------------------------------------------------------------------------------";
		$output .= " Item									| Price\r\n";
		$output .= "-----------------------------------------------------------------------------------";
		
		$items = $s->Items();
		if( count($items) )
			foreach($items as $v)
			{
				$output .= " ".sprintf("%31s", $v->Name)."| ";
				$output .= $v->Price()."\r\n";
			}
		$output .= bold."-----------------------------------------------------------------------------------";
		
		return $output;	
	}
	
	//购买物品
	function Buy(string $item)
	{
		$p = $this->player;
		if( $p->CurrentRoom()->Data() == 0 )
		{
			$p->SendString(red.bold."Illegal request!");
			return;			
		}
		
		$s = StoreDatabase::get( $p->CurrentRoom()->Data() );
		
		$itm = $s->Find($item);
		if($itm == null)
		{
			$p->SendString(red.bold."Sorry, we don't have that item!");
			return;
		}
		
		if( $p->Money() < $itm->Price() )
		{
			$p->SendString(red.bold."Sorry, but you can't afford that!");
			return;
		}
		
		if( $p->PickUpItem($itm) )
		{
			$p->SendString(red.bold."Sorry, but you can't carry that much!");
			return;
		}
		
		$p->AddMoney( -( $itm->Price() ) );
		self::SendRoom( cyan.bold.$p->Name()." buys a ".$itm->Name(),
			$p->CurrentRoom() ); 
	}
	
	//贩卖物品
	function Sell(string $item)
	{
		$p = $this->player;
		if( $p->CurrentRoom()->Data() == 0 )
		{
			$p->SendString(red.bold."Illegal request!");
			return;			
		}
			
		$s = StoreDatabase::get( $p->CurrentRoom()->Data() );
		
		$index = $p->GetItemIndex($item);
		if($index == -1)
		{
			$p->SendString(red.bold."Sorry, you don't have that!");
			return;
		}
		
		$itm = $p->GetItem($index);
		if( !$s->has($i) )
		{
			$p->SendString(red.bold."Sorry, we don't want that item!");
			return;
		}
		
		$p->DropItem($index);
		$p->AddMoney( $itm->Price() );
		SendRoom(
			cyan.bold.$p->Name()." sells a".$itm->Name(),
			$p->CurrentRoom()
		);
	}
	
	//敌人攻击动作
	static function EnemyAttack(Enemy $enemy)
	{
		$room = $enemy->CurrentRoom();
		
		$p = $room->RandPlayer();
		if($p == null)
			return;
		
		$now = self::GetTimer().GetMS();
		
		$damage = 0;
		if( $enemy->Weapon() == 0 )
		{
			$damage = mt_rand(1, 3);
			$enemy->SetNextAttackTime( $now + seconds(1) );
		}
		else
		{
			$damage = mt_rand( $enemy->Weapon()->Min(), $enemy->Weapon()->Max() );
			$enemy->SetNextAttackTime( $now + seconds( $enemy->Speed() ) );
		}
		
		//
		$damage += $enemy->StrikeDamage();
		$damage -= $p->GetAttr(DAMAGEABSORB);
		
		if($damage < 1)
			$damage = 1;
			
		$p->AddHitpoints(-$damage);
		
		self::SendRoom( red.$enemy." hits ".$player->Name()." for ".
			$damage." damage!", $enemy->CurrentRoom() );
			
		if( $p->HitPoints() <= 0 )
			$p->PlayerKilled($p);
	}
	
	//玩家死亡处理
	static function PlayerKilled(Player $player)
	{
		self::SendRoom( red.bold.$player." has died!", $player->CurrentRoom() );
		
		//减去玩家百分之十的金钱
		$m = $player->Money() / 10;
		if($m > 0)
		{
			$p->CurrentRoom()->AddMoney($m);
			$p->AddMoney(-$m);
			self::SendRoom( cyan."$".$m." drops to the ground.", 
				$player->CurrentRoom() );
		}
		
		//随机丢弃玩家身上一件物品到房间
		if( $player->Items() > 0 )
		{
			$index = -1;
			while( $player->GetItem($index = mt_rand(0, PLAYERITEMS-1)) == 0 )
			$item = $player->GetItem($index);
			$player->CurrentRoom()->AddItem($item);
			$player->DropItem($index);
			
			self::SendRoom( cyan.$i->Name()." drops to the ground.", 
				$player->CurrentRoom() );
		}
		
		$exp = $player->Experience() / 10;
		$player->AddExperience(-$exp);
		
		$player->CurrentRoom()->RemovePlayer($player);
		$player->SetCurrentRoom( RomDatabase::get(1) );
		$player->CurrentRoom()->AddPlayer($player);
		
		$player->SetHitpoints( (int)($player->GetAttr(MAXHITPOINTS)*0.7) );
		$player->SendString( white.bold."You have died, but have been ".
			"ressurected in ".$player->CurrentRoom()->Name() );
		$player->SendString( red.bold."You have lost ".$exp." experience!" );
		self::SendRoom( white.bold.$player->Name()." appears out of nowhere!",
			$player->CurrentRoom() );
	}
	
	//玩家攻击动作
	function PlayerAttack(string $enemy)
	{
		$p = $this->player;
		//获得当前时间
		$now = Game::GetTimer()->GetMS();
		
		//如果未到达下一次攻击的时间，则提示玩家并返回
		if(  $now < $p->NextAttackTime() )
		{
			$p->SendString(red.bold."You can't attack yet!");
			return;
		}
		
		//获得攻击目标
		$e = $p->CurrentRoom()->FindEnemy($enemy);
		if($e == null)
		{
			$p->SendString(red.bold."You don't see that here!");
			return;
		}
		
		//计算伤害
		$damage = 0;
		if( $p->Weapon() == null )
		{
			//随即1～3之间的数值作为伤害
			$damage = mt_rand(1,3);
			//下一次攻击时间为1秒后
			$p->SetNextAttackTime( $now+seconds(1) );
		}
		else
		{
			$damage = mt_rand( $p->Weapon()->Min(), $p->Weapon()->Max() );
			$p->SetNextAttackTime( $now + seconds( $p->Weapon()->Speed() ) );
		}
		
		if( mt_rand(0, 99) >= $p->GetAttr(ACCURACY) - $e->Dodging() )
		{
			self::SendRoom(white.$p->Name()." swings at ".$e->Name().
				" but misses!", $p->CurrentRoom() );
				return;
		}
		
		$damage += $p->GetAttr(STRIKEDAMAGE);
		$damage -= $e->DamageAbsorb();
		
		//伤害修正
		if($damage < 1)
			$damage = 1;
		
		//
		$e->AddHitPoints(-$damage);
		
		self::SendRoom( red.$p->Name()." hits ".$e->Name()." for ".$damage.
			" damage!", $p->CurrentRoom() );
			
		if($e->HitPoints() <= 0)
			self::EnemyKilled($e, $p);
	}
	
	//敌人死亡处理
	static function EnemyKilled(Enemy $enemy, Player $player)
	{
		self::SendRoom( cyan.bold.$enemy->Name()."has died!", $enemy->CurrentRoom() );
		
		//掉下金钱
		//随机一个掉落金钱值，并增加到玩家
		$m = mt_rand( $enemy->MoneyMin(), $enemy->MoneyMax() );
		if($m > 0)
		{
			$enemy->CurrentRoom()->AddMoney($m);
			self::SendRoom( cyan."$".$m." drops to the ground.".$enemy->CurrentRoom() );
		}
		
		//掉下物品
		//遍历敌人掉落物品列表
		$lootList = $enemy->LootList();
		foreach($lootList as $k => $v)
		{
			//随机一个0～99之中的数。
			//如果小于物品掉率，则掉下该物品到房间中并通知房间中的玩家
			if( mt_rand(0, 99) < $v['second'] )
			{
				$enemy->CurrentRoom()->AddItem($v['first']);
				self::SendRoom( cyan.$v['first']->Name()." drops to the ground.", 
					$enemy->CurrentRoom() );
			}
		}
		
		//将敌人经验值增加到玩家，并通知玩家
		$player->AddExperience( $enemy->Experience() );
		$player->SendString(cyan.bold."You gain ".$enemy->Experience()." experience.");
		
		//将敌人从敌人数据库删除
		EnemyDatabase::Delete($enemy);
	}
};//end class Game
Game::SetTimer( new \BasicLib\Timer() );


//向玩家发送"所有玩家列表"的仿函数
class Wholist
{
	public $str = '';
	
	function __invoke(Player $p)
	{
		$this->str .= "".sprintf( "%17s", $p->Name() )."| ";
		$this->str .= sprintf( "%10s", $p->Level() )."| ";
		
		if( $p->Active() )
			$this->str .= green."Online  ".white;
		else if( $p->LoggedIn() )
			$this->str .= yellow."Inactive".white;
		else
			$this->str .= red."Offline ".white;
			
		$this->str .= " | ";
		switch( $p->Rank() )
		{
			case REGULAR:
				$this->str .= white;
			break;
			case GOD:
				$this->str .= yellow;
			break;
			case ADMIN:
				$this->str .= green;
			break;
		}
		
		$this->str .= GetRankString( $p->Rank() );
		$this->str .= white."\r\n";
	}
};


}//end namespace
