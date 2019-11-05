<?php
namespace SocketLib
{
require_once("Connection.php");
	
//命令处理器基类
abstract class ConnectionHandler
{
	protected $conn = null;	
	
	public function __construct(Connection $conn)
	{
		$this->conn = $conn;
	}
	
	abstract public function Handle(string $data);
	
	abstract public function Enter();
	
	abstract public function Leave();
	
	abstract public function Hungup();
	
	abstract public function Flooded();
};
	
	
}//end namespace
