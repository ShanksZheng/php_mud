<?php
namespace BasicLib
{
	
//文件类
//为了不手动关闭文件指针，封装了该类
class File
{
	protected $handle = null;
	
	function __construct($fileName, $mode)
	{
		$this->handle = fopen($fileName, $mode);
		if($this->handle === false)
			throw new Exception("open file fail: {$fileName}");
	}
	
	function __destruct()
	{
		fclose($this->handle);
	}
	
	function Getc()
	{
		return fgetc($this->handle);
	}
	
	function Gets()
	{
		return fgets($this->handle);
	}
	
	function Write(string $content)
	{
		return fwrite($this->handle, $content);
	}
	
	function Eof()
	{
		return feof($this->handle);
	}
};

	
//文件input流类
//为了方便提取文件中的内容，封装了该类
class IStream
{
	protected $file = null;
	
	function __construct($fileName)
	{
		//文件不存在则创建文件
		$file = fopen($fileName, "a");
		if($file === false)
			throw new \Exception("open file fail: {$fileName}");
		fclose($file);
		
		//打开文件
		$this->file = fopen($fileName, "r");
		if($this->file === false)
			throw new \Exception("open file fail: {$fileName}");
		
		//如果文件是空的，不希望$this->Good()返回true。
		//因为那样没有意义，所以吃掉了false符号
		$c = fread($this->file, 1);
		if($c !== false)
			fseek($this->file, -1, SEEK_CUR);
	}
	
	function __destruct()
	{
		fclose($this->file);
	}
	
	//从流中提取出一个整数
	function GetInt(&$val)
	{
		$tmpVal = '';
		$char = '';
		
		//吃掉所有空白字符
		while( !feof($this->file) )
		{
			$char = fgetc($this->file);
			if($char != " " && $char != "\n" && $char != "\t" && $char != "\r" && $char !== false)
			{
				//字符不是空白字符，指针位置退格并退出循环
				fseek($this->file, -1, SEEK_CUR);
				break;
			}
		}
		
		//获取数字字符串
		while( !feof($this->file) )
		{
			$char = fgetc($this->file);
			
			//字符是数字或-号，添加到临时字符串
			if( (ord($char) >= 48 && ord($char) <= 57) || $char == "-" )
			{
				$tmpVal .= $char;
			}
			//字符不是数字，指针位置退格并退出循环
			else
			{
				fseek($this->file, -1, SEEK_CUR);
				break;
			}
		}
		
		//吃掉后面的空白字符
		while( !feof($this->file) )
		{
			$char = fgetc($this->file);
			//字符不是空白字符，指针位置退格并退出循环
			if($char != " " && $char != "\n" && $char != "\t" && $char != "\r" && $char !== false)
			{
				fseek($this->file, -1, SEEK_CUR);
				break;
			}
		}

		$val = (int)$tmpVal;
	}
	
	//从流中提取出一个无空白符号字符串
	function GetString(&$val)
	{
		$tmpVal = '';
		$char = '';
		
		//吃掉前面的空白字符
		while( !feof($this->file) )
		{
			$char = fgetc($this->file);
			if($char != " " && $char != "\n" && $char != "\t" && $char != "\r" && $char !== false)
			{
				//字符不是空白字符，指针位置退格并退出循环
				fseek($this->file, -1, SEEK_CUR);
				break;
			}
		}
		
		//获取字符串
		while( !feof($this->file) )
		{
			$char = fgetc($this->file);

			//字符是空白符号，指针位置退格并退出循环
			if($char == " " || $char == "\n" || $char == "\t" || $char == "\r" || $char === false)
			{
				fseek($this->file, -1, SEEK_CUR);
				break;	
			}
			//添加到临时字符串
			else
			{
				$tmpVal .= $char;			
			}
		}
		
		//吃掉后面的空白字符
		while( !feof($this->file) )
		{
			$char = fgetc($this->file);
			//字符不是空白字符，指针位置退格并退出循环
			if($char != " " && $char != "\n" && $char != "\t" && $char != "\r" && $char !== false)
			{
				fseek($this->file, -1, SEEK_CUR);
				break;
			}
		}
				
		$val = $tmpVal;
	}
	
	//
	function GetInt2(&$val)
	{
		$this->GetInt($val);
		return $val;
	}
	
	//
	function GetString2(&$val)
	{
		$this->GetString($val);
		return $val;
	}
	
	//读取一行字符串
	function GetLine(&$val)
	{
		$tmpVal = fgets($this->file);
		if($tmpVal === false)
			$val = '';
		else
			$val = rtrim($tmpVal);
	}
	
	//文件是否可读
	function Good()
	{
		if(!feof($this->file))
			return true;
		else
			return false;
	}
};//end class




/*
$fs = new IStream("../items/items.itm");
$temp = '';
while( $fs->Good() )
{
	$fs->GetLine($temp);
	var_dump($temp);
}
* */


}//end namespace 
