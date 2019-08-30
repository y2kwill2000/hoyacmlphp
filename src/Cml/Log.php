<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Log處理類
 * *********************************************************** */

namespace Cml;

use Cml\Logger\Base;

/**
 * Log處理類,簡化的psr-3日誌接口,負責Log的處理
 *
 * @package Cml
 */
class Log
{
	/**
	 * 添加debug類型的日誌
	 *
	 * @param string $log 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return bool
	 */
	public static function debug($log, array $context = [])
	{
		return self::getLogger()->debug($log, $context);
	}

	/**
	 * 獲取Logger實例
	 *
	 * @return Base
	 */
	private static function getLogger()
	{
		return Cml::getContainer()->make('cml_log');
	}

	/**
	 * 添加info類型的日誌
	 *
	 * @param string $log 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return bool
	 */
	public static function info($log, array $context = [])
	{
		return self::getLogger()->info($log, $context);
	}

	/**
	 * 添加notice類型的日誌
	 *
	 * @param string $log 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return bool
	 */
	public static function notice($log, array $context = [])
	{
		return self::getLogger()->notice($log, $context);
	}

	/**
	 * 添加warning類型的日誌
	 *
	 * @param string $log 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return bool
	 */
	public static function warning($log, array $context = [])
	{
		return self::getLogger()->warning($log, $context);
	}

	/**
	 * 添加error類型的日誌
	 *
	 * @param string $log 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return bool
	 */
	public static function error($log, array $context = [])
	{
		return self::getLogger()->error($log, $context);
	}

	/**
	 * 添加critical類型的日誌
	 *
	 * @param string $log 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return bool
	 */
	public static function critical($log, array $context = [])
	{
		return self::getLogger()->critical($log, $context);
	}

	/**
	 * 添加critical類型的日誌
	 *
	 * @param string $log 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return bool
	 */
	public static function emergency($log, array $context = [])
	{
		return self::getLogger()->emergency($log, $context);
	}

	/**
	 * 錯誤日誌handler
	 *
	 * @param int $errorType 錯誤類型 分運行時警告、運行時提醒、自定義錯誤、自定義提醒、未知等
	 * @param string $errorTip 錯誤提示
	 * @param string $errorFile 發生錯誤的文件
	 * @param int $errorLine 錯誤所在行數
	 *
	 * @return void
	 */
	public static function catcherPhpError($errorType, $errorTip, $errorFile, $errorLine)
	{
		$logLevel = Cml::getWarningLogLevel();
		if (in_array($errorType, $logLevel)) {
			return;//只記錄warning以上級別日誌
		}

		self::getLogger()->log(self::getLogger()->phpErrorToLevel[$errorType], $errorTip, ['file' => $errorFile, 'line' => $errorLine]);
		return;
	}
}
