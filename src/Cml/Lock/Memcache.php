<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 15-1-25 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 鎖機制Memcache驅動
 * *********************************************************** */

namespace Cml\Lock;

use Cml\Log;
use Cml\Model;

/**
 * 鎖機制Memcache驅動
 *
 * @package Cml\Lock
 */
class Memcache extends Base
{
	/**
	 * 加鎖的具體實現-每個驅動自行實現原子性加鎖
	 *
	 * @param string $lock 鎖的標識key
	 * @param bool $wouldBlock 是否堵塞
	 *
	 * @return bool
	 */
	protected function execLock($lock, $wouldBlock = false)
	{
		$inst = Model::getInstance()->cache($this->useCache)->getInstance();

		if (
			isset($this->lockCache[$lock])
			&& $this->lockCache[$lock] == $inst->get($lock)
		) {
			return true;
		}
		$unique = uniqid('', true);

		$driverType = Model::getInstance()->cache($this->useCache)->getDriverType();
		if ($driverType === 1) { //memcached
			$isLock = $inst->add($lock, $unique, $this->expire);
		} else {//memcache
			$isLock = $inst->add($lock, $unique, 0, $this->expire);
		}
		if ($isLock) {
			$this->lockCache[$lock] = $unique;
			return true;
		}

		//非堵塞模式
		if (!$wouldBlock) {
			return false;
		}

		//堵塞模式
		do {
			usleep(200);

			if ($driverType === 1) { //memcached
				$isLock = $inst->add($lock, $unique, $this->expire);
			} else {//memcache
				$isLock = $inst->add($lock, $unique, 0, $this->expire);
			}
		} while (!$isLock);

		$this->lockCache[$lock] = $unique;
		return true;
	}

	/**
	 * 解鎖的具體實現-每個驅動自行實現原子性解鎖
	 *
	 * @param string $lock 鎖的標識key
	 *
	 * @return bool
	 */
	protected function execUnlock($lock)
	{
		$inst = Model::getInstance()->cache($this->useCache);

		$success = false;
		if ($inst->getDriverType() === 1) { //memcached
			$cas = 0;
			if (defined('Memcached::GET_EXTENDED')) {
				$lockValue = $inst->getInstance()->get($lock, null, \Memcached::GET_EXTENDED);
				if (is_array($lockValue)) {
					$cas = $lockValue['cas'];
					$lockValue = $lockValue['value'];
				}
			} else {
				$lockValue = $inst->getInstance()->get($lock, null, $cas);
			}
			if ($this->lockCache[$lock] == $lockValue && $inst->getInstance()->cas($cas, $lock, 0, $this->expire)) {
				$success = true;
			}
		} else {//memcache
			$lockValue = $inst->getInstance()->get($lock);
			if ($this->lockCache[$lock] == $lockValue) {
				$success = true;
			}
		}

		if ($success) {
			$inst->getInstance()->delete($lock);
		}
		$this->lockCache[$lock] = null;//防止gc延遲,判斷有誤
		unset($this->lockCache[$lock]);
		return $success;
	}
}
