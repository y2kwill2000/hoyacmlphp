<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-13 上午11:01
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 配置處理類
 * *********************************************************** */

namespace Cml;

use Cml\Exception\ConfigNotFoundException;

/**
 * 配置讀寫類、負責配置文件的讀取
 *
 * @package Cml
 */
class Config
{
	/**
	 * 配置文件類型
	 *
	 * @var string
	 */
	public static $isLocal = 'product';

	/**
	 * 存放了所有配置信息
	 *
	 * @var array
	 */
	private static $_content = [
		'normal' => []
	];

	public static function init()
	{
		self::$isLocal = Cml::getContainer()->make('cml_environment')->getEnv();
	}

	/**
	 * 獲取配置參數不區分大小寫
	 *
	 * @param string $key 支持.獲取多維數組
	 * @param string $default 不存在的時候默認值
	 *
	 * @return mixed
	 */
	public static function get($key = null, $default = null)
	{
		// 無參數時獲取所有
		if (empty($key)) {
			return self::$_content;
		}

		$key = strtolower($key);
		return Cml::doteToArr($key, self::$_content['normal'], $default);
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
			static::$_content['normal'] = array_merge(static::$_content['normal'], array_change_key_case($key));
		} else {
			$key = strtolower($key);

			if (!strpos($key, '.')) {
				static::$_content['normal'][$key] = $value;
				return null;
			}

			// 多維數組設置 A.B.C = 1
			$key = explode('.', $key);
			$tmp = null;
			foreach ($key as $k) {
				if (is_null($tmp)) {
					if (isset(static::$_content['normal'][$k]) === false) {
						static::$_content['normal'][$k] = [];
					}
					$tmp = &static::$_content['normal'][$k];
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

	/**
	 * 從文件載入Config
	 *
	 * @param string $file
	 * @param bool $global 是否從全局加載,true為從全局加載、false為載入當前app下的配置、字符串為從指定的app下加載
	 *
	 * @return array
	 */
	public static function load($file, $global = true)
	{
		if (isset(static::$_content[$global . $file])) {
			return static::$_content[$global . $file];
		} else {
			$filePath =
				(
				$global === true
					? Cml::getApplicationDir('global_config_path')
					: Cml::getApplicationDir('apps_path')
					. '/' . ($global === false ? Cml::getContainer()->make('cml_route')->getAppName() : $global) . '/'
					. Cml::getApplicationDir('app_config_path_name')
				)
				. '/' . ($global === true ? self::$isLocal . DIRECTORY_SEPARATOR : '') . $file . '.php';

			if (!is_file($filePath)) {
				throw new ConfigNotFoundException(Lang::get('_NOT_FOUND_', $filePath));
			}
			static::$_content[$global . $file] = Cml::requireFile($filePath);
			return static::$_content[$global . $file];
		}
	}
}
