<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 文件緩存驅動
 * *********************************************************** */

namespace Cml\Cache;

use Cml\Cml;
use Cml\Config;

/**
 * 文件緩存驅動
 *
 * @package Cml\Cache
 */
class File extends namespace\Base
{
	/**
	 * @var bool | resource
	 */
	private $lock = false;//是否對文件鎖操作 值為bool或打開的文件指針

	/**
	 * 使用的緩存配置 默認為使用default_cache配置的參數
	 *
	 * @param bool ｜array $conf
	 */
	public function __construct($conf = false)
	{
		$this->conf = $conf ? $conf : Config::get('default_cache');
		$this->conf['CACHE_PATH'] = isset($this->conf['CACHE_PATH']) ? $this->conf['CACHE_PATH'] : Cml::getApplicationDir('runtime_cache_path') . DIRECTORY_SEPARATOR . 'FileCache' . DIRECTORY_SEPARATOR;
		is_dir($this->conf['CACHE_PATH']) || mkdir($this->conf['CACHE_PATH'], 0700, true);
	}

	/**
	 * 刪除緩存
	 *
	 * @param string $key 要刪除的數據的key
	 *
	 * @return bool
	 */
	public function delete($key)
	{
		$fileName = $this->getFileName($key);
		return (is_file($fileName) && unlink($fileName));
	}

	/**
	 * 獲取緩存文件名
	 *
	 * @param string $key 緩存名
	 *
	 * @return string
	 */
	private function getFileName($key)
	{
		$md5Key = md5($this->conf['prefix'] . $key);

		$dir = $this->conf['CACHE_PATH'] . substr($key, 0, strrpos($key, '/')) . DIRECTORY_SEPARATOR;
		$dir .= substr($md5Key, 0, 2) . DIRECTORY_SEPARATOR . substr($md5Key, 2, 2);
		is_dir($dir) || mkdir($dir, 0700, true);
		return $dir . DIRECTORY_SEPARATOR . $md5Key . '.php';
	}

	/**
	 * 清空緩存
	 *
	 * @return bool
	 */
	public function truncate()
	{
		set_time_limit(60);
		if (!is_dir($this->conf['CACHE_PATH'])) return true;
		$this->cleanDir('all');
		return true;
	}

	/**
	 * 清空文件夾
	 *
	 * @param string $dir
	 *
	 * @return bool
	 */
	public function cleanDir($dir)
	{
		if (empty($dir)) return false;

		$dir === 'all' && $dir = '';//刪除所有
		$fullDir = $this->conf['CACHE_PATH'] . $dir;
		if (!is_dir($fullDir)) {
			return false;
		}

		$files = scandir($fullDir);
		foreach ($files as $file) {
			if ('.' === $file || '..' === $file) continue;
			$tmp = $fullDir . DIRECTORY_SEPARATOR . $file;
			if (is_dir($tmp)) {
				$this->cleanDir($dir . DIRECTORY_SEPARATOR . $file);
			} else {
				unlink($tmp);
			}
		}
		rmdir($fullDir);
		return true;
	}

	/**
	 * 自增
	 *
	 * @param string $key 要自增的緩存的數據的key
	 * @param int $val 自增的進步值,默認為1
	 *
	 * @return bool
	 */
	public function increment($key, $val = 1)
	{
		$this->lock = true;
		$v = $this->get($key);
		if (is_int($v)) {
			return $this->update($key, $v + abs(intval($val)));
		} else {
			$this->set($key, 1);
			return 1;
		}
	}

	/**
	 * 獲取緩存
	 *
	 * @param string $key 要獲取的緩存key
	 *
	 * @return mixed
	 */
	public function get($key)
	{
		$fileName = $this->getFileName($key);
		if (!is_file($fileName)) {
			if ($this->lock) {
				$this->lock = false;
				$this->set($key, 0);
				return 0;
			}
			return false;
		}
		$fp = fopen($fileName, 'r+');
		if ($this->lock) {//自增自減  上鎖
			$this->lock = $fp;
			if (flock($fp, LOCK_EX) === false) return false;
		}
		$data = fread($fp, filesize($fileName));
		$this->lock || fclose($fp);//非自增自減操作時關閉文件
		if ($data === false) {
			return false;
		}
		//緩存過期
		$fileTime = substr($data, 13, 10);
		$pos = strpos($data, ')');
		$cacheTime = substr($data, 24, $pos - 24);
		$data = substr($data, $pos + 1);
		if ($cacheTime == 0) return unserialize($data);

		if (Cml::$nowTime > (intval($fileTime) + intval($cacheTime))) {
			unlink($fileName);
			return false;//緩存過期
		}
		return unserialize($data);
	}

	/**
	 * 寫入緩存
	 *
	 * @param string $key key 要緩存的數據的key
	 * @param mixed $value 要緩存的數據 要緩存的值,除resource類型外的數據類型
	 * @param int $expire 緩存的有效時間 0為不過期
	 *
	 * @return bool
	 */
	public function set($key, $value, $expire = 0)
	{
		$value = '<?php exit;?>' . time() . "($expire)" . serialize($value);

		if ($this->lock) {//自增自減
			fseek($this->lock, 0);
			$return = fwrite($this->lock, $value);
			flock($this->lock, LOCK_UN);
			fclose($this->lock);
			$this->lock = false;
		} else {
			$fileName = $this->getFileName($key);
			$return = file_put_contents($fileName, $value, LOCK_EX);
		}
		$return && clearstatcache();
		return $return;
	}

	/**
	 * 更新緩存  可以直接用set但是為了一致性操作所以做此兼容
	 *
	 * @param string $key 要更新的數據的key
	 * @param mixed $value 要更新緩存的值,除resource類型外的數據類型
	 * @param int $expire 緩存的有效時間 0為不過期
	 *
	 * @return bool
	 */
	public function update($key, $value, $expire = 0)
	{
		return $this->set($key, $value, $expire);
	}

	/**
	 * 自減
	 *
	 * @param string $key 要自減的緩存的數據的key
	 * @param int $val 自減的進步值,默認為1
	 *
	 * @return bool
	 */
	public function decrement($key, $val = 1)
	{
		$this->lock = true;
		$v = $this->get($key);
		if (is_int($v)) {
			return $this->update($key, $v - abs(intval($val)));
		} else {
			$this->set($key, 0);
			return 0;
		}
	}

	/**
	 * 返回實例便於操作未封裝的方法
	 *
	 * @param string $key
	 *
	 * @return void
	 */
	public function getInstance($key = '')
	{
	}
}
