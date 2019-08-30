<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Apc緩存驅動
 * *********************************************************** */

namespace Cml\Cache;

use Cml\Config;
use Cml\Exception\PhpExtendNotInstall;
use Cml\Lang;

/**
 * Apc緩存驅動
 *
 * @package Cml\Cache
 */
class Apc extends namespace\Base
{
	/**
	 * 使用的緩存配置 默認為使用default_cache配置的參數
	 *
	 * @param bool ｜array $conf
	 *
	 * @throws PhpExtendNotInstall
	 */
	public function __construct($conf = false)
	{
		if (!function_exists('apc_cache_info')) {
			throw new PhpExtendNotInstall(Lang::get('_CACHE_EXTENT_NOT_INSTALL_', 'Apc'));
		}
		$this->conf = $conf ? $conf : Config::get('default_cache');
	}

	/**
	 * 更新對像
	 *
	 * @param mixed $key 要更新的緩存的數據的key
	 * @param mixed $value 要更新的要緩存的值,除resource類型外的數據類型
	 * @param int $expire 緩存的有效時間 0為不過期
	 *
	 * @return bool|int
	 */
	public function update($key, $value, $expire = 0)
	{
		$arr = $this->get($key);
		if (!empty($arr)) {
			$arr = array_merge($arr, $value);
			return $this->set($key, $arr, $expire);
		}
		return 0;
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
		return apc_fetch($this->conf['prefix'] . $key);
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
		($expire == 0) && $expire = null;
		return apc_store($this->conf['prefix'] . $key, $value, $expire);
	}

	/**
	 * 刪除對像
	 *
	 * @param mixed $key 要刪除的緩存的數據的key
	 *
	 * @return bool
	 */
	public function delete($key)
	{
		return apc_delete($this->conf['prefix'] . $key);
	}

	/**
	 * 清洗已經存儲的所有元素
	 *
	 */
	public function truncate()
	{
		return apc_clear_cache('user'); //只清除用戶緩存
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
		return apc_inc($this->conf['prefix'] . $key, abs(intval($val)));
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
		return apc_dec($this->conf['prefix'] . $key, abs(intval($val)));
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
