<?php
namespace SimpleMUDLog
{
require_once(ROOT_PATH."/BasicLib/Logger.php");

$USERLOG = new \BasicLib\Logger(ROOT_PATH."/logs/user.log", "userlog", "\BasicLib\TextDecorator");
$ERRORLOG = new \BasicLib\Logger(ROOT_PATH."/logs/error.log", "errorlog", "\BasicLib\TextDecorator");

/*
class A
{
	public static function print()
	{
		global $USERLOG;
		$USERLOG->Log("class A");
	} 
}
*/

}//end namespace
