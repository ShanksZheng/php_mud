<?php
namespace SocketLib
{
require_once("Socket.php");
require_once("SocketSet.php");
require_once("ConnectionManager.php");	


//监听套接字管理器
class ListenManager
{
	protected $sockets = array();	//用于监听的套接字列表
	protected $set = null;			//用于select()的对象
	protected $manager = null;		//连接管理器
	
	
	public function __construct()
	{
		$this->set = new SocketSet();
	}
	
	//析构函数关闭所有的监听套接字
	public function __destruct()
	{
		foreach($this->sockets as $v)
		{
			$v->Close();
		}
	}
	
	//为管理器添加一个端口(监听套接字)
	public function AddPort(int $port)
	{
		//只允许MAX个监听套接字，超过则抛出异常
		if( count($this->sockets) == SocketSet::MAX)
			throw new SocketException("socket limit reached", ELIMITREACHED);
			
		//创建监听套接字
		$lsock = new ListenSocket();
		//监听指定端口
		$lsock->Listen($port);
		//设置为不阻塞
		$lsock->SetBlocking(false);
		//添加到列表
		array_push($this->sockets, $lsock);
		//添加到SocketSet
		$this->set->AddSocket($lsock);
	}
	
	//设置监听管理器在接受新的套接字时使用的连接管理器
	public function SetConnectionManager(ConnectionManager $manager)
	{
		$this->manager = $manager;
	}
	
	//在监听套接字上监听任何新的连接
	public function Listen()
	{
		if( $this->set->Poll() <= 0 )
			return;

		//存在可accept()的监听套接字，遍历他们
		foreach($this->sockets as $v)
		{
			if( $this->set->HasActivity($v) )
			{
				try
				{
					$datasock = $v->accept();
					$this->manager->NewConnection($datasock);
				}
				//捕获异常，如果它不是EWOULDBLOCK,则重新抛出。这是因为可能存在连接攻击，
				//导致套接字检测到连接，但一旦连接到达accept调用，则无法检索该连接。因此，
				//如果连接被阻塞，则忽略它。但如果发生其他错误，则抛出它。
				catch(Exception $e)
				{
					if( $e.getcode() !=  EWOULDBLOCK )
					{
						throw $e;
					}
				}
			}// end activity check
		}// end socket loop
	}// end Listen()
};


}//end namespace
