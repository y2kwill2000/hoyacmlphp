<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 15-1-25 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 鎖機制驅動抽像類基類
 * *********************************************************** */

namespace Cml\Lock;

use Cml\Config;
use Cml\Interfaces\Lock;

/**
 * 鎖驅動抽像類基類
 *
 * @package Cml\Lock
 */
abstract class Base implements Lock
{
	/**
	 *  鎖驅動使用redis/memcache時使用的緩存
	 *
	 * @var string
	 */
	protected $useCache = '';
	/**
	 * 鎖的過期時間針對Memcache/Redis兩種鎖有效,File鎖無效 單位s
	 * 設為0時不過期。此時假如開發未手動unlock且這時出現程序掛掉的情況 __destruct未執行。這時鎖必須人工介入處理
	 * 這個值可根據業務需要進行修改比如60等
	 *
	 * @var int
	 */
	protected $expire = 100;
	/**
	 * 保存鎖數據
	 *
	 * @var array
	 */
	protected $lockCache = [];

	public function __construct($useCache)
	{
		$useCache || $useCache = Config::get('locker_use_cache', 'default_cache');
		$this->useCache = $useCache;
	}

	/**
	 * 設置鎖的過期時間
	 *
	 * @param int $expire
	 *
	 * @return $this | \Cml\Lock\Redis | \Cml\Lock\Memcache | \Cml\Lock\File
	 */
	public function setExpire($expire = 100)
	{
		$this->expire = $expire;
		return $this;
	}

	/**
	 * 上鎖
	 *
	 * @param string $lock 要上的鎖的標識key
	 * @param bool $wouldBlock 是否堵塞
	 *
	 * @return bool
	 */
	final public function lock($lock, $wouldBlock = false)
	{
		if (empty($lock)) {
			return false;
		}

		return $this->execLock($this->getKey($lock), $wouldBlock);
	}

	/**
	 * 加鎖的具體實現-每個驅動自行實現原子性加鎖
	 *
	 * @param string $lock 鎖的標識key
	 * @param bool $wouldBlock 是否堵塞
	 *
	 * @return bool
	 */
	abstract protected function execLock($lock, $wouldBlock = false);

	/**
	 * 組裝key
	 *
	 * @param string $lock 要上的鎖的標識key
	 *
	 * @return string
	 */
	protected function getKey($lock)
	{
		return Config::get('lock_prefix') . $lock;
	}

	/**
	 * 解鎖
	 *
	 * @param string $lock 鎖的標識key
	 *
	 * @return bool
	 */
	final public function unlock($lock)
	{
		$lock = $this->getKey($lock);
		if (isset($this->lockCache[$lock])) {
			return $this->execUnlock($lock);
		} else {
			return false;
		}
	}

	/**
	 * 解鎖的具體實現-每個驅動自行實現原子性解鎖
	 *
	 * @param string $lock 鎖的標識
	 *
	 * @return bool
	 */
	abstract protected function execUnlock($lock);

	/**
	 * 定義析構函數 自動釋放獲得的鎖
	 *
	 */
	public function __destruct()
	{
		foreach ($this->lockCache as $lock => $isMyLock) {
			$this->execUnlock($lock);
		}
	}
}
