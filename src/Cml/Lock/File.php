<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 15-1-25 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 鎖機制File驅動
 * *********************************************************** */

namespace Cml\Lock;

use Cml\Cml;

/**
 * 鎖機制File驅動
 *
 * @package Cml\Lock
 */
class File extends Base
{
	/**
	 * 定義析構函數 自動釋放獲得的鎖
	 */
	public function __destruct()
	{
		foreach ($this->lockCache as $lock => $fp) {
			flock($fp, LOCK_UN);//5.3.2 在文件資源句柄關閉時不再自動解鎖。現在要解鎖必須手動進行。
			fclose($fp);
			is_file($lock) && unlink($lock);
			$this->lockCache[$lock] = null;//防止gc延遲,判斷有誤
			unset($this->lockCache[$lock]);
		}
	}

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
		if (isset($this->lockCache[$lock])) {//FileLock不支持設置過期時間
			return true;
		}

		if (!$fp = fopen($lock, 'w+')) {
			return false;
		}

		if (flock($fp, LOCK_EX | LOCK_NB)) {
			$this->lockCache[$lock] = $fp;
			return true;
		}

		//非堵塞模式
		if (!$wouldBlock) {
			return false;
		}

		//堵塞模式
		do {
			usleep(200);
		} while (!flock($fp, LOCK_EX | LOCK_NB));

		$this->lockCache[$lock] = $fp;
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
		flock($this->lockCache[$lock], LOCK_UN);//5.3.2 在文件資源句柄關閉時不再自動解鎖。現在要解鎖必須手動進行。
		fclose($this->lockCache[$lock]);
		is_file($lock) && unlink($lock);
		$this->lockCache[$lock] = null;
		unset($this->lockCache[$lock]);
		return true;
	}

	/**
	 * 獲取緩存文件名
	 *
	 * @param string $lock 緩存名
	 *
	 * @return string
	 */
	protected function getKey($lock)
	{
		$lock = parent::getKey($lock);
		$md5Key = md5($lock);

		$dir = Cml::getApplicationDir('runtime_cache_path') . DIRECTORY_SEPARATOR . 'LockFileCache' . DIRECTORY_SEPARATOR . substr($lock, 0, strrpos($lock, '/')) . DIRECTORY_SEPARATOR;
		$dir .= substr($md5Key, 0, 2) . DIRECTORY_SEPARATOR . substr($md5Key, 2, 2);
		is_dir($dir) || mkdir($dir, 0700, true);
		return $dir . DIRECTORY_SEPARATOR . $md5Key . '.php';
	}
}
