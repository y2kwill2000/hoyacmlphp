<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 memcache緩存驅動
 * *********************************************************** */

namespace Cml\Cache;

use Cml\Config;
use Cml\Exception\CacheConnectFailException;
use Cml\Exception\PhpExtendNotInstall;
use Cml\Lang;
use Cml\Log;
use Cml\Plugin;

/**
 * memcache緩存驅動
 *
 * @package Cml\Cache
 */
class Memcache extends namespace\Base
{
	/**
	 * @var \Memcache | \Memcached
	 */
	private $memcache;

	/**
	 * @var int 類型 1Memcached 2 Memcache
	 */
	private $type = 1;

	/**
	 * 使用的緩存配置 默認為使用default_cache配置的參數
	 *
	 * @param bool ｜array $conf
	 *
	 * @throws CacheConnectFailException | PhpExtendNotInstall
	 */
	public function __construct($conf = false)
	{
		$this->conf = $conf ? $conf : Config::get('default_cache');

		if (extension_loaded('Memcached')) {
			$this->memcache = new \Memcached('cml_memcache_pool');
			$this->type = 1;
		} elseif (extension_loaded('Memcache')) {
			$this->memcache = new \Memcache;
			$this->type = 2;
		} else {
			throw new PhpExtendNotInstall(Lang::get('_CACHE_EXTEND_NOT_INSTALL_', 'Memcached/Memcache'));
		}

		if (!$this->memcache) {
			throw new PhpExtendNotInstall(Lang::get('_CACHE_NEW_INSTANCE_ERROR_', 'Memcache'));
		}

		$singleNodeDownFunction = function ($host, $port) {
			Plugin::hook('cml.cache_server_down', ['host' => $host, 'port' => $port]);
			Log::emergency('memcache server down', ['downServer' => ['host' => $host, 'port' => $port]]);
		};

		$allNodeDownFunction = function ($serverList) {
			Plugin::hook('cml.cache_server_down', ['on_cache_server_list' => $serverList]);//全掛

			throw new CacheConnectFailException(Lang::get('_CACHE_CONNECT_FAIL_', 'Memcache',
				json_encode($serverList)
			));
		};

		$downServer = 0;

		if ($this->type == 2) {//memcache
			foreach ($this->conf['server'] as $val) {
				if (!$this->memcache->addServer($val['host'], $val['port'], true, isset($val['weight']) ? $val['weight'] : null)) {
					Log::emergency('memcache server down', ['downServer' => $val]);
				}
			}

			//method_exists($this->memcache, 'setFailureCallback') && $this->memcache->setFailureCallback($singleNodeDownFunction);

			$serverList = $this->memcache->getextendedstats();
			foreach ($serverList as $server => $status) {
				if (!$status) {
					$downServer++;
					$server = explode(':', $server);
					$singleNodeDownFunction($server[0], $server[1]);
				}
			}

			if (count($serverList) <= $downServer) {
				$allNodeDownFunction($serverList);
			}

			return;
		}

		$serverList = $this->memcache->getServerList();
		if (count($this->conf['server']) !== count($serverList)) {
			$this->memcache->quit();
			$this->memcache->resetServerList();

			$this->memcache->setOptions([
				\Memcached::OPT_PREFIX_KEY => $this->conf['prefix'],
				\Memcached::OPT_DISTRIBUTION => \Memcached::DISTRIBUTION_CONSISTENT,
				\Memcached::OPT_LIBKETAMA_COMPATIBLE => true,
				\Memcached::OPT_SERVER_FAILURE_LIMIT => 1,
				\Memcached::OPT_RETRY_TIMEOUT => 30,
				\Memcached::OPT_AUTO_EJECT_HOSTS => true,
				\Memcached::OPT_REMOVE_FAILED_SERVERS => true,
				\Memcached::OPT_BINARY_PROTOCOL => true,
				\Memcached::OPT_TCP_NODELAY => true
			]);
			\Memcached::HAVE_JSON && $this->memcache->setOption(\Memcached::OPT_SERIALIZER, \Memcached::SERIALIZER_JSON_ARRAY);

			$servers = [];
			foreach ($this->conf['server'] as $item) {
				$servers[] = [$item['host'], $item['port'], isset($item['weight']) ? $item['weight'] : 0];
			}
			$this->memcache->addServers($servers);
			isset($this->conf['server'][0]['username']) && $this->memcache->setSaslAuthData($this->conf['server'][0]['username'], $this->conf['server'][0]['password']);
		}

		$serverStatus = $this->memcache->getStats();
		if ($serverStatus === false) {//Memcached驅動無法判斷全掛還是單台掛。這邊不拋異常
			$singleNodeDownFunction($serverList, '');
		}
	}

