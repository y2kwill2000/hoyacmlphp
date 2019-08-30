<?php namespace Cml\Queue;

/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-02-04 下午20:11
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 隊列Redis驅動
 * *********************************************************** */

use Cml\Config;
use Cml\Model;

/**
 * 隊列Redis驅動
 *
 * @package Cml\Queue
 */
class Redis extends Base
{
	private $useCache = '';

	/**
	 * Redis隊列驅動
	 *
	 * @param mixed $useCache 使用的緩存配置key,未傳則獲取redis_queue_use_cache中配置的key
	 */
	public function __construct($useCache = false)
	{
		$this->useCache = $useCache ? $useCache : Config::get('redis_queue_use_cache');
	}

	/**
	 * 從列表頭入隊
	 *
	 * @param string $name 要從列表頭入隊的隊列的名稱
	 * @param mixed $data 要入隊的數據
	 *
	 * @return mixed
	 */
	public function lPush($name, $data)
	{
		return $this->getDriver()->lPush($name, $this->encodeDate($data));
	}

	/**
	 * 返回驅動
	 *
	 * @return \Redis
	 */
	private function getDriver()
	{
		return Model::getInstance()->cache($this->useCache)->getInstance();
	}

	/**
	 * 從列表頭出隊
	 *
	 * @param string $name 要從列表頭出隊的隊列的名稱
	 *
	 * @return mixed
	 */
	public function lPop($name)
	{
		$data = $this->getDriver()->lPop($name);
		$data && $data = $this->decodeDate($data);
		return $data;
	}

	/**
	 * 從列表尾入隊
	 *
	 * @param string $name 要從列表尾入隊的隊列的名稱
	 * @param mixed $data 要入隊的數據
	 *
	 * @return mixed
	 */
	public function rPush($name, $data)
	{
		return $this->getDriver()->rPush($name, $this->encodeDate($data));
	}

	/**
	 * 從列表尾出隊
	 *
	 * @param string $name 要從列表尾出隊的隊列的名稱
	 *
	 * @return mixed
	 */
	public function rPop($name)
	{
		$data = $this->getDriver()->rPop($name);
		$data && $data = $this->decodeDate($data);
		return $data;
	}

	/**
	 * 彈入彈出
	 *
	 * @param string $from 要彈出的隊列名稱
	 * @param string $to 要入隊的隊列名稱
	 *
	 * @return mixed
	 */
	public function rPopLpush($from, $to)
	{
		return $this->getDriver()->rpoplpush($from, $to);
	}
}
