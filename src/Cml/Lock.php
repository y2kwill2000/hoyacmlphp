<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-4-15
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Lock處理類
 * *********************************************************** */

namespace Cml;

/**
 * Lock處理類提供統一的鎖機制
 *
 * @package Cml
 */
class Lock
{
	/**
	 * 設置鎖的過期時間
	 *
	 * @param int $expire
	 *
	 * @return \Cml\Lock\Redis | \Cml\Lock\Memcache | \Cml\Lock\File
	 */
	public static function setExpire($expire = 100)
	{
		return self::getLocker()->setExpire($expire);
	}

	/**
	 * 獲取Lock實例
	 *
	 * @param string|null $useCache 使用的鎖的配置
	 *
	 * @return \Cml\Lock\Redis | \Cml\Lock\Memcache | \Cml\Lock\File | false
	 */
	public static function getLocker($useCache = null)
	{
		return Cml::getContainer()->make('cml_lock', $useCache);
	}

	/**
	 * 上鎖並重試N次-每2000微秒重試一次
	 *
	 * @param string $key 要解鎖的鎖的key
	 * @param int $reTryTimes 重試的次數
	 *
	 * @return bool
	 */
	public static function lockWait($key, $reTryTimes = 3)
	{
		$reTryTimes = intval($reTryTimes);

		$i = 0;
		while (!self::lock($key)) {
			if (++$i >= $reTryTimes) {
				return false;
			}
			usleep(2000);
		}

		return true;
	}

	/**
	 * 上鎖
	 *
	 * @param string $key 要解鎖的鎖的key
	 * @param bool $wouldBlock 是否堵塞
	 *
	 * @return mixed
	 */
	public static function lock($key, $wouldBlock = false)
	{
		return self::getLocker()->lock($key, $wouldBlock);
	}

	/**
	 * 解鎖
	 *
	 * @param string $key
	 *
	 * @return void
	 */
	public static function unlock($key)
	{
		self::getLocker()->unlock($key);
	}
}
