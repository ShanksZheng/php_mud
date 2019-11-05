<?php
namespace SocketLib
{
//为了方便在程序中阅读，将这部错误码定义为常量。

//以下将socket错误码定义到常量
define("EWOULDBLOCK", 11);

//以下是自定义错误码
define("EALREADYCONN", 5001);	//socket已连接
define("ENOTCONN", 5002);		//socket未连接
define("ECONNCLOSE", 5003);		//连接已关闭
define("ELIMITREACHED", 5004);	//连接数已达到上限,由MAX常量限定连接数


class SocketException extends \Exception
{
	public function __construct($msg, $code=0)
	{
		parent::__construct($msg, $code);
	}
}



}//end namespace
