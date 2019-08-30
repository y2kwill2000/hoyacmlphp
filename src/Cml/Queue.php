<?php namespace Cml;
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-02-04 下午20:11
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 隊列調度中心
 * *********************************************************** */

/**
 * 隊列調度中心,封裝的隊列的操作
 *
 * @package Cml
 */
class Queue
{
	/**
	 * 獲取Queue
	 *
	 * @param mixed $useCache 如果該鎖服務使用的是cache，則這邊可傳配置文件中配置的cache的key
	 *
	 * @return \Cml\Queue\Base
	 */
	public static function getQueue($useCache = false)
	{
		return Cml::getContainer()->make('cml_queue', $useCache);
	}

	/**
	 * 訪問Cml::getContainer()->make('cml_queue')中其餘方法
	 *
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public static function __callStatic($name, $arguments)
	{
		return call_user_func_array([Cml::getContainer()->make('cml_queue'), $name], $arguments);
	}
}
