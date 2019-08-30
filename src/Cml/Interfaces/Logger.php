<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Logger 抽像接口 參考 https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * *********************************************************** */

namespace Cml\Interfaces;

/**
 * Logger 抽像接口
 *
 * @package Cml\Interfaces
 */
interface Logger
{
	/**
	 * 系統不可用
	 *
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function emergency($message, array $context = []);

	/**
	 * **必須**立刻採取行動
	 *
	 * 例如：在整個網站都垮掉了、數據庫不可用了或者其他的情況下，**應該**發送一條警報短信把你叫醒。
	 *
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function alert($message, array $context = []);

	/**
	 * 緊急情況
	 *
	 * 例如：程序組件不可用或者出現非預期的異常。
	 *
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function critical($message, array $context = []);

	/**
	 * 運行時出現的錯誤，不需要立刻採取行動，但必須記錄下來以備檢測。
	 *
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function error($message, array $context = []);

	/**
	 * 出現非錯誤性的異常。
	 *
	 * 例如：使用了被棄用的API、錯誤地使用了API或者非預想的不必要錯誤。
	 *
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function warning($message, array $context = []);

	/**
	 * 一般性重要的事件。
	 *
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function notice($message, array $context = []);

	/**
	 * 重要事件
	 *
	 * 例如：用戶登錄和SQL記錄。
	 *
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function info($message, array $context = []);

	/**
	 * debug 詳情
	 *
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function debug($message, array $context = []);

	/**
	 * 任意等級的日誌記錄
	 *
	 * @param mixed $level 日誌的嚴重等級
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function log($level, $message, array $context = []);
}
