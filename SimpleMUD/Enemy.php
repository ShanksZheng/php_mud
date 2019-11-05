<?php
namespace SimpleMUD
{
require_once("Entity.php");

//敌人模板类
//该类将敌人的生命值上限、命中率、闪避率等不变的属性放在该类中，
//通过访问器来访问，得以节省内存
class EnemyTemplate extends Entity
{
	use tEnemyTemplate;
	
	public $hitpoints = 0;		//生命值上限
	public $accuracy = 0;		//命中率
	public $dodging = 0;		//闪避率
	public $strikeDamage = 0;	//打击伤害
	public $damageAbsorb = 0;	//伤害吸收
	public $experience = 0;		//被杀死时提供的经验
	public $weapon = 0;			//敌人使用的武器(这里保存物品的id)
	public $moneyMin = 0;		//敌人死亡时留下的最小金钱数
	public $moneyMax = 0;		//敌人死亡时留下的最大金钱数
	public $loot = array();		//敌人死亡时留下的物品的列表
};//end class EnemyTemplate


trait tEnemyTemplate
{
	function Load(\BasicLib\IStream $fs)
	{
		$temp = '';
		
		$fs->GetString($temp); $fs->GetInt($this->id);
		$fs->GetString($temp); $fs->GetLine($this->name);
		$fs->GetString($temp); $fs->GetInt($this->hitpoints);		
		$fs->GetString($temp); $fs->GetInt($this->accuracy);		
		$fs->GetString($temp); $fs->GetInt($this->dodging);		
		$fs->GetString($temp); $fs->GetInt($this->strikeDamage);		
		$fs->GetString($temp); $fs->GetInt($this->damageAbsorb);		
		$fs->GetString($temp); $fs->GetInt($this->experience);		
		$fs->GetString($temp); $fs->GetInt($this->weapon);		
		$fs->GetString($temp); $fs->GetInt($this->moneyMin);		
		$fs->GetString($temp); $fs->GetInt($this->moneyMax);	
		
		
		$first = 0;
		$second = 0;
		while($fs->GetString2($temp) != "[ENDLOOT]")
		{
			$fs->GetInt($first);
			$fs->GetInt($second);
			$pair = array("first"=>$first,"second"=>$second);
			array_push($this->loot, $pair);
		}
	}
};//end trait tEnemyTemplate


//敌人类
class Enemy extends Entity
{
	use tEnemy;
	
	protected $template = null;		//敌人模板，不变的属性放在该实例中
	protected $hitpoints = 0;		//当前生命值
	protected $room = null;			//所在房间
	protected $nextAttacktime = 0;	//下一次攻击的时间
	
	
	//普通访问器
	public function HitPoints(){ return $this->hitpoints; }
	public function AddHitPoints($arg){ $this->hitpoints += $arg; }
	public function CurrentRoom(){ return $this->room; }
	public function SetCurrentRoom($room){ $this->room = $room; }
	public function NextAttacktime(){ return $this->nextAttacktime; }
	
	//设定模板
	public function LoadTemplate(EnemyTemplate $template)
	{
		$this->template = $template;
		$this->hitpoints = $template->hitpoints;
	}
	
	//代理访问器
	public function Name()			{ return $this->template->Name(); }
	public function Accuracy()		{ return $this->template->accuracy; }
	public function Dodging()		{ return $this->template->dodging; }
	public function StrikeDamage()	{ return $this->template->strikeDamage; }
	public function DamageAbsorb()	{ return $this->template->damageAbsorb; }
	public function Experience()	{ return $this->template->experience; }
	public function Weapon()		{ return $this->template->weapon; }
	public function MoneyMin()		{ return $this->template->moneyMin; }
	public function MoneyMax()		{ return $this->template->moneyMax; }
	public function LootList()			{ return $this->template->loot; }
};//end class Enemy


trait tEnemy
{
	public function Load(\BasicLib\IStream $fs)
	{
		$temp = '';
		
		$fs->GetString($temp); $fs->GetInt($temp);
		$this->template = EnemyTemplateDatabase::get($temp);
		$fs->GetString($temp); $fs->GetInt($this->hitpoints);
		$fs->GetString($temp); $fs->GetInt($temp);
		$this->room = RoomDatabase::FindFromID($temp);
		$fs->GetString($temp); $fs->GetInt($this->nextAttacktime);
	}
	
	public function ToString()
	{
		$templateId = $this->template->ID();
		$roomId = $this->room->ID();
		
		$str = "[TEMPLATEID]     {$templateId}\n". 
			   "[HITPOINTS]      {$this->hitpoints}\n". 
			   "[ROOM]           {$roomId}\n". 
			   "[NEXTATTACKTIME] {$this->nextAttacktime}\n";
		
		return $str;
	}	
};//end trait tEnemy


}//end namespace
