<?php
namespace SocketLib
{
require_once("SocketSet.php");
require_once("Connection.php");


class ConnectionManager
{
	protected $connections = array();	//连接列表
	protected $maxDatatate = 0;			//最大接受数据速率，以每秒字节数为单位
	protected $sendTimeout = 0;			//允许发送超时多长时间(以秒为单位)
	protected $maxBuffered = 0;			//允许连接进行缓冲的最大字节数
	protected $set = null;				//为套接字活动进行轮询的对象
	
	protected $protocolClass = null;	//协议类的反射
	protected $dHandlerClass = null;	//默认处理器类的反射
	
	
	public function __construct( 
		string $protocolCName,
		string $dHandlerCName,
		int $maxDatatate=1024,
		int $sendTimeout=60,
		int $maxBuffered=8192
	)
	{
		$this->maxDatatate = $maxDatatate;
		$this->sendTimeout = $sendTimeout;
		$this->maxBuffered = $maxBuffered;
		$this->set = new SocketSet();

		$this->protocolClass = new \ReflectionClass($protocolCName);
		if( !$this->protocolClass->isInstantiable() )
			throw new Exception("类{$protocolCName}不存在");
			
		$this->dHandlerClass = new \ReflectionClass($dHandlerCName);
		if( !$this->dHandlerClass->isInstantiable() )
			throw new Exception("类{$protocolCName}不存在");		
	}
	
	//关闭所有连接
	public function __destruct()
	{
		if( count($this->connections) > 0)
			foreach($this->connections as $v)
			{
				$v->CloseSocket();
			}
	}
	
	//添加一个新的连接。由监听管理器调用
	public function NewConnection(DataSocket $socket)
	{
		$protocol = $this->protocolClass->newInstance();
		$conn = new Connection($socket, $protocol);
		//超过最大可连接数
		if( $this->AvailableConnections() == 0)
		{
			//告知用户
			$dHandlerClass::NoRoom($conn);
			//关闭连接
			$conn->CloseSocket();
		}
		else
		{
			//添加连接
			array_push($this->connections, $conn);
			//将连接转换为非阻塞模式
			$conn->setBlocking(false);
			//添加到set
			$this->set->AddSocket($conn);
			//为连接添加默认处理器
			$conn->AddHandler( $this->dHandlerClass->newInstance($conn) );
		}
	}
	
	//返回可以添加到管理器的连接数量
	public function AvailableConnections()
	{
		return SocketSet::MAX - count($this->connections);
	}
	
	//返回管理器内的连接数
	public function TotalConnections()
	{
		return count($this->connections);
	}
	
	//侦听传入的数据
	public function Listen()
	{
		if( $this->TotalConnections() == 0 )
			return;
		if( $this->set->Poll() <= 0)
			return;
			
		foreach($this->connections as $k => $v)
		{
			if( $this->set->HasActivity($v) )
			{
				try
				{
					//接收可能多的数据
					$v->receive();
					//检查连接是否泛红
					if( $v->GetDataRate() > $this->maxDatatate )
					{
						//客户发送了太多数据，告诉协议处理程序
						$v->Hander()->Flooded();
						//关闭连接
						$this->Close($v, $k);
					}
				}
				//捕获接受数据时引发的任何致命异常。抛出的唯一异常是主要错误，
				//而套接字应该立即关闭。因此，协议处理器被告知连接挂起，套接字关闭
				catch(Exception $e)
				{
					$v->Handler()->Hungup();
					$this->Close($v, $k);
				}
			}//结束活动检查
		}//结束连接遍历
	}
	
	//遍历所有连接并发送所有缓冲数据
	public function Send()
	{
		foreach($this->connections as $k => $v)
		{
			try
			{
				$v->SendBuffer();
				
				if( $v->GetBufferLength() > $this->maxBuffered || 
				$v->GetLastSendTime() > $this->sendTimeout )
				{
					$v->Handler()->Hungup();
					$this->Close($v, $k);
				}
			}
			//检查是否存在发送问题，这些问题通常由漏洞或客户端崩溃引起的
			catch(Exception $e)
			{
				$v->Handler()->Hungup();
				$this->Close($v, $k);
			}
		}	
	}
	
	//遍历关闭列表中软关闭的连接并从列表中删除
	public function CloseConnections()
	{
		foreach($this->connections as  $k => $v)
		{
			if( $v->Closed() )
				$this->Close($v, $k);
		}
	}
	
	//此函数执行Manager类的三个动作，即接收发送和关闭
	public function Manage()
	{
		$this->Listen();
		$this->Send();
		$this->CloseConnections();
	}
	
	//关闭连接并删除套接字
	protected function Close($conn, $index)
	{
		$this->set->RemoveSocket($conn);
		$conn->CloseSocket();
		unset($this->connections[$index]);
		//防止数组键不停增长，重新排列数组键
		//这个方法相当耗时，所以使用了随机数
		if( mt_rand(1,30) == 1)
			array_values($this->connections);
	}
};


}// end namespace
