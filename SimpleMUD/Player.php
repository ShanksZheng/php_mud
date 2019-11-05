<?php
namespace SimpleMUD
{
require_once("Entity.php");
require_once("Attributes.php");
require_once("RoomDatabase.php");
require_once("Item.php");
require_once("ItemDatabase.php");
require_once(ROOT_PATH."/SocketLib/Connection.php");
require_once(ROOT_PATH."/BasicLib/FileStream.php");


//玩家类
class Player extends Entity
{
	use tPlayer;
		
	//玩家背包容量
	const PLAYERITEMCOUNT = 16;
	
	//玩家信息
	protected $pass = 'UNDEFINED';	 //密码
	protected $rank = REGULAR;		 //权限
	//角色属性
	protected $statpoints = 18;		 //剩余属性点数
	protected $experience = 0;		 //当前经验
	protected $level = 1;			 //等级
	protected $room = null;		 	 //所在房间
	protected $money = 0;			 //金钱
	protected $hitpoints = 0;		 //生命值
	protected $baseAttributes = null;//基础属性数组
	protected $attributes = null;	 //动态属性数组
	protected $nextAttacktime = 0;	 //下一次攻击的时间
	//角色背包和装备
	protected $inventory = null;	 //玩家背包数组(保存Item)
	protected $itemCount = 0;			//背包已使用格数
	protected $weapon = -1;			 //武器,这是一个背包数组的下标
	protected $armor = -1;			 //防具,这是一个背包数组的下标
	//不可保存的信息
	protected $connection = null;	 //玩家当前连接
	protected $loggedin = false;	 //是否已登陆
	protected $active = false;		 //是否是活动的
	protected $newbie = true; 		 //是否新手
	
	
	public function __construct()
	{
		$this->inventory = new \SplFixedArray(self::PLAYERITEMCOUNT);
		$this->baseAttributes = new \SplFixedArray(NUMATTRIBUTES);
		foreach($this->baseAttributes as $k => $v){ $this->baseAttributes[$k] = 0; }
		$this->attributes = new \SplFixedArray(NUMATTRIBUTES);
		foreach($this->attributes as $k => $v){ $this->attributes[$k] = 0; }
		
		$this->room = RoomDatabase::get(1);
		
		//基础力量，健康，敏捷初始化为1
		$this->baseAttributes[STRENGTH] = 1;
		$this->baseAttributes[HEALTH] = 1;
		$this->baseAttributes[AGILITY] = 1;
		
		//计算属性值
		$this->RecalculateStats();
		//计算生命点数
		$this->hitpoints = $this->GetAttr(MAXHITPOINTS);
	}
	
	//获取给定级数所需的经验
	public static function NeedForLevel(int $level)
	{
		return (int)( 100 * ( pow(1.4, $level-1) - 1) );
	}
	
	//玩家距离升级还需要多少经验
	public function NeedForNextLevel()
	{
		//到下一个等级还要多少经验
		return self::NeedForLevel($this->level+1) - $this->experience;
	}
	
	//角色升级逻辑
	public function Train()
	{
		//如果达到下一等级的经验足够
		if( $this->NeedForNextLevel() <= 0 )
		{
			//奖励2点属性点
			$this->statpoints += 2;
			//增加和角色等级相等的生命值上限，增加在基础属性集中
			$this->baseAttributes[MAXHITPOINTS] += $this->level;
			//升级
			$this->level++;
			//计算动态属性值
			$this->RecalculateStats();
			return true;
		}
		return false;
	}
	
	//获得当前等级
	public function Level()
	{
		return $this->level;
	}
	
