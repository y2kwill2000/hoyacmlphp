<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 異常/錯誤接口
 * *********************************************************** */

namespace Cml\Interfaces;

/**
 * 系統錯誤及異常捕獲驅動抽像接口
 *
 * @package Cml\Interfaces
 */
interface ErrorOrException
{
	/**
	 * 致命錯誤捕獲
	 *
	 * @param array $error 錯誤信息
	 */
	public function fatalError(&$error);

	/**
	 * 自定義異常處理
	 *
	 * @param mixed $e 異常對像
	 */
	public function appException(&$e);
}
