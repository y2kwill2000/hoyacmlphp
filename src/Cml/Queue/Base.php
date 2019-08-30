<?php namespace Cml\Queue;

/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-02-04 下午20:11
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 隊列基類
 * *********************************************************** */

use Cml\Interfaces\Queue;

/**
 * 隊列基類
 *
 * @package Cml\Queue
 */
abstract class Base implements Queue
{
	/**
	 * 序列化數據
	 *
	 * @param mixed $data
	 *
	 * @return string
	 */
	protected function encodeDate($data)
	{
		return json_encode($data, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 反序列化數據
	 *
	 * @param mixed $data
	 *
	 * @return string
	 */
	protected function decodeDate($data)
	{
		return json_decode($data, true);
	}
}
