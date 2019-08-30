<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-13 下午1:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 語言處理類
 * *********************************************************** */

namespace Cml;

/**
 * 語言包讀寫類、負責語言包的讀取
 *
 * @package Cml
 */
class Lang
{
	/**
	 * 存放了所有語言信息
	 *
	 * @var array
	 */
	protected static $lang = [];

	/**
	 * 獲取語言 不區分大小寫
	 *  獲取值的時候可以動態傳參轉出語言值
	 *  如：\Cml\Lang::get('_CML_DEBUG_ADD_CLASS_TIP_', '\Cml\Base') 取出_CML_DEBUG_ADD_CLASS_TIP_語言變量且將\Cml\base替換語言中的%s
	 *
	 * @param string $key 支持.獲取多維數組
	 * @param string $default 不存在的時候默認值
	 *
	 * @return string
	 */
	public static function get($key = null, $default = '')
	{
		if (empty($key)) {
			return '';
		}
		$key = strtolower($key);
		$val = Cml::doteToArr($key, self::$lang);

		if (is_null($val)) {
			return is_array($default) ? '' : $default;
		} else {
			if (is_array($default)) {
				$keys = array_keys($default);
				$keys = array_map(function ($key) {
					return '{' . $key . '}';
				}, $keys);
				return str_replace($keys, array_values($default), $val);
			} else {
				$replace = func_get_args();
				$replace[0] = $val;
				return call_user_func_array('sprintf', array_values($replace));
			}
		}
	}

	/**
	 * 設置配置【語言】 支持批量設置 /a.b.c方式設置
	 *
	 * @param string|array $key 要設置的key,為數組時是批量設置
	 * @param mixed $value 要設置的值
	 *
	 * @return null
	 */
	public static function set($key, $value = null)
	{
		if (is_array($key)) {
			static::$lang = array_merge(static::$lang, array_change_key_case($key));
		} else {
			$key = strtolower($key);

			if (!strpos($key, '.')) {
				static::$lang[$key] = $value;
				return null;
			}

			// 多維數組設置 A.B.C = 1
			$key = explode('.', $key);
			$tmp = null;
			foreach ($key as $k) {
				if (is_null($tmp)) {
					if (isset(static::$lang[$k]) === false) {
						static::$lang[$k] = [];
					}
					$tmp = &static::$lang[$k];
				} else {
					is_array($tmp) || $tmp = [];
					isset($tmp[$k]) || $tmp[$k] = [];
					$tmp = &$tmp[$k];
				}
			}
			$tmp = $value;
			unset($tmp);
		}
		return null;
	}
}