	//最终属性计算函数
	public function RecalculateStats()
	{
		$this->attributes[MAXHITPOINTS] = 
			10 + (int)( $this->level * ($this->GetAttr(HEALTH) / 1.5) );
		
		$this->attributes[HPREGEN] = 
			( $this->GetAttr(HEALTH) / 5 ) + $this->level;
			
		$this->attributes[ACCURACY] = $this->GetAttr(AGILITY) * 3;
		$this->attributes[DODGING] = $this->GetAttr(AGILITY) * 3;
		$this->attributes[DAMAGEABSORB] = $this->GetAttr(STRENGTH) / 5;
		$this->attributes[STRIKEDAMAGE] = $this->GetAttr(STRIKEDAMAGE) / 5;	
		
		//处理生命值溢出
		if( $this->hitpoints > $this->GetAttr(MAXHITPOINTS) )
				$this->hitpoints = GetAttr(MAXHITPOINTS);
				
		//计算武器和盔甲带来的属性红利
		if( $this->Weapon() != 0 )
			$this->AddDynamicBonuses( $this->Weapon() );
		if( $this->Armor() != 0 )
			$this->AddDynamicBonuses( $this->Armor() );
	}
	
	//增加生命值
	public function AddHitpoints(int $hitpoints)
	{
		$this->SetHitpoints($this->hitpoints + $hitpoints);
	}
	
	//设置生命值
	public function SetHitpoints(int $hitpoints)
	{
		$this->hitpoints = $hitpoints;
		
		//防止生命值小于0或大于生命值上限
		if($this->hitpoints < 0)
			$this->hitpoints = 0;
		if( $this->hitpoints > $this->GetAttr(MAXHITPOINTS) )
			$this->hitpoints = $this->GetAttr(MAXHITPOINTS);
	}
	
	//获得生命值
	public function HitPoints()
	{
		return $this->hitpoints;
	}
	
	//获得最终属性(最终属性=基础属性+动态属性)
	public function GetAttr(int $attr)
	{
		$val = $this->attributes[$attr] + $this->baseAttributes[$attr];
		
		//小于0时修正为1
		if($attr == STRENGTH || $attr == AGILITY || $attr == HEALTH)
		{
			if($val < 1)
				return 1;
		}
		
		return $val;
	}
	
	//获取基础属性
	public function GetBaseAttr(int $attr)
	{
		return $this->baseAttributes[$attr];
	}
	
	//设置基础属性
	public function SetBaseAttr(int $attr, int $val)
	{
		$this->baseAttributes[$attr] = $val;
		$this->RecalculateStats();
	}
	
	//增加基础属性
	public function AddToBaseAttr(int $attr, int $val)
	{
		$this->baseAttributes[$attr] += $val;
		$this->RecalculateStats();
	}
	
	//获取属性点数
	public function StatPoints() {	return $this->statpoints; }
	public function SetStatPoints($arg) {	$this->statpoints = $arg; }
	//获取经验值
	public function Experience() {	return $this->experience; }
	public function AddExperience($arg) {	$this->experience += $arg; }
	//获取所在房间
	public function CurrentRoom() {	return $this->room; }
	public function SetCurrentRoom($arg) {	$this->room = $arg; }
	//获取金钱
	public function Money() { return $this->money; }
	public function AddMoney($arg) { $this->money += $arg; }
	//获取下一次攻击的时间
	public function NextAttackTime() { return $this->nextAttacktime; }
	public function SetNextAttackTime($arg) { $this->nextAttacktime = $arg; }
	//获取背包物品
	public function GetItem(int $index) { return $this->inventory[$index]; }
	//获取背包物品数
	public function ItemCount() { return $this->itemCount; }
	//获取背包最大空间数
	public function MaxItemCount() { return self::PLAYERITEMCOUNT; }
	
	//获取武器(id)
	public function Weapon()
	{
		if($this->weapon == -1)
			return 0;
		else
			return $this->inventory[$this->weapon];
	}
	
	//获取防具(id)
	public function Armor()
	{
		if($this->armor == -1)
			return 0;
		else
			return $this->inventory[$this->armor];		
	}
	
	//通过物品增加基础属性(例如通过所使用力量药永久增加1点力量)
	public function AddBonuses(Item $item)
	{
		foreach($this->baseAttributes as $k => $v)
		{
			$this->baseAttributes[$k] = $item->GetAttr($k);
		}
			
		$this->RecalculateStats();
	}
	
