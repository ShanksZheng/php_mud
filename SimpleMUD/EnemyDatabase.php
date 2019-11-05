<?php
namespace SimpleMUD
{
require_once("EntityDatabase.php");
require_once("Enemy.php");
require_once(ROOT_PATH."/BasicLib/FileStream.php");


//敌人模板数据库
class EnemyTemplateDatabase extends EntityDatabase
{
	protected static $map = array();
	
	
	public static function Load()
	{
		global $USERLOG;
		$template = null;
		$fs = new \BasicLib\IStream(ROOT_PATH."/enemies/enemies.templates");
		while( $fs->Good() )
		{
			$template = new EnemyTemplate();
			$template->Load($fs);
			self::$map[$template->ID()] = $template;
			$USERLOG->Log( "Loaded Enemy: ".self::$map[$template->ID()]->Name() );
		}
	}
};//end class EnemyTemplateDatabase


//敌人数据库
class EnemyDatabase extends EntityDatabase
{
	protected static $map = array();
	
	
	public static function Create(EnemyTemplate $template, Room $room)
	{
		$id = self::FindOpenID();
		$enemy = new Enemy(); 
		$enemy->LoadTemplate($template);
		$enemy->SetCurrentRoom($room);
		self::$map[$id] = $enemy;
		
		$room->AddEnemy($enemy);
	}
	
	public static function Delete(Enemy $enemy)
	{
		$enemy->CurrentRoom()->RemoveEnemy($enemy);
		unset(self::$map[$enemy->ID()]);
	}
	
	public static function Load()
	{
		$temp = '';
		$entity = null;
		$fs = new \BasicLib\Istream(ROOT_PATH."/enemies/enemies.instances");
		while( $fs->Good() )
		{
			$entity = new Entity();
			$entity->Load($fs);
			self::$map[$entity->ID()] = $entity;
			$map[$entity->ID()]->CurrentRoom()->AddEnemy();
		}
	}
	
	public static function Save()
	{
		$file = new \BasicLib\File(ROOT_PATH."/enemies/enemies.instances", "w");
		foreach(self::$map as $v)
		{
			$file->Write($v->ToString()."\n");
		}
	}
};//end class EnemyDatabase


//EnemyTemplateDatabase::Load();
}//end namespace
