<?php
define( "ROOT_PATH", dirname(__FILE__) );

require_once("SocketLib/ConnectionManager.php");
require_once("SocketLib/ListenManager.php");
require_once("SocketLib/Telnet.php");
require_once("SimpleMUD/Logon.php");
require_once("SimpleMUD/GameLoop.php");
require_once("SimpleMUD/Game.php");

use \SimpleMUD\GameLoop;
use \SimpleMUD\Game;
use \SocketLib\ConnectionManager;
use \SocketLib\ListenManager;
use \SocketLib\SocketException;

try
{
	$gameLoop = new GameLoop();

	$cm = new ConnectionManager("\SocketLib\Telnet", "\SimpleMUD\Logon");
	$lm = new ListenManager();
	$lm->SetConnectionManager($cm);
	$lm->AddPort(9000);
	
	while( Game::Running() )
	{
		$gameLoop->Loop();
		$lm->Listen();
		$cm->Manage();	
		usleep(1000);		
	}
}
catch(SocketException $e)
{
	
}


