<?php
namespace SocketLib
{
require_once("Socket.php");


class SocketSet
{
	const MAX = 1024;					//允许的最大连接数
	protected $set = array();			//套接字描述符集合
	protected $read_set = array();		//有可读事件套接字描述符集合
	protected $write_set = array();		//暂时用不到
	protected $except_set = array();	//暂时用不到

	//添加到套接字描述符集
	public function AddSocket(Socket $sock)
	{
		array_push( $this->set, $sock->GetSock() );
	}
	
	//从套接字描述符集删除
	public function RemoveSocket(Socket $sock)
	{
		$key = array_search( $sock->GetSock(), $this->set );
		
		unset($this->set[$key]);
		//防止数组键不停增长，重新排列数组键
		//这个方法相当耗时，所以使用了随机数
		if( mt_rand(1,30) == 1)
			array_values($this->set);		
	}
	
	//等待活动time毫秒
	public function Poll(int $time=0)
	{
		if( count($this->set) == 0 )
			return 0;
		
		//需要复制到read_set，这样原来的集合就不会被selete()修改
		$this->read_set = $this->set;
		
		//参数5为等待多少微秒，因此将$time乘以1000。$time为0意味着不阻塞
		return socket_select($this->read_set, $this->write_set, $this->except_set, 0, $time*1000);
	}
	
	//检查是否有可读活动
	public function HasActivity(Socket $sock)
	{
		return in_array( $sock->GetSock(), $this->read_set ); 
	}
}

	
}//end namespace
