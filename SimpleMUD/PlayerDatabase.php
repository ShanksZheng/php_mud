<?php
namespace SimpleMUD
{
require_once("EntityDatabase.php");
require_once("Player.php");
require_once(ROOT_PATH."/BasicLib/FileStream.php");
require_once("SimpleMUDLog.php");
use \BasicLib\File;


//玩家数据库
class PlayerDatabase extends EntityDatabase
{
	const FILENAME = ROOT_PATH."/players/players.txt";
	
	protected static $map = array();
	
	//加载所有玩家
	public static function Load()
	{
		$file = new File(self::FILENAME, "r");
		$name = '';
		while( !$file->Eof() )
		{
			$name = str_replace( "\n", "", $file->Gets() );  
			if($name)
				self::LoadPlayer($name);
		}
	}
	
	//保存所有玩家
	public static function Save()
	{
		$file = new File(self::FILENAME, "w");
		foreach(self::$map as $v)
		{
			$file->Write( $v->Name()."\n" );
			self::SavePlayer( $v->ID() );
		}
	}
	
	//添加玩家
	public static function AddPlayer(Player $player)
	{
		$id = $player->ID();
		$name = $player->Name();
		
		if( self::hasFromID($id) )
			return;
		if( self::hasfull($name) )
			return;
			
		self::$map[$id] = $player;
		
		$file = new File(self::FILENAME, "a");
		$file->Write($player->Name()."\n");
		
		self::SavePlayer($player);
	}
	
	//加载玩家
	//$name是玩家名称，玩家数据保存在单独的文件
	public static function LoadPlayer(string $name)
	{
		$name = self::PlayerFileName($name);
		$fs = new \BasicLib\IStream($name, "r");
		$p = new Player();
		$p->Load($fs);
		self::$map[$p->ID()] = $p;
		//$USERLOG->Log( "Loaded Player: ".$p->Name() );
	}
	
	//保存玩家
	//将玩家保存在单独的文件上
	public static function SavePlayer(Player $player)
	{
		$name = self::PlayerFileName( $player->Name() );
		$file = new File($name, "w");
		$file->Write( $player->ToString() );
	}
	
	//获取玩家数据文件名
	private static function PlayerFileName(string $name)
	{
		return ROOT_PATH."/players/".$name.".plr";
	}
	
	//获得数组最后一个元素的key
	public static function LastID()
	{
		//返回数组最后一个元素的key
		end(self::$map);
		return key(self::$map);
	}
	
	//查找指定用户是否是活动的
	public static function FindActive(string $name)
	{
		foreach(self::$map as $v)
		{
			//找到这个玩家，如果他是活动的则返回他
			if( $v->Matchfull($name) )
			{
				if( $v->Active() )
					return $v;
				else
					return null;
			}
		}
		
		return null;
	}
	
	//查找指定用户是否在线
	public static function FindLoggedin(string $name)
	{
		foreach(self::$map as $v)
		{
			//找到这个玩家，如果他已登陆则返回他
			if( $v->Matchfull($name) )
			{
				if( $v->LoggedIn() )
					return $v;
				else
					return null;
			}
		}
		
		return null;
	}
	
	//指定用户退出登陆
	public static function Logout(Player $p)
	{
		global $USERLOG;
		$USERLOG->Log(
			$p->Conn()->GetRemoteIPAddress().
			" - User ".$p->Name()." logged off."
		);
		
		$p->SetConn(null);
		$p->SetLoggedIn(false);
		$p->SetActive(false);
		
		self::SavePlayer($p);
	}
};//end class


//PlayerDatabase::Load();
//PlayerDatabase::Save();


}//end namespace
