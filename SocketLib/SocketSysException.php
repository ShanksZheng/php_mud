<?php
namespace SocketLib
{

define("INET_ADDR_NONE", 1);
define("HOST_NAME_NOT_FOUND", 2);

//用于socket杂项函数的异常类
class SocketSysException extends \Exception
{
	public function __construct($msg, $code=0)
	{
		parent::__construct($msg, $code);
	}
}



}//end namespace
