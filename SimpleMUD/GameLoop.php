<?php
namespace SimpleMUD
{
require_once(ROOT_PATH."/BasicLib/Time.php");	
require_once(ROOT_PATH."/BasicLib/FileStream.php");	
require_once("ItemDatabase.php");	
require_once("PlayerDatabase.php");	
require_once("RoomDatabase.php");	
require_once("StoreDatabase.php");	
require_once("EnemyDatabase.php");	
require_once("Game.php");


define( "DBSAVETIME", \BasicLib\minutes(15) );
define( "ROUNDTIME", \BasicLib\seconds(1) );
define( "REGENTIME", \BasicLib\minutes(1) );
define( "HEALTIME", \BasicLib\minutes(1) );	


//游戏循环类
class GameLoop
{
	protected $nextDbSave;		//多久保存一次数据库
	protected $nextRound;		//多久执行一次敌人战斗
	protected $nextRegen;		//多久执行一次敌人再生
	protected $nextHeal;		//多久执行一次玩家治疗
	
	
	function __construct()
	{
		$this->LoadDatabases();
	}
	
	function __destruct()
	{
		$this->SaveDatabases();
	}
	
	//加载游戏时间
	function Load()
	{
		$fs = new \BasicLib\IStream("game.data");
		
		if( $fs->good() )
		{
			$temp = '';
			$time;
			$fs->GetString($temp);
			$fs->GetInt($time);
			Game::GetTimer()->Reset();
			
			$file->GetString($temp);
			$file->GetInt($this->nextDbSave);
			$file->GetString($temp);
			$file->GetInt($nthis->extRound);
			$file->GetString($temp);
			$file->GetInt($this->nextRegen);
			$file->GetString($temp);
			$file->GetInt($this->nextHeal);
		}
		else
		{
			Game::GetTimer()->Reset();
			$this->nextDbSave = DBSAVETIME;
			$this->nextRound = ROUNDTIME;
			$this->nextRegen = REGENTIME;
			$this->nextHeal = HEALTIME;
		}
		
		Game::SetRunning(true);
	}
	
	//保存游戏时间
	function Save()
	{
		$file = new \BasicLib\File("game.data", "w");
		$str = "[GAMETIME]      {Game::GetTimer()->GetMS()}\n".
			   "[NEXTDBSAVE]    {$this->nextDbSave}\n".
			   "[NEXTROUND]		{$this->nextRound}\n".
			   "[NEXTREGEN]		{$this->nextRegen}\n".
			   "[NEXTHEAL]		{$this->nextHeal}\n";
		$file->Write($str);
	}
	
	//执行一次循环迭代
	function Loop()
	{
		//到了执行敌人战斗的时间
		if(Game::GetTimer()->GetMS() >= $this->nextRound)
		{
			$this->PerformRound();
			$this->nextRound += ROUNDTIME;
		}
		
		//到了敌人再生的时间
		if(Game::GetTimer()->GetMS() >= $this->nextRegen)
		{
			$this->PerformRegen();
			$this->nextRegen += REGENTIME;
		}
		
		//到了玩家再生的时间
		if(Game::GetTimer()->GetMS() >= $this->nextHeal)
		{
			$this->PerformHeal();
			$this->nextHeal += HEALTIME;
		}
		
		//到了保存数据库的时间
		if(Game::GetTimer()->GetMS() >= $this->nextDbSave)
		{
			$this->SaveDatabases();
			$this->nextDbSave += DBSAVETIME;
		}
	}
	
	//读取所有数据库
	function LoadDatabases()
	{
		$this->Load();
		ItemDatabase::Load();
		EnemyTemplateDatabase::Load();
		EnemyDatabase::Load();
		RoomDatabase::LoadTemplate();
		RoomDatabase::LoadData();	
		StoreDatabase::Load();	
		PlayerDatabase::Load();		
	}
	
	//保存所有数据库
	function SaveDatabases()
	{
		$this->Save();
		PlayerDatabase::Save();
		RoomDatabase::SaveData();
		EnemyDatabase::Save();
	}
	
	//敌人进行一次战斗
	function PerformRound()
	{
		$enemys = EntityDatabase::getall();
		$now = Game::GetTimer()->GetMS();
		//遍历所有敌人
		foreach($enemys as $v)
		{
			//如果敌人攻击时间冷却完成，并且所在房间有玩家。
			//则执行它的攻击方法
			if( $now >= $v->NextAttackTime() &&
				count( $v->CurrentRoom()->Players() ) 
			)
				Game::EnemyAttack($v);
		}
	}
	
	//再生敌人
	function PerformRegen()
	{
		$rooms = RoomDatabase::getall();
		//遍历所有敌人
		foreach($rooms as $v)
		{
			//如果敌人攻击时间冷却完成，并且所在房间有玩家。
			//则执行它的攻击方法
			if( $v->SpawnWhich() != null &&
				count( $v->Enemys() ) < $v->MaxEnemys()
			)
			{
				EnemyDatabase::Create($v->SpawnWhich(), $v);
				Game::SendRoom(red.bold.$v->SpawnWhich()->Name().
					" enters the room!", $v);	
			}
		}		
	}
	
	//玩家治疗
	function PerformHeal()
	{
		$players = PlayerDatabase::getall();
		//遍历所有玩家
		foreach($players as $v)
		{
			//如果玩家是活动的，则增加玩家的生命值。并打印当前状态给玩家
			if( $v->Active() )
			{
				$v->AddHitpoints( $v->GetAttr(HPREGEN) );
				$v->PrintStabar(true);
			}
		}
	}
};//end class GameLoop

}
