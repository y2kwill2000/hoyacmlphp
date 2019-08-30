<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 緩存驅動抽像接口
 * *********************************************************** */

namespace Cml\Interfaces;

/**
 * 緩存驅動抽像接口
 *
 * @package Cml\Interfaces
 */
interface Cache
{

	/**
	 * 使用的緩存配置 默認為使用default_cache配置的參數
	 *
	 * @param bool ｜array $conf
	 */
	public function __construct($conf = false);

	/**
	 * 根據key取值
	 *
	 * @param mixed $key 要獲取的緩存key
	 *
	 * @return mixed
	 */
	public function get($key);

	/**
	 * 存儲對像
	 *
	 * @param mixed $key 要緩存的數據的key
	 * @param mixed $value 要緩存的值,除resource類型外的數據類型
	 * @param int $expire 緩存的有效時間 0為不過期
	 *
	 * @return bool
	 */
	public function set($key, $value, $expire = 0);

	/**
	 * 更新對像
	 *
	 * @param mixed $key 要更新的數據的key
	 * @param mixed $value 要更新緩存的值,除resource類型外的數據類型
	 * @param int $expire 緩存的有效時間 0為不過期
	 *
	 * @return bool|int
	 */
	public function update($key, $value, $expire = 0);

	/**
	 * 刪除對像
	 *
	 * @param mixed $key 要刪除的數據的key
	 *
	 * @return bool
	 */
	public function delete($key);

	/**
	 * 清洗已經存儲的所有元素
	 *
	 * @return bool
	 */
	public function truncate();

	/**
	 * 自增
	 *
	 * @param mixed $key 要自增的緩存的數據的key
	 * @param int $val 自增的進步值,默認為1
	 *
	 * @return bool
	 */
	public function increment($key, $val = 1);

	/**
	 * 自減
	 *
	 * @param mixed $key 要自減的緩存的數據的key
	 * @param int $val 自減的進步值,默認為1
	 *
	 * @return bool
	 */
	public function decrement($key, $val = 1);

	/**
	 * 返回實例便於操作未封裝的方法
	 *
	 * @param string $key
	 *
	 * @return \Redis | \Memcache | \Memcached
	 */
	public function getInstance($key = '');
}
