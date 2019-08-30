<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-11-15 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 環境解析實現接口
 * *********************************************************** */

namespace Cml\Interfaces;

/**
 * 環境解析實現接口
 *
 * @package Cml\Interfaces
 */
interface Environment
{
	/**
	 * 獲取當前環境名稱
	 *
	 * @return string
	 */
	public function getEnv();
}
