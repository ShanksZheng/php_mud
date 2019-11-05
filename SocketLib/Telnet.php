<?php
namespace SocketLib
{
	
//定义缓冲区大小
define("BUFFERSIZE", 1024);

define("reset", "\x1B[0m");
define("bold", "\x1B[1m");
define("dim", "\x1B[2m");
define("under", "\x1B[4m");
define("reverse", "\x1B[7m");
define("hide", "\x1B[8m");

define("clearscreen", "\x1B[2J");
define("clearline", "\x1B[2K");

define("black", "\x1B[30m");
define("red", "\x1B[31m");
define("green", "\x1B[32m");
define("yellow", "\x1B[33m");
define("blue", "\x1B[34m");
define("magenta", "\x1B[35m");
define("cyan", "\x1B[36m");
define("white", "\x1B[37m");

define("bblack", "\x1B[40m");
define("bred", "\x1B[41m");
define("bgreen", "\x1B[42m");
define("byellow", "\x1B[43m");
define("bblue", "\x1B[44m");
define("bmagenta", "\x1B[45m");
define("bcyan", "\x1B[46m");
define("bwhite", "\x1B[47m");

define("newline", "\r\n\x1B[0m");

	
//telnet协议类
class Telnet
{
	protected $buffer = '';
	protected $bufferSize = 0;
	
	
	//将原始字节数句转换为telnet数据，并将其发送到连接的当前协议处理程序
	//注意！这里有个bug需要解决:如果缓冲区堆满还不是一个完整命令时，这个方法会什么都不做
	public function Translate(Connection $conn, string $buffer, int $size)
	{
		for($i=0; $i<$size; $i++)
		{
			//如果字符是字母(或汉字字节)且缓冲区未满，则添加到缓冲区
			$c = $buffer[$i];
			if( ord($c) >= 32  &&  ord($c) != 127  && $this->bufferSize < BUFFERSIZE )
			{
				$this->buffer .= $c;
				$this->bufferSize++;
			}
			//否则检查是否是退格键
			else if( ord($c) == 8 && $this->bufferSize > 0)
			{
				$this->buffer = substr( $this->buffer, 0, strlen($this->buffer)-1 );
				$this->bufferSize--;
			}
			//否则，检查它是否是换行符，这意味着该命令是完整的。
			else if($c == "\n" || $c == "\r")
			{
				//如果缓冲区大小大于0,将缓冲区发送到当前连接的处理程序,然后重置缓冲区
				$handler = $conn->Handler();
				if( $this->bufferSize > 0 && $handler != null )
				{
					$handler->Handle($this->buffer);
				}
				$this->bufferSize = 0;
				$this->buffer = '';
			}
		}
	}
	
	//将一个纯文本字符串放入连接的发送缓冲区
	public function SendString(Connection $conn, string $string)
	{
		$conn->BufferData($string);
	}
	
	//获取bufferSize
	public function Buffered()
	{
		return $this->bufferSize;
	}
};	


}//end namespace
