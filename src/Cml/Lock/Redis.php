<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 15-1-25 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 鎖機制Redis驅動
 * *********************************************************** */

namespace Cml\Lock;

use Cml\Model;

/**
 * 鎖機制Redis驅動
 *
 * @package Cml\Lock
 */
class Redis extends Base
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
			&& $inst->eval('if redis.call("GET", KEYS[1]) == ARGV[1] then return redis.call("EXPIRE", KEYS[1], ' . $this->expire . ') else return 0 end'
				, [$lock, $this->lockCache[$lock]], 1)
		) {
			return true;
		}
		$unique = uniqid('', true);

		if ($inst->set(
			$lock,
			$unique,
			['nx', 'ex' => $this->expire]
		)
		) {
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
		} while (!$inst->set(
			$lock,
			$unique,
			['nx', 'ex' => $this->expire]
		));

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
		$script = 'if redis.call("GET", KEYS[1]) == ARGV[1] then return redis.call("DEL", KEYS[1]) else return 0 end';
		$res = Model::getInstance()->cache($this->useCache)->getInstance()->eval($script, [$lock, $this->lockCache[$lock]], 1);

		//Model::getInstance()->cache($this->useCache)->getInstance()->delete($lock);
		$this->lockCache[$lock] = null;//防止gc延遲,判斷有誤
		unset($this->lockCache[$lock]);
		return $res > 0;
	}
}
