<?php

namespace SocketLib
{
require_once("SocketException.php");


//套接字基类
class Socket
{
	public $sock = false;
	protected $local_ip = '';
	protected $local_port = 0;
	protected $isblocking = true; //套接字默认为阻塞模式
	
	protected function __construct($sock = false)
	{
		if($sock)
		{
			$this->sock = $sock; 
			//获取套接字的本地ip和端口地址
			socket_getsockname($sock, $this->local_ip, $this->local_port);
		}
	}
	
	public function GetSock()
	{
		return $this->sock;
	}
	
	public function GetLocalPort()
	{
		return $this->local_port;
	}
	
	public function GetLocalIPAddress()
	{
		return $this->local_ip;
	}
	
	public function Close()
	{
		socket_close($this->sock);
		//使套接字无效
		$this->sock = false;
	}
	
	public function SetBlocking($blockmode)
	{
		$err = 0;
		//$blockmode为false是非阻塞模式，为true是非阻塞模式
		if($blockmode == false)
			$err = socket_set_nonblock($this->sock);
		else
			$err = socket_set_block($this->sock);
		
		if($err === false)
		{
			$ecode = socket_last_error($this->sock);
			socket_clear_error($this->sock);
			throw new SocketException( socket_strerror($ecode), $ecode );
		}
		
		$this->isblocking = $blockmode;
	}
};


//数据套接字类
class DataSocket extends Socket
{
	protected $connected = false;
	protected $remote_ip = '';
	protected $remote_port = 0;
	
	//服务端创建DataSocket对象时会传入$sock到该构造函数
	public function __construct($sock = false)
	{
		parent::__construct($sock);
		
		if($sock)
		{
			//获取套接字的远端ip和端口地址
			socket_getpeername($this->sock, $this->remote_ip,
				$this->remote_port);
			$this->connected = true;	
		}
	}
	
	public function GetRemotePort()
	{
		return $this->remote_port;
	}
		
	public function GetRemoteIPAddress()
	{
		return $this->remote_ip;
	}
	
	//确认套接字是否连接
	public function IsConnected()
	{
		return $this->connected;
	}
	
	//将此套接字连接到另一套接字
	//客户端使用DataSocket类时需要调用该方法
	public function Connect(string $ip_address, int $port)
	{
		$err = 0;
		
		//套接字已连接
		if($this->connected == true)
			throw new SocketException("already connected", EALREADYCONN);
		
		//创建套接字
		$this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if($this->sock === false)
		{
			$ecode = socket_last_error();
			socket_clear_error();
			throw new SocketException( socket_strerror($ecode), $ecode );
		}
		
		//连接它
		$err = socket_connect($this->sock, $ip_address, $port);
		if($err == false)
		{
			$ecode = socket_last_error();
			socket_clear_error();
			throw new SocketException( socket_strerror($ecode), $ecode );
		}
		
		$this->connected = true;
		
		//获取套接字的本地ip和端口地址
		socket_getsockname($this->sock, $this->local_ip, $this->local_port);
	}
	
	//发送数据
	public function Send(string $buffer, int $length)
	{
		$err = 0;
		
		//确保socket已连接
		if($this->connected == false)
			throw new SocketException("not connected", ENOTCONN);

		$err = socket_send( $this->sock, $buffer, $length, 0);
		if($err === false)
		{
			//如果错误码是EWOULDBLOCK(操作将阻塞)，
			//则忽略该错误。否则抛出异常
			$ecode = socket_last_error($this->sock);
			socket_clear_error($this->sock);

			if($ecode != EWOULDBLOCK)
				throw new SocketException( socket_strerror($ecode), $ecode );
			
			//如果套接字是非阻塞的，我们不想发送终端错误，所以只要将发送的
			//字节数设置为0,假设客户端能够处理该错误
			$err = 0;
		}
		
		return $err;
	}
	
	//接收数据
	//注意!socket_recv()完成时，$buffer原有的内容会被清空，并放入新的数据。
	//比如：
	//$buffer = "hello";
	//假如这时客户端发送内容"123",那么$buffer的内容为"123"，而不是"123lo"
	//socket_recv($sock, $buffer, 100, 0);
	//打印内容为"123"
	//echo $buffer; 
	public function Recv(string &$buffer, int $size)
	{
		$err = 0;
		//确保socket已连接
		if($this->connected == false)
			throw SocketException("not connected", ENOTCONN);
		
		//读取数据
		$err = socket_recv($this->sock, $buffer, $size, 0);
		//ECONNCLOSE是自定义错误，读取字节数为0时抛出
		if($err === 0)
			throw new SocketException("connect closed", ECONNCLOSE);	
					
		if($err === false)
		{
			$ecode = socket_last_error($this->sock);
			socket_clear_error($this->sock);
			throw new SocketException( socket_strerror($ecode), $error );		
		}

		return $err;
	}
	
	//关闭套接字
	public function Close()
	{
		//停止套接字收发数据
		if($this->connected == true)
			socket_shutdown($this->sock, 2);
		
		//关闭socket连接
		parent::Close();
		
		$this->connected = false;
	}
};


//监听套接字类
class ListenSocket extends Socket
{
	protected $listening = false;
	
	public function __construct()
	{
	}
	
	public function Listen(int $port)
	{
		$err = 0;
		$this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		//从os获取一个套接字描述符，如果没有的话
		if($this->sock === false)
		{
			$ecode = socket_last_error();
			socket_clear_error();
			throw new SocketException( socket_strerror($ecode), $ecode );
		}
		
		//在套接字上设置SO_REUSEADDR选项，以便它在关闭后不会占用端口
		$err = socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 1);
		if($err == false)
		{
			$ecode = socket_last_error($this->sock);
			socket_clear_error($this->sock);
			throw new SocketException( socket_strerror($ecode), $ecode );			
		}
		
		//绑定套接字
		$err = socket_bind($this->sock, "127.0.0.1", $port);
		if($err == false)
		{
			$ecode = socket_last_error($this->sock);
			socket_clear_error($this->sock);
			throw new SocketException( socket_strerror($ecode), $ecode );	
		}			

		//开始监听连接，将队列设置为8
		$err = socket_listen($this->sock, 8);
		if($err == false)
		{
			$ecode = socket_last_error($this->sock);
			socket_clear_error($this->sock);
			throw new SocketException( socket_strerror($ecode), $ecode );			
		}
		
		$this->listening = true;
	}
	
	public function Accept()
	{
		//尝试获取连接
		$datasock = false;
		
		$datasock = socket_accept($this->sock);
		if($datasock === false)
		{
			$ecode = socket_last_error();
			socket_clear_error();
			throw new SocketException( socket_strerror($ecode), $ecode );			
		}
		
		//返回新创建的套接字
		return new DataSocket($datasock);
	}
	
	public function IsListening()
	{
		return $this->listening;
	}
	
	public function Close()
	{
		//关闭套接字
		parent::Close();
		$this->listening = false;
	}
};	


}//end namespace
