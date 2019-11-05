<?php
namespace SocketLib
{
require_once("SocketSysException.php");


//根据点分字符串ip或域名获得整型ip
function GetIPAddress(string $address)
{
	//如果是ip地址
	if( IsIPAddress($address) )
	{
		$addr = ip2long($address);
		if($addr === false)
			throw new SocketSysException("INET_ADDR_NONE", INET_ADDR_NONE);
		return $addr;
	}
	//如果是域名
	else
	{
		$addr = gethostbyname($address);
		if($addr == $address)
			throw new SocketSysException("HOST_NAME_NOT_FOUND", HOST_NAME_NOT_FOUND);	
		return ip2long($addr);
	}
}

//根据整型ip获得点分字符串ip
function GetIPString(int $address)
{
	$str = long2ip($address);
	if($str === false)
		return "Invalid IP Address";
	
	return $str;
}

//字符串是否是ip地址
function IsIPAddress(string $address)
{
	//如果字符串中的字符不在数字0～9区间，也不是'.'，则认为这个字符串不是ip字符串
	for($i=0; $i<strlen($address); $i++)
	{
		if( ($address[$i] < '0' || $address[$i] > '9') && $address[$i] != '.' )
			return false;
	}
	return true;
}


}//end namespace
