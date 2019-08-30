<?php namespace Cml\Logger;

/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 15-21-22 下午1:11
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Log Redis驅動實現
 * *********************************************************** */

use Cml\Config;
use Cml\Model;

/**
 * Log Redis驅動實現
 *
 * @package Cml\Logger
 */
class Redis extends Base
{
	/**
	 * 任意等級的日誌記錄
	 *
	 * @param mixed $level 日誌等級
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function log($level, $message, array $context = [])
	{
		return Model::getInstance()->cache(Config::get('redis_log_use_cache'))->getInstance()->lPush(
			Config::get('log_prefix') . '_' . $level,
			$this->format($message, $context)
		);
	}
}
