<?php
namespace SocketLib
{
require_once("Socket.php");
require_once(dirname(__FILE__)."/../BasicLib/Time.php");

//连接类
class Connection extends DataSocket
{
	//默认缓冲区大小
	const BUFFERSIZE = 1024;
	//每16秒接收的数据量
	const TIMECHUNK = 16;
	
	protected $protocol = null;				//协议处理器
	protected $handlerStack = null;			//命令处理器栈
	protected $sbuffer = '';				//发送缓冲区
	protected $checkSendTime = false;		//数据发送失败的标识，发送数据失败时会被设置为true
	protected $buffer = '';					//接收缓冲区
	protected $datarate = 0;				//当前时间块接收到的数据量
	protected $lastDatarate = 0;			//上一个时间块接收到的数据量
	protected $lastReceiveTime = 0;			//上一次数据接收成功的时间
	protected $lastSendTime = 0;			//上一次数据发送成功的时间
	protected $creationTime = 0;			//连接创建的时间
	protected $closed = false;				//连接是否已关闭
	
	
	public function __construct($dataSocket, $protocol)
	{
		//这里偷个懒，没有写copy方法，重新构造一次父类
		parent::__construct( $dataSocket->GetSock() );
		
		$this->handlerStack = new \SplStack();
		$this->protocol = $protocol;
		$this->creationTime = \BasicLib\GetTimeMS();
	}
	
	//上一次数据发送成功的时间
	public function GetLastSendTime()
	{
		if($this->checkSendTime)
		{
			return \BasicLib\GetTimeS() - $this->lastSendTime;
		}
		return 0;
	}
	
	//上一次数据接收成功的时间
	public function GetLastReceiveTime()
	{
		return $this->lastReceiveTime;
	}
	
	//“关闭”连接。这只是设置一个布尔值来告诉连接，当连接管理器到达时它会被关闭
	public function Close()
	{
		$this->closed = true;
	}
	
	//物理上关闭套接字
	public function CloseSocket()
	{
		parent::Close();
		//顶层处理器(如果存在)连接进入Leave状态，并删除所有处理器
		$this->ClearHandlers();
	}
	
	//将数据放入发送缓冲区
	public function BufferData(string $buffer)
	{
		$this->sbuffer .=  $buffer;
	}
	
	//发送缓冲区的数据
	public function SendBuffer()
	{
		$length = strlen($this->sbuffer);
		if($length > 0)
		{
			//发送数据，并从缓冲区发送尽可能多的数据
			$sent = $this->Send($this->sbuffer, $length);
			//将已发送的数据从buffer中清除
			$this->sbuffer = substr($this->sbuffer, $sent);
			if($sent)
			{
				//数据发送成功，重置发送时间
				$this->lastSendTime = \BasicLib\GetTimeS();
				$this->checkSendTime = false;
			}
			//发送失败时，记录发送失败的时间
			else
			{
				//没有数据发送，所以checksendtime需要检查，看看套接字是否进入客户端死锁
				if(!$this->checkSendTime)
				{
					//如果执行到达这里，这意味着此连接以前发送数据没有任何问题，所以请
					//记下发送问题开始的时间。 请注意，它可能已经开始较早了，但由于我们
					//之前没有开始发送数据，所以确实无法确切知道它何时开始。
					$this->checkSendTime = true;
					$this->clastSendTime = \BasicLib\GetTimeS();
				}
			}//end send check
		}//end sbuffer length check
	}
	
	//接收数据
	//$buffer是接收到的数据，外部接收到该连接的数据时会调用这个方法
	public function Receive()
	{
		//获取尽可能多的数据
		$length = $this->Recv($this->buffer, self::BUFFERSIZE);
		//获取当前时间
		$t = \BasicLib\GetTimeMS();
		
		//检查是否到达了下一个X秒，如果是的话，清除数据速率
		if( ($this->lastReceiveTime/self::TIMECHUNK) != ($t/self::TIMECHUNK) )
		{
			$this->lastDatarate = $this->datarate / self::TIMECHUNK;
			$this->datarate = 0;
			$this->lastReceiveTime = $t;
		}
		
		$this->datarate += $length;
		
		//将数据送入命令翻译器
		$this->protocol->Translate($this, $this->buffer, $length);
	}
	
	//获得连接的接收数据速率，以每秒字节数为单位，在上一个时间间隔内计算
	public function GetDataRate()
	{
		return $this->lastDatarate;
	}
	
	//获取发送缓冲区的长度
	public function GetBufferLength()
	{
		return strlen($this->sbuffer);
	}
	
	//获取创建连接的时间
	public function GetCreationTime()
	{
		return $this->creationTime;
	}
	
	//获取协议处理器
	public function Protocol()
	{
		return $this->protocol;
	}
	
	//获得连接关闭状态
	public function Closed()
	{
		return $this->closed;
	}
	
	//添加命令处理器
	public function AddHandler($handler)
	{
		//旧的处理器进入Leave状态
		$thandler = $this->Handler();
		if($thandler)
			$thandler->Leave();
		//新处理器进入Enter状态
		$this->handlerStack->push($handler);
		$handler->Enter();
	}
	
	//移除命令处理器
	public function RemoveHandler()
	{
		//新处理器进入Leave状态
		$thandler = $this->Handler();
		if($thandler)
		{
			$thandler->Leave();						 //离开当前状态
			$this->handlerStack->pop();				 //删除新处理器		
		}
		
		//旧处理器进入Enter状态
		$thandler = $this->Handler();
		if($thandler)
			$thandler->Enter();			
	}
	
	//获取顶层命令处理器
	public function Handler()
	{
		if( $this->handlerStack->count() )
		{
			return $this->handlerStack->top();
		}
		return null;
	}
	
	//清除所有命令处理器
	public function ClearHandlers()
	{
		//当前处理器进入Leave状态
		$thandler = $this->Handler();
		if($thandler)
			$thandler->Leave();
		//清空处理器
		while( $this->handlerStack->count() )
		{
			$this->handlerStack->pop();
		}
	}
}; // end class Connection


}// end namespace


