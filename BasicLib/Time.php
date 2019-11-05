<?php
namespace BasicLib
{


//获取当前时间毫秒
function GetTimeMS()
{
	return (int)(microtime(true) * 1000);
}

//获取当前时间秒
function GetTimeS()
{
	return time();
}

//获取当前时间分
function GetTimeM()
{
	return (int)(time() / 60);
}

//获取当前时间小时
function GetTimeH()
{
	return (int)(time() / 3600);
}

//秒转换为毫秒
function seconds(int $t) { return $t*1000; }
//分钟转换为毫秒
function minutes(int $t) { return $t*60*1000; }
//小时转换为毫秒
function hours(int $t) 	 { return $t*60*60*1000; }
//天数转换为毫秒
function days(int $t) 	 { return $t*24**60*60*1000; }
//月数转换为毫秒
function weeks(int $t)   { return $t*7*24*60*60*1000; }
//年数转换为毫秒
function years(int $t)   { return $t*365*24*60*60*1000; }


//定时器类
class Timer
{
	//计时器的初始化时间
	protected $init_time = 0;
	//计时器的开始时间
	protected $start_time = 0;

	
	//启动或重置定时器
	public function Reset(int $timepassed = 0)
	{
		$this->start_time = $timepassed;
		$this->init_time = GetTimeMS();
	}
	
	//获取当前时间(毫秒)
	public function GetMS()
	{
		//返回自定时器初始化后经过的时间量，以及定时器给定的开始时间。
		return ( GetTimeMS() - $this->init_time ) + $this->start_time;
	}
	
	//获取当前时间(秒)
	public function GetS()
	{
		return $this->GetMS() / 1000;
	}
	
	//获取当前时间(分)
	public function GetM()
	{
		return $this->GetMS() / 6000;
	}
	
	//获取当前时间(小时)
	public function GetH()
	{
		return $this->GetMS() / 3600000;
	}
	
	//获取当前时间(天)
	public function GetD()
	{
		return $this->GetMS() / 86400000;
	}
	
	//获取当前时间(年)
	public function GetY()
	{
		return $this->GetD() / 365;
	}
	
	//获取当前时间的字符串
	public function GetString()
	{
		$y = $this->GetY();
		$d = $this->GetD() % 365;
		$h = $this->GetH() % 24;
		$m = $this->GetM() % 60;
		
		return "{$y} years, {$d} days, {$h} hours, {$m} minutes";
	}
};
	

}//end namespace

