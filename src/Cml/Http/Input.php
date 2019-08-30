<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 輸入管理類
 * *********************************************************** */

namespace Cml\Http;

/**
 * 輸入過濾管理類,用戶輸入數據通過此類獲取
 *
 * @package Cml\Http
 */
class Input
{

	/**
	 * 獲取get string數據
	 *
	 * @param string $name 要獲取的變量
	 * @param null $default 未獲取到$_GET值時返回的默認值
	 *
	 * @return string|null|array
	 */
	public static function getString($name, $default = null)
	{
		if (isset($_GET[$name]) && $_GET[$name] !== '') return self::parseInputToString($_GET[$name]);
		return $default;
	}

	/**
	 * 統一的處理輸入-輸出為字符串
	 *
	 * @param array|string $params
	 *
	 * @return array|string
	 */
	private static function parseInputToString($params)
	{
		return is_array($params) ? array_map(function ($item) {
			return trim(htmlspecialchars($item, ENT_QUOTES, 'UTF-8'));
		}, $params) : trim(htmlspecialchars($params, ENT_QUOTES, 'UTF-8'));
	}

	/**
	 * 獲取post string數據
	 *
	 * @param string $name 要獲取的變量
	 * @param null $default 未獲取到$_POST值時返回的默認值
	 *
	 * @return string|null|array
	 */
	public static function postString($name, $default = null)
	{
		if (isset($_POST[$name]) && $_POST[$name] !== '') return self::parseInputToString($_POST[$name]);
		return $default;
	}

	/**
	 * 獲取$_REQUEST string數據
	 *
	 * @param string $name 要獲取的變量
	 * @param null $default 未獲取到$_REQUEST值時返回的默認值
	 *
	 * @return null|string|array
	 */
	public static function requestString($name, $default = null)
	{
		if (isset($_REQUEST[$name]) && $_REQUEST[$name] !== '') return self::parseInputToString($_REQUEST[$name]);
		return $default;
	}

	/**
	 * 獲取Refer string數據
	 *
	 * @param string $name 要獲取的變量
	 * @param null $default 未獲取到Refer值時返回的默認值
	 *
	 * @return null|string|array
	 */
	public static function referString($name, $default = null)
	{
		$res = self::getReferParams($name);
		if (!is_null($res)) return self::parseInputToString($res);
		return $default;
	}

	/**
	 * 獲取解析後的Refer的參數
	 * @param string $name 參數的key
	 *
	 * @return mixed
	 */
	private static function getReferParams($name)
	{
		static $params = null;

		if (is_null($params)) {
			if (isset($_SERVER['HTTP_REFERER'])) {
				$args = parse_url($_SERVER['HTTP_REFERER']);
				parse_str($args['query'], $params);
			}
		}
		return isset($params[$name]) ? $params[$name] : null;
	}

	/**
	 * 獲取get int數據
	 *
	 * @param string $name 要獲取的變量
	 * @param null $default 未獲取到$_GET值時返回的默認值
	 *
	 * @return int|null|array
	 */
	public static function getInt($name, $default = null)
	{
		if (isset($_GET[$name]) && $_GET[$name] !== '') return self::parseInputToInt($_GET[$name]);
		return (is_null($default) ? null : intval($default));
	}

	/**
	 * 統一的處理輸入-輸出為整型
	 *
	 * @param array|string $params
	 *
	 * @return array|int
	 */
	private static function parseInputToInt($params)
	{
		return is_array($params) ? array_map(function ($item) {
			return intval($item);
		}, $params) : intval($params);
	}

	/**
	 * 獲取post int數據
	 *
	 * @param string $name 要獲取的變量
	 * @param null $default 未獲取到$_POST值時返回的默認值
	 *
	 * @return int|null|array
	 */
	public static function postInt($name, $default = null)
	{
		if (isset($_POST[$name]) && $_POST[$name] !== '') return self::parseInputToInt($_POST[$name]);
		return (is_null($default) ? null : intval($default));
	}

	/**
	 * 獲取$_REQUEST int數據
	 *
	 * @param string $name 要獲取的變量
	 * @param null $default 未獲取到$_REQUEST值時返回的默認值
	 *
	 * @return null|int|array
	 */
	public static function requestInt($name, $default = null)
	{
		if (isset($_REQUEST[$name]) && $_REQUEST[$name] !== '') return self::parseInputToInt($_REQUEST[$name]);
		return (is_null($default) ? null : intval($default));
	}

	/**
	 * 獲取Refer int數據
	 *
	 * @param string $name 要獲取的變量
	 * @param null $default 未獲取到Refer值時返回的默認值
	 *
	 * @return null|string|array
	 */
	public static function referInt($name, $default = null)
	{
		$res = self::getReferParams($name);
		if (!is_null($res)) return self::parseInputToInt($res);
		return (is_null($default) ? null : intval($default));
	}

	/**
	 * 獲取get bool數據
	 *
	 * @param string $name 要獲取的變量
	 * @param null $default 未獲取到$_GET值時返回的默認值
	 *
	 * @return bool|null|array
	 */
	public static function getBool($name, $default = null)
	{
		if (isset($_GET[$name]) && $_GET[$name] !== '') return self::parseInputToBool($_GET[$name]);
		return (is_null($default) ? null : ((bool)$default));
	}

	/**
	 * 統一的處理輸入-輸出為布爾型
	 *
	 * @param array|string $params
	 *
	 * @return array|bool
	 */
	private static function parseInputToBool($params)
	{
		return is_array($params) ? array_map(function ($item) {
			return ((bool)$item);
		}, $params) : ((bool)$params);
	}

	/**
	 * 獲取post bool數據
	 *
	 * @param string $name 要獲取的變量
	 * @param null $default 未獲取到$_POST值時返回的默認值
	 *
	 * @return bool|null|array
	 */
	public static function postBool($name, $default = null)
	{
		if (isset($_POST[$name]) && $_POST[$name] !== '') return self::parseInputToBool($_POST[$name]);
		return (is_null($default) ? null : ((bool)$default));
	}

	/**
	 * 獲取$_REQUEST bool數據
	 *
	 * @param string $name 要獲取的變量
	 * @param null $default 未獲取到$_REQUEST值時返回的默認值
	 *
	 * @return null|bool|array
	 */
	public static function requestBool($name, $default = null)
	{
		if (isset($_REQUEST[$name]) && $_REQUEST[$name] !== '') return self::parseInputToBool($_REQUEST[$name]);
		return (is_null($default) ? null : ((bool)$default));
	}

	/**
	 * 獲取Refer bool數據
	 *
	 * @param string $name 要獲取的變量
	 * @param null $default 未獲取到Refer值時返回的默認值
	 *
	 * @return null|string|array
	 */
	public static function referBool($name, $default = null)
	{
		$res = self::getReferParams($name);
		if (!is_null($res)) return self::parseInputToBool($res);
		return (is_null($default) ? null : ((bool)$default));
	}
}
