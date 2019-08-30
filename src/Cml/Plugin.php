<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 插件類
 * *********************************************************** */

namespace Cml;

/**
 * CmlPHP中的插件實現類,負責鉤子的綁定和插件的執行
 *
 * @package Cml
 */
class Plugin
{
	/**
	 * 插件的掛載信息
	 *
	 * @var array
	 */
	private static $mountInfo = [];

	/**
	 * 執行插件
	 *
	 * @param string $hook 插件鉤子名稱
	 * @param array $params 參數
	 *
	 * @return mixed
	 */
	public static function hook($hook, $params = [])
	{
		$hookRun = isset(self::$mountInfo[$hook]) ? self::$mountInfo[$hook] : null;
		if (!is_null($hookRun)) {
			foreach ($hookRun as $key => $val) {
				if (is_int($key)) {
					$callBack = $val;
				} else {
					$plugin = new $key();
					$callBack = [$plugin, $val];
				}
				$return = call_user_func_array($callBack, array_slice(func_get_args(), 1));

				if (!is_null($return)) {
					return $return;
				}
			}
		}
		return null;
	}

	/**
	 * 掛載插件到鉤子
	 * \Cml\Plugin::mount('hookName', [
	 * function() {//匿名函數
	 * },
	 * '\App\Test\Plugins' => 'run' //對像,
	 * '\App\Test\Plugins::run'////靜態方法
	 * ]);
	 *
	 * @param string $hook 要掛載的目標鉤子
	 * @param array $params 相應參數
	 */
	public static function mount($hook, $params = [])
	{
		is_array($params) || $params = [$params];
		if (isset(self::$mountInfo[$hook])) {
			self::$mountInfo[$hook] += $params;
		} else {
			self::$mountInfo[$hook] = $params;
		}
	}
}
