<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Lock 抽像接口 參考 https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * *********************************************************** */

namespace Cml\Interfaces;

/**
 * Lock 抽像接口
 *
 * @package Cml\Interfaces
 */
interface Lock
{
	/**
	 * 設置鎖的過期時間
	 *
	 * @param int $expire
	 *
	 * @return \Cml\Lock\Redis | \Cml\Lock\Memcache | \Cml\Lock\File
	 */
	public function setExpire($expire = 100);

	/**
	 * 上鎖
	 *
	 * @param string $key 要解鎖的鎖的key
	 * @param bool $wouldBlock 是否堵塞
	 *
	 * @return mixed
	 */
	public function lock($key, $wouldBlock = false);

	/**
	 * 解鎖
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function unlock($key);
}
