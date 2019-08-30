<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Redis緩存驅動
 * *********************************************************** */

namespace Cml\Cache;

use Cml\Config;
use Cml\Exception\CacheConnectFailException;
use Cml\Exception\PhpExtendNotInstall;
use Cml\Lang;
use Cml\Lock;
use Cml\Log;
use Cml\Plugin;

/**
 * Redis緩存驅動
 *
 * @package Cml\Cache
 */
class Redis extends namespace\Base
{
	/**
	 * @var array(\Redis)
	 */
	private $redis = [];

	/**
	 * 使用的緩存配置 默認為使用default_cache配置的參數
	 *
	 * @param bool ｜array $conf
	 */
	public function __construct($conf = false)
	{
		$this->conf = $conf ? $conf : Config::get('default_cache');

		if (!extension_loaded('redis')) {
			throw new PhpExtendNotInstall(Lang::get('_CACHE_EXTEND_NOT_INSTALL_', 'Redis'));
		}
	}

	/**
	 * 根據key取值
	 *
	 * @param mixed $key 要獲取的緩存key
	 *
	 * @return bool | array
	 */
	public function get($key)
	{
		$return = json_decode($this->hash($key)->get($key), true);
		is_null($return) && $return = false;
		return $return; //orm層做判斷用
	}

	/**
	 * 根據key獲取redis實例
	 * 這邊還是用取模的方式，一致性hash用php實現性能開銷過大。取模的方式對只有幾台機器的情況足夠用了
	 * 如果有集群需要，直接使用redis3.0+自帶的集群功能就好了。不管是可用性還是性能都比用php自己實現好
	 *
	 * @param $key
	 *
	 * @return \Redis
	 */
	private function hash($key)
	{
		$serverNum = count($this->conf['server']);
		$success = sprintf('%u', crc32($key)) % $serverNum;

		if (!isset($this->redis[$success]) || !is_object($this->redis[$success])) {
			$instance = new \Redis();

			$connectToRedisFunction = function ($host, $port, $isPersistentConnect) use ($instance) {
				try {
					if ($isPersistentConnect) {
						return $instance->pconnect($host, $port, 1.5);
					} else {
						return $instance->connect($host, $port, 1.5);
					}
				} catch (\Exception $e) {
					return false;
				}
			};

			$isPersistentConnect = !(isset($this->conf['server'][$success]['pconnect']) && $this->conf['server'][$success]['pconnect'] === false);
			$connectResult = $connectToRedisFunction($this->conf['server'][$success]['host'], $this->conf['server'][$success]['port'], $isPersistentConnect);

			$failOver = null;

			if (!$connectResult && !empty($this->conf['back'])) {
				$failOver = $this->conf['back'];
				$isPersistentConnect = !(isset($failOver['pconnect']) && $failOver['pconnect'] === false);
				$connectResult = $connectToRedisFunction($failOver['host'], $failOver['port'], $isPersistentConnect);
			}

			if (!$connectResult && $serverNum > 1) {
				$failOver = $success + 1;
				$failOver >= $serverNum && $failOver = $success - 1;
				$failOver = $this->conf['server'][$failOver];
				$isPersistentConnect = !(isset($failOver['pconnect']) && $failOver['pconnect'] === false);
				$connectResult = $connectToRedisFunction($failOver['host'], $failOver['port'], $isPersistentConnect);
			}

			if (!$connectResult) {
				Plugin::hook('cml.cache_server_down', [$this->conf['server'][$success]]);

				throw new CacheConnectFailException(Lang::get('_CACHE_CONNECT_FAIL_', 'Redis',
					$this->conf['server'][$success]['host'] . ':' . $this->conf['server'][$success]['port']
				));
			}


			$instance->setOption(\Redis::OPT_PREFIX, $this->conf['prefix']);
			$instance->setOption(\Redis::OPT_READ_TIMEOUT, -1);
			$this->redis[$success] = $instance;

			$password = false;
			if (is_null($failOver)) {
				if (isset($this->conf['server'][$success]['password']) && !empty($this->conf['server'][$success]['password'])) {
					$password = $this->conf['server'][$success]['password'];
				}

				if ($password && !$this->redis[$success]->auth($password)) {
					throw new \RuntimeException('redis password error!');
				}

				isset($this->conf['server'][$success]['db']) && $this->redis[$success]->select($this->conf['server'][$success]['db']);
			} else {
				if (isset($failOver['password']) && !empty($failOver['password'])) {
					$password = $failOver['password'];
				}

				if ($password && !$this->redis[$success]->auth($password)) {
					throw new \RuntimeException('redis password error!');
				}

				isset($failOver['db']) && $this->redis[$success]->select($failOver['db']);

				Log::emergency('redis server down', ['downServer' => $this->conf['server'][$success], 'failOverTo' => $failOver]);
				Plugin::hook('cml.redis_server_down_fail_over', ['downServer' => $this->conf['server'][$success], 'failOverTo' => $failOver]);
			}
		}
		return $this->redis[$success];
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
		$value = json_encode($value, JSON_UNESCAPED_UNICODE);
		if ($expire > 0) {
			return $this->hash($key)->setex($key, $expire, $value);
		} else {
			return $this->hash($key)->set($key, $value);
		}
	}

	/**
	 * 更新對像
	 *
	 * @param mixed $key 要更新的數據的key
	 * @param mixed $value 要更新緩存的值,除resource類型外的數據類型
	 * @param int $expire 緩存的有效時間 0為不過期
	 *
	 * @return bool|int
	 */
	public function update($key, $value, $expire = 0)
	{
		$value = json_encode($value, JSON_UNESCAPED_UNICODE);
		if ($expire > 0) {
			return $this->hash($key)->set($key, $value, ['xx', 'ex' => $expire]);
		} else {
			return $this->hash($key)->set($key, $value, ['xx']);
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
		return $this->hash($key)->del($key);
	}

	/**
	 * 清洗已經存儲的所有元素
	 *
	 */
	public function truncate()
	{
		foreach ($this->conf['server'] as $key => $val) {
			if (!isset($this->redis[$key]) || !is_object($this->redis[$key])) {
				$instance = new \Redis();
				if ($instance->pconnect($val['host'], $val['port'], 1.5)) {
					$val['password'] && $instance->auth($val['password']);
					$this->redis[$key] = $instance;
				} else {
					throw new \RuntimeException(Lang::get('_CACHE_NEW_INSTANCE_ERROR_', 'Redis'));
				}
			}
			$this->redis[$key]->flushDB();
			$this->redis[$key]->close();
			unset($this->redis[$key]);
		}
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
		return $this->hash($key)->incrBy($key, abs(intval($val)));
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
		return $this->hash($key)->decrBy($key, abs(intval($val)));
	}

	/**
	 * 判斷key值是否存在
	 *
	 * @param mixed $key 要判斷的緩存的數據的key
	 *
	 * @return mixed
	 */
	public function exists($key)
	{
		return $this->hash($key)->exists($key);
	}

	/**
	 * 返回實例便於操作未封裝的方法
	 *
	 * @param string $key
	 *
	 * @return \Redis
	 */
	public function getInstance($key = '')
	{
		return $this->hash($key);
	}

	/**
	 * 定義析構方法。不用判斷長短連接，長鏈接執行close無效
	 *
	 */
	public function __destruct()
	{
		Lock::getLocker()->__destruct();//防止在lock gc之前 cache已經發生gc
		foreach ($this->redis as $instance) {
			$instance->close();
		}
		$this->redis = [];
	}
}