	//增加动态属性(例如通过装备武器，盔甲时获得的属性)
	public function AddDynamicBonuses(Item $item)
	{
		foreach($this->attributes as $k => $v)
		{
			$this->atributes[$k] = $item->GetAttr($k);
		}
			
		$this->RecalculateStats();
	}
	
	//拾取物品
	public function PickUpItem(Item $item)
	{
		if( $this->itemCount < $this->MaxItemCount() )
		{
			//遍历背包找到空位放入
			foreach($this->inventory as $k => $v)
			{
				if($v == 0)
				{
					$this->inventory[$k] = $item;
					$this->itemCount++;
					
					return true;
				}
			}
		}
		return false;
	}
	
	//丢弃物品
	public function DropItem(int $index)
	{
		if($this->inventory[$index] != null)
		{
			//如果需要，移除武器或防具
			if($this->weapon == $index)
				$this->RemoveWeapon();
			if($this->armor == $index)
				$this->RemoveArmor();
			
			$this->inventory[$index] = null;
			$this->itemCount--;
			
			return true;
		}
		
		return false;
	}
	
	//解除武器
	public function RemoveWeapon()
	{
		$this->weapon = -1;
		$this->RecalculateStats();
	}
	
	//解除防具
	public function RemoveArmor()
	{
		$this->weapon = -1;
		$this->RecalculateStats();
	}
	
	//装备武器
	public function UseWeapon(int $index)
	{
		$this->RemoveWeapon();
		$this->weapon = $index;
		$this->RecalculateStats();
	}
	
	//装备防具
	public function UseArmor(int $index)
	{
		$this->RemoveArmor();
		$this->armor = $index;
		$this->RecalculateStats();		
	}
	
	//根据name获取物品在背包的下标
	public function GetItemIndex(string $name)
	{
		foreach($this->inventory as $k => $v)
		{
			if($v != null && $v->MatchFull($name) )
				return $k;
		}
		foreach($this->inventory as $k => $v)
		{
			if($v != null && $v->Match($name) )
				return $k;
		}
		return -1;
	}
	
	//其他访问器
	public function Password() { return $this->pass; }
	public function SetPassword($arg) { $this->pass = $arg; }
	public function Rank() { return $this->rank; }
	public function SetRank($arg) { $this->rank = $arg; }
	public function Conn() { return $this->connection; }
	public function SetConn($arg) { $this->connection = $arg; }
	public function LoggedIn() { return $this->loggedin; }
	public function SetLoggedIn($arg) { $this->loggedin = $arg; }
	public function Active() { return $this->active; }
	public function SetActive($arg) { $this->active = $arg; }
	public function Newbie() { return $this->newbie; }
	public function SetNewbie($arg) { $this->newbie = $arg; }
	
	//向玩家连接发送一个字符串
	public function SendString(string $string)
	{
		$conn = $this->Conn();
		if( $conn == null )
		{
			global $ERRORLOG;
			$ERRORLOG->Log("Trying to send string to player".
				$this->Name()."but players is not connected.");
			return;
		}
		
		$conn->Protocol()->SendString($conn, $string.newline);
	}
	
