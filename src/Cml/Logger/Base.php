<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 15-12-22 下午1:11
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Logger 抽像類 參考 https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * *********************************************************** */

namespace Cml\Logger;

use Cml\Config;
use Cml\Http\Request;
use Cml\Interfaces\Logger;

/**
 * Logger 抽像類
 *
 * @package Cml\Logger
 */
abstract class Base implements Logger
{
	const EMERGENCY = 'emergency';
	const ALERT = 'alert';
	const CRITICAL = 'critical';
	const ERROR = 'error';
	const WARNING = 'warning';
	const NOTICE = 'notice';
	const INFO = 'info';
	const DEBUG = 'debug';

	/**
	 * php錯誤相對應的錯誤等級
	 *
	 * @var array
	 */
	public $phpErrorToLevel = [
		E_ERROR => self::EMERGENCY,
		E_WARNING => self::WARNING,
		E_PARSE => self::EMERGENCY,
		E_NOTICE => self::NOTICE,
		E_CORE_ERROR => self::EMERGENCY,
		E_CORE_WARNING => self::EMERGENCY,
		E_COMPILE_ERROR => self::EMERGENCY,
		E_COMPILE_WARNING => self::EMERGENCY,
		E_USER_ERROR => self::ERROR,
		E_USER_WARNING => self::WARNING,
		E_USER_NOTICE => self::NOTICE,
		E_STRICT => self::NOTICE,
		E_RECOVERABLE_ERROR => self::ERROR,
		E_DEPRECATED => self::NOTICE,
		E_USER_DEPRECATED => self::NOTICE,
	];

	/**
	 * 系統不可用
	 *
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function emergency($message, array $context = [])
	{
		return $this->log(self::EMERGENCY, $message, $context);
	}

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
	public function alert($message, array $context = [])
	{
		return $this->log(self::ALERT, $message, $context);
	}

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
	public function critical($message, array $context = [])
	{
		return $this->log(self::CRITICAL, $message, $context);
	}

	/**
	 * 運行時出現的錯誤，不需要立刻採取行動，但必須記錄下來以備檢測。
	 *
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function error($message, array $context = [])
	{
		return $this->log(self::ERROR, $message, $context);
	}

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
	public function warning($message, array $context = [])
	{
		return $this->log(self::WARNING, $message, $context);
	}

	/**
	 * 一般性重要的事件。
	 *
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function notice($message, array $context = [])
	{
		return $this->log(self::NOTICE, $message, $context);
	}

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
	public function info($message, array $context = [])
	{
		return $this->log(self::INFO, $message, $context);
	}

	/**
	 * debug 詳情
	 *
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function debug($message, array $context = [])
	{
		return $this->log(self::DEBUG, $message, $context);
	}

	/**
	 * 格式化日誌
	 *
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return string
	 */
	public function format($message, array $context = [])
	{
		is_array($context) || $context = [$context];
		$context['cmlphp_log_src'] = Request::isCli() ? 'cli' : 'web';
		return '[' . date('Y-m-d H:i:s') . '] ' . Config::get('log_prefix', 'cml_log') . ': ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
	}
}
