<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 session 管理類
 * *********************************************************** */

namespace Cml\Http;

use Cml\Config;

/**
 * session 管理類,封裝了對session的操作
 *
 * @package Cml\Http
 */
class Session
{
	/**
	 * session的前綴
	 *
	 * @var string
	 */
	public static $prefix = '';

	/**
	 * 設置session值
	 *
	 * @param string $key 可以為單個key值，也可以為數組
	 * @param string $value value值
	 *
	 * @return string
	 */
	public static function set($key, $value = '')
	{
		empty(self::$prefix) && self::$prefix = Config::get('session_prefix');
		if (!is_array($key)) {
			$_SESSION[self::$prefix . $key] = $value;
		} else {
			foreach ($key as $k => $v) {
				$_SESSION[self::$prefix . $k] = $v;
			}
		}
		return true;
	}

	/**
	 * 獲取session值
	 *
	 * @param string $key 要獲取的session的key
	 *
	 * @return string
	 */
	public static function get($key)
	{
		empty(self::$prefix) && self::$prefix = Config::get('session_prefix');
		return (isset($_SESSION[self::$prefix . $key])) ? $_SESSION[self::$prefix . $key] : null;
	}

	/**
	 * 刪除session值
	 *
	 * @param string $key 要刪除的session的key
	 *
	 * @return string
	 */
	public static function delete($key)
	{
		empty(self::$prefix) && self::$prefix = Config::get('session_prefix');
		if (is_array($key)) {
			foreach ($key as $k) {
				if (isset($_SESSION[self::$prefix . $k])) unset($_SESSION[self::$prefix . $k]);
			}
		} else {
			if (isset($_SESSION[self::$prefix . $key])) unset($_SESSION[self::$prefix . $key]);
		}
		return true;
	}

	/**
	 * Session-清空session
	 *
	 * @return void
	 */
	public static function clear()
	{
		session_destroy();
		$_SESSION = [];
	}
}