	//将玩家的状态栏打印到玩家连接
	public function PrintStabar(bool $update=false)
	{
		$conn = $this->Conn();
		//如果这是一个状态更新，并且用户当前正在 键入某些内容，则不执行任何操作
		if($update && $conn->Protocol()->Buffered() > 0)
			return;
		
		$statbar = white . bold . "[";
		
		//获得生命值百分比
		$ratio = 100 * $this->HitPoints() / $this->GetAttr(MAXHITPOINTS); 
		
		//对你的生命值进行颜色编码，使其低为红色，中为黄色，高为绿色。
		if($ratio < 33)
			$statbar .= red;
		else if($ratio < 67)
			$statbar .= yellow;
		else
			$statbar .= green;
			
		$statbar .= $this->HitPoints() . white . "/" .
			$this->GetAttr(MAXHITPOINTS) . "]";
			
		$conn->Protocol()->SendString($conn, clearline."\r".$statbar.reset);
	}
	
};//end class


trait tPlayer
{
	//将文件中的角色信息加载到类中
	public function Load(\BasicLib\IStream $fs)
	{
		$temp = '';
		$fs->GetString($temp); $fs->GetInt($this->id);
		$fs->GetString($temp); $fs->GetString($this->name);
		$fs->GetString($temp); $fs->GetString($this->pass);
		$fs->GetString($temp); $fs->GetString($temp);
		$this->rank = GetRank($temp);
		$fs->GetString($temp); $fs->GetInt($this->statpoints);
		$fs->GetString($temp); $fs->GetInt($this->experience);
		$fs->GetString($temp); $fs->GetInt($this->level);
		$fs->GetString($temp); $fs->GetInt($temp); 
		$this->room = RoomDatabase::get($temp); 
		$this->room->AddPlayer($this);	//这句稍后删除，将写在登陆处理器
		$fs->GetString($temp); $fs->GetInt($this->money);
		$fs->GetString($temp); $fs->GetInt($this->hitpoints);
		$fs->GetString($temp); $fs->GetInt($this->nextAttacktime);
		
		foreach($this->baseAttributes as $k => $v)
		{
			//注意，spl数组不能被间接修改，所以用$temp得到值后赋值给spl数组元素
			$fs->GetString($temp); $fs->GetInt($temp);
			$this->baseAttributes[$k] = $temp;
		}

		$fs->GetString($temp); 
		foreach($this->inventory as $k => $v)
		{
			//注意，spl数组不能被间接修改，所以用$temp得到值后赋值给spl数组元素
			$fs->GetInt($temp);
			$this->inventory[$k] = $temp;
		}

		$fs->GetString($temp); $fs->GetInt($this->weapon);
		$fs->GetString($temp); $fs->GetInt($this->armor);
		
		$this->RecalculateStats();
	}
	
	//将类中的角色信息输出到字符串，玩家数据库会将它保存在文件中
	public function ToString()
	{
		$rank = GetRankString($this->rank);
		$roomId = $this->room->ID();

		$str = "[ID]             {$this->id}\n". 
			   "[NAME]           {$this->name}\n". 
			   "[PASS]           {$this->pass}\n". 
			   "[RANK]           {$rank}\n". 
			   "[STATPOINTS]     {$this->statpoints}\n". 
			   "[EXPERIENCE]     {$this->experience}\n". 
			   "[LEVEL]          {$this->level}\n". 
			   "[ROOM]           {$roomId}\n". 
			   "[MONEY]          {$this->money}\n". 
			   "[HITPOINTS]      {$this->hitpoints}\n". 
			   "[NEXTATTACKTIME] {$this->nextAttacktime}\n";
			   
		$baseAttrStr = '';
		foreach($this->baseAttributes as $k => $v)
		{
			$AttriFieldName = GetAttributeString($k);
			$baseAttrStr .= "[{$AttriFieldName}] {$v}\n";
		}
		$str .= $baseAttrStr;
		
		$inventoryStr = "[INVENTORY]      ";
		foreach($this->inventory as $v)
		{
			$inventoryStr .= "$v ";
		}
		$inventoryStr .= "\n";
		$str .= $inventoryStr;
		
		$str .=  "[WEAPON]         {$this->weapon}\n";
		$str .=  "[ARMOR]          {$this->armor}\n";
		
		return $str;
	}
};//end trait


//确定玩家是否处于活动状态的仿函数
class PlayerActive
{
	function __invoke(Player $p)
	{
		return $p->Active();
	}
};

//确定玩家是否已登陆的仿函数
class PlayerLoggedin
{
	function __invoke(Player $p)
	{
		return $p->LoggedIn();
	}
};

//向玩家发送字串的仿函数
class PlayerSend
{
	private $msg = '';
	
	function __construct(string $msg)
	{
		$this->msg = $msg;
	}
	
	function __invoke(Player $p)
	{
		$p->SendString($this->msg);
	}
};


}//end namespace