	/**
	 * 返回memcache驅動類型  加鎖時用
	 *
	 * @return int
	 */
	public function getDriverType()
	{
		return $this->type;
	}

	/**
	 * 根據key取值
	 *
	 * @param mixed $key 要獲取的緩存key
	 *
	 * @return mixed
	 */
	public function get($key)
	{
		if ($this->type === 1) {
			$return = $this->memcache->get($key);
		} else {
			$return = json_decode($this->memcache->get($this->conf['prefix'] . $key), true);
		}

		is_null($return) && $return = false;
		return $return; //orm層做判斷用
	}

	/**
	 * 存儲對像
	 *
	 * @param mixed $key 要緩存的數據的key
	 * @param mixed $value 要緩存的值,除resource類型外的數據類型
	 * @param int $expire 緩存的有效時間 0為不過期
	 *
	 * @return bool
	 */
	public function set($key, $value, $expire = 0)
	{
		if ($this->type === 1) {
			return $this->memcache->set($key, $value, $expire);
		} else {
			return $this->memcache->set($this->conf['prefix'] . $key, json_encode($value, JSON_UNESCAPED_UNICODE), false, $expire);
		}
	}

	/**
	 * 更新對像
	 *
	 * @param mixed $key 要更新的數據的key
	 * @param mixed $value 要更新緩存的值,除resource類型外的數據類型
	 * @param int $expire 緩存的有效時間 0為不過期
	 *
	 * @return bool
	 */
	public function update($key, $value, $expire = 0)
	{
		if ($this->type === 1) {
			return $this->memcache->replace($key, $value, $expire);
		} else {
			return $this->memcache->replace($this->conf['prefix'] . $key, json_encode($value, JSON_UNESCAPED_UNICODE), false, $expire);
		}

	}

	/**
	 * 刪除對像
	 *
	 * @param mixed $key 要刪除的數據的key
	 *
	 * @return bool
	 */
	public function delete($key)
	{
		$this->type === 2 && $key = $this->conf['prefix'] . $key;
		return $this->memcache->delete($key);
	}

	/**
	 * 清洗已經存儲的所有元素
	 *
	 */
	public function truncate()
	{
		$this->memcache->flush();
		return true;
	}

	/**
	 * 自增
	 *
	 * @param mixed $key 要自增的緩存的數據的key
	 * @param int $val 自增的進步值,默認為1
	 *
	 * @return bool
	 */
	public function increment($key, $val = 1)
	{
		$this->type === 2 && $key = $this->conf['prefix'] . $key;
		return $this->memcache->increment($key, abs(intval($val)));
	}

	/**
	 * 自減
	 *
	 * @param mixed $key 要自減的緩存的數據的key
	 * @param int $val 自減的進步值,默認為1
	 *
	 * @return bool
	 */
	public function decrement($key, $val = 1)
	{
		$this->type === 2 && $key = $this->conf['prefix'] . $key;
		return $this->memcache->decrement($key, abs(intval($val)));
	}

	/**
	 * 返回實例便於操作未封裝的方法
	 *
	 * @param string $key
	 *
	 * @return \Memcache|\Memcached
	 */
	public function getInstance($key = '')
	{
		return $this->memcache;
	}
}
