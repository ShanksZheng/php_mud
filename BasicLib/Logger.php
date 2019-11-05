<?php
namespace BasicLib
{

//装饰器接口
interface Decorator
{
	public static function FileHeader(string $title);
	public static function SessionOpen();
	public static function SessionClose();
	public static function Decorate(string $string);
};

//文本装饰器
class TextDecorator implements Decorator
{
	public static function FileHeader(string $title)
	{
		return "==========================================\n".
			$title."\n".
			"==========================================\n\n";
	}
	
	public static function SessionOpen()
	{
		return "\n";
	}
	
	public static function SessionClose()
	{
		return "\n";
	}
	
	public static function Decorate(string $string)
	{
		return $string."\n";
	}
};

//日志类
class Logger
{
	protected $logfile = null;
	protected $decCName = '';
	
	
	function __construct(
		string $fileName, 
		string $logTitle, 
		string $decCName)
	{
		$decClass = new \ReflectionClass($decCName);
		if( !$decClass->isInstantiable() )
			throw new \Exception("类{$decCName}不存在");
		$this->decCName = $decCName;
					
		//测试文件是否存在
		$file = @fopen($fileName, "r");
		//文件不存在
		if($file === false)
		{
			//将装饰头写入文件
			$this->logfile = fopen($fileName, "a+");
			fwrite( $this->logfile, $this->decCName::FileHeader($logTitle) );
		}
		//文件存在
		else
		{
			fclose($file);				
			$this->logfile = fopen($fileName, "a+");		
		}
		
		$this->Log("Session opened.");	
		//fwrite( $this->logfile, $this->decCName::SessionOpen() );		
	}
	
	
	function __destruct()
	{	
		$this->Log("Session closed.");	
		//fwrite( $this->logfile, $this->decCName::SessionClose() );
		fclose($this->logfile);
	}
	
	
	function Log(string $entry)
	{
		$msg = '';
		$msg .= "[".date("Y-m-d")."]";
		$msg .= "[".date("h:i:s")."]";
		
		$msg .= $entry;
		fwrite( $this->logfile, $this->decCName::Decorate($msg) );
	}
};//end class Logger


//$loger = new Logger("user.txt", "title", __NAMESPACE__."\TextDecorator");
//$loger->Log("hello");
}//end namespace
