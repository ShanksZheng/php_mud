<?php
//namespace BasicLib
{

//从数组中删除指定的元素
//将数组当列表、集合使用时。请用该方法删除元素而不是unset()
//因为unset()后再array_push()插入元素时，被删除元素的键并不会被重新使用，而是使用数组中最大的键+1。
//这样数组的键会不停增长，会造成隐患.
function array_unset(&$arr, $key)
{
	unset($arr[$key]);
	//防止数组键不停增长，重新排列数组键
	//这个方法相当耗时，所以使用了随机数
	if( mt_rand(1,30) == 1)
		array_values($arr);		
}


//获取数组下一个可用键(此方法时间消耗是上面的10倍)
/*
function array_next_key(array $arr)
{
	if( count($arr) == 0 )
		return 0;
	
	//数组没有元素被删除时，返回数组长度作为下一个键
	end($arr);
	if( key($arr) == count($arr)-1)
		return count($arr);
		
	$next_key = 0;
	foreach($arr as $k => $v)
	{
		if($next_key == $k)
			$next_key++;
		else
			break;
	}
	
	return $next_key;
}
* */


//获取第index个空格后的单词
function parse_word(string $str, $index)
{
	$spos = -1;
	//尽量将spos移动到第index个空格
	while($index > 0)
	{
		$spos = strpos($str, ' ', $spos+1);
		if($spos === false)
		{
			$spos = -1;
			break;
		}
		$index--;			
	}
	
	if($spos == -1)
		$spos = 0;
	
	$epos = strpos($str, ' ', $spos+1);
	if($epos === false)
		return trim( substr($str, $spos) );
	else
		return trim( substr($str, $spos, $epos-$spos) );
}


//删除字符串第index个空格后面的单词
function remove_word(string $str, $index)
{
	$spos = -1;
	//尽量将spos移动到第index个空格
	while($index > 0)
	{
		$spos = strpos($str, ' ', $spos+1);
		if($spos === false)
		{
			$spos = -1;
			break;
		}
		$index--;			
	}
	
	if($spos == -1)
		$spos = 0;	
		
	$epos = strpos($str, ' ', $spos+1);
	if($epos === false)
		$length = strlen($str)-$spos;
	else
		$length = $epos-$spos;

	return trim( substr_replace($str, "", $spos, $length) );
}

//echo parse_word("world1 world2 world3", 4);
}//end namespace
