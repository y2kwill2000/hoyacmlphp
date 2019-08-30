<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 隊列驅動抽像接口
 * *********************************************************** */

namespace Cml\Interfaces;

/**
 * 隊列驅動抽像接口
 *
 * @package Cml\Interfaces
 */
interface Queue
{
	/**
	 * 從列表頭入隊
	 *
	 * @param string $name 要從列表頭入隊的隊列的名稱
	 * @param mixed $data 要入隊的數據
	 *
	 * @return mixed
	 */
	public function lPush($name, $data);

	/**
	 * 從列表頭出隊
	 *
	 * @param string $name 要從列表頭出隊的隊列的名稱
	 *
	 * @return mixed
	 */
	public function lPop($name);

	/**
	 * 從列表尾入隊
	 *
	 * @param string $name 要從列表尾入隊的隊列的名稱
	 * @param mixed $data 要入隊的數據
	 *
	 * @return mixed
	 */
	public function rPush($name, $data);

	/**
	 * 從列表尾出隊
	 *
	 * @param string $name 要從列表尾出隊的隊列的名稱
	 *
	 * @return mixed
	 */
	public function rPop($name);
}
