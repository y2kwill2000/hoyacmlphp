<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Cookie管理類
 * *********************************************************** */

namespace Cml\Http;

use Cml\Cml;
use Cml\Config;
use Cml\Encry;

/**
 * Cookie管理類，封裝了對Cookie的操作
 *
 * @package Cml\Http
 */
class Cookie
{
	/**
	 * 獲取某個Cookie值
	 *
	 * @param string $name 要獲取的cookie名稱
	 *
	 * @return bool|mixed
	 */
	public static function get($name)
	{
		if (!self::isExist($name)) return false;
		$value = $_COOKIE[Config::get('cookie_prefix') . $name];
		return Encry::decrypt($value);
	}

	/**
	 * 判斷Cookie是否存在
	 *
	 * @param $key string 要判斷Cookie
	 *
	 * @return bool
	 */
	public static function isExist($key)
	{
		return isset($_COOKIE[Config::get('cookie_prefix') . $key]);
	}

	/**
	 * 刪除某個Cookie值
	 *
	 * @param string $name 要刪除的cookie的名稱
	 * @param string $path path
	 * @param string $domain domain
	 *
	 * @return void
	 */
	public static function delete($name, $path = '', $domain = '')
	{
		self::set($name, '', -3600, $path, $domain);
		unset($_COOKIE[Config::get('cookie_prefix') . $name]);
	}

	/**
	 * 設置某個Cookie值
	 *
	 * @param string $name 要設置的cookie的名稱
	 * @param mixed $value 要設置的值
	 * @param int $expire 過期時間
	 * @param string $path path
	 * @param string $domain domain
	 *
	 * @return void
	 */
	public static function set($name, $value, $expire = 0, $path = '', $domain = '')
	{
		empty($expire) && $expire = Config::get('cookie_expire');
		empty($path) && $path = Config::get('cookie_path');
		empty($domain) && $domain = Config::get('cookie_domain');

		$expire = empty($expire) ? 0 : Cml::$nowTime + $expire;
		$value = Encry::encrypt($value);
		setcookie(Config::get('cookie_prefix') . $name, $value, $expire, $path, $domain);
		$_COOKIE[Config::get('cookie_prefix') . $name] = $value;
	}

	/**
	 * 清空Cookie值
	 *
	 * @return void
	 */
	public static function clear()
	{
		unset($_COOKIE);
	}
}
