<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 隊列實現類
 * *********************************************************** */

namespace Cml\Vendor;

/**
 * 隊列實現類
 *
 * @package Cml\Vendor
 */
class Quenue
{

	private static $queue = []; //存放隊列數據

	/**
	 * 隊列-設置值
	 *
	 * @param mixed $val 要入隊的值
	 *
	 * @return bool
	 */
	public function set($val)
	{
		array_unshift(self::$queue, $val);
		return true;
	}

	/**
	 * 隊列-從隊列中獲取一個最早放進隊列的值
	 *
	 * @return string
	 */
	public function get()
	{
		return array_pop(self::$queue);
	}

	/**
	 * 隊列-隊列中總共有多少值
	 *
	 * @return string
	 */
	public function count()
	{
		return count(self::$queue);
	}

	/**
	 * 隊列-清空隊列數據
	 *
	 * @return string
	 */
	public function clear()
	{
		return self::$queue = [];
	}
}
