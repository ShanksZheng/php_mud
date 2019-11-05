<?php
namespace SimpleMUD
{
require_once(ROOT_PATH."/SocketLib/Telnet.php");
require_once(ROOT_PATH."/SocketLib/ConnectionHandler.php");
require_once(ROOT_PATH."/SocketLib/Connection.php");
require_once(ROOT_PATH."/SocketLib/function.php");
require_once(ROOT_PATH."/SocketLib/SocketException.php");
require_once("Attributes.php");
require_once("Player.php");
require_once("PlayerDatabase.php");
require_once("SimpleMUDLog.php");
require_once("Game.php");


use \SocketLib\Connection;
use \SocketLib\ConnectionHandler;
use \SocketLib\SocketException;

//登陆处理器
class Logon extends ConnectionHandler
{
	const NEWCONNECTION = 0;
	const NEWUSER = 1;
	const ENTERNEWPASS = 2;
	const ENTERPASS = 3;
	
	protected $state = 0;		//登陆状态
	protected $errors = 0;		//无效的回答已经输入了多少次
	protected $name = '';		//名称
	protected $pass = '';		//密码
	
	function __construct(Connection $conn)
	{
		parent::__construct($conn);
	}
	
	//处理命令
	function Handle(string $data)
	{
		//无效回答5次，关闭连接
		if($this->errors == 5)
		{
			$this->conn->Protocol()->SendString($this->conn, 
				red.bold."Too many incorrect responses, closing connection...".
				newline);
				
			$this->connection->Close();
			return;
		}
		
		//尚未输入名称
		if($this->state == self::NEWCONNECTION)
		{
			if( ucfirst($data) == "New" )
			{
				$this->state = self::NEWUSER;
				$this->conn->Protocol()->SendString($this->conn,
					yellow."Please enter your desired name: ".reset);
			}
			else
			{
				$p = PlayerDatabase::findfull($data);
				if($p == null)
				{
					$this->errors++;
					$this->conn->Protocol()->SendString($this->conn,
						red.bold."Sorry the user \"".white.$data.red.
						"\" does not exist.\r\n".
						"Please enter your name, or\"new\" if you are new: ".
						reset);
				}
				else
				{
					$this->state = self::ENTERPASS;
					$this->name = $data;
					$this->pass = $p->Password();
					
					$this->conn->Protocol()->SendString($this->conn,
						green.bold."Welcome, ".white.$data.red.newline.
						green."Please enter your password: ".reset);
				}
			}
			return;
		}
		
		//新用户
		if($this->state == self::NEWUSER)
		{
			if( PlayerDatabase::hasfull($data) )
			{
				$this->errors++;
				$this->conn->Protocol()->SendString($this->conn,
					red.bold."Sorry, the name \"".white.$data.red.
					"\" has already been taken.".newline.yellow.
					"Please enter your desired name: ".reset);
			}
			else
			{
				if( !self::AcceptibleName($data) )
				{
					$this->conn->Protocol()->SendString($this->conn,
						red.bold."Sorry, the name \"".white.$data.red.
						"\" has already been taken.".newline.yellow.
						"Please enter your desired name: ".reset);
				}
				else
				{
					$this->state = self::ENTERNEWPASS;
					$this->name = $data;
					$this->conn->Protocol()->SendString($this->conn,
						green."Please enter your desired password: ".reset);
				}
			}
			
			return;
		}
		
		//输入新密码
		if($this->state == self::ENTERNEWPASS)
		{
			$data = trim($data);	//过滤掉空白符号
			$this->conn->Protocol()->SendString($this->conn,
				green."Thank you! You are now entering the realm...".
				newline);
			
			$p = new Player();
			$p->SetName($this->name);
			$p->SetPassword($data);
			
			//如果玩家是第一个注册的，就让他成为管理员
			if( PlayerDatabase::size() == 0 )
			{
				$p->SetRank(ADMIN);
				$p->SetID(1);
			}
			else
			{
				$p->SetID( PlayerDatabase::LastID() + 1 );
			}
			
			//添加用户到数据库
			PlayerDatabase::AddPlayer($p);
			//作为新手进入游戏
			$this->GotoGame(true);
			
			return;
		}
		
		//输入密码
		if($this->state == self::ENTERPASS)
		{
			if($this->pass == $data)
			{
				$this->conn->Protocol()->SendString($this->conn,
					green."Thank you! You are now entering the realm..."
					.newline);
				//进入游戏
				$this->GotoGame();
			}
			else
			{
				$this->errors++;
				$this->conn->Protocol()->SendString($this->conn,
					red.bold."INVALID PASSWORD!".newline.yellow.
					"Please enter your password: ".reset);
			}
			
			return;
		}
	}
	
	//连接进入
	function Enter()
	{
		global $USERLOG;
		$USERLOG->Log( 
			$this->conn->GetRemoteIPAddress().
			" - entered login state.");
		
		$this->conn->Protocol()->SendString($this->conn, 
			red.bold."Welcome To SimpleMUD v1.0\r\n".
			"Please enter your name, or \"new\" if you are new: ".reset);
	}
	
	//连接离开
	function Leave(){}
	
	//连接断开处理
	function Hungup()
	{
		global $USERLOG;
		$USERLOG->Log(
			$this->conn->GetRemoteIPAddress().
			" - hung up in login state.");
	}
	
	//连接泛洪处理
	function Flooded()
	{
		global $USERLOG;
		$USERLOG->Log(
			GetIPString( $this->conn->GetRemoteIPAddress() ).
			" - flooded in login state.");		
	}
	
	//进入游戏
	function GotoGame(bool $newbie = false)
	{
		$p = PlayerDatabase::findfull($this->name);
		
		//如果用户已连接，则踢掉该用户
		if( $p && $p->LoggedIn() )
		{
			$p->Conn()->Close();
			$p->Conn()->Handler()->Hungup();
			$p->Conn()->ClearHandlers();
		}
		
		$p->SetNewbie($newbie);
		
		//记录用户的新连接
		$p->SetConn($this->conn);
		//删除处理器自身，当此处理器被删除时，它连同处理器中的任何成员变量
		//一同在内存上删除。所以删除后不可访问成员变量，否则程序会崩溃
		$p->Conn()->RemoveHandler();
		$p->Conn()->AddHandler( new Game( $p->Conn(), $p ) );
	}
	
	//连接达到上限时的处理
	static function NoRoom(Connection $connection)
	{
		static $msg = "Sorry, there is no more room on this server.\r\n";
		//防止异常导致程序退出
		try
		{
			$connection->Send( $msg, strlen($msg) );
		}
		catch(SocketException $e)
		{
			//这里什么都不做
			//如果发送数据引起异常，则很可能是一个漏洞利用
		}
	}
	
	//检查name是否合法
	static function AcceptibleName(string $name)
	{
		static $inv = "\"'~!@#$%^&*+/\\[]{}()=.,?;:";
		//不能包含特殊字符
		for($i=0; $i<strlen($inv); $i++)
		{
			if( strpos($name, $inv[$i]) !== false )
				return false;
		}
		
		//必须小于17个字符且大于2个字符
		if( strlen($name) > 17 || strlen($name) < 2 )
			return false;
		
		if($name == "new")
			return false;
			
		return true;
	}
};//end Logon class


}//end namespace
