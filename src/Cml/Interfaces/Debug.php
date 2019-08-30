<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Debug 抽像接口
 * *********************************************************** */

namespace Cml\Interfaces;

/**
 * Debug 抽像接口
 *
 * @package Cml\Interfaces
 */
interface Debug
{
	/**
	 * 程序執行完畢,打印CmlPHP運行信息
	 * 相關信息通過 \Cml\Debug獲取
	 * \Cml\Debug::getIncludeLib();獲取框架載入的類文件
	 * \Cml\Debug::getIncludeFiles();獲取框架載入的模板文件緩存文件等
	 * \Cml\Debug::getTipInfo();獲取框架提示信息
	 * \Cml\Debug::getSqls();獲取orm執行的sql語句
	 * \Cml\Debug::getUseTime();獲取程序運行耗時
	 * \Cml\Debug::getUseMemory();獲取程序運行耗費的內存
	 *
	 */
	public function stopAndShowDebugInfo();
}
