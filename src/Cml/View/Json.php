<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 視圖 Json渲染引擎
 * *********************************************************** */

namespace Cml\View;

use Cml\Cml;
use Cml\Config;
use Cml\Debug;

/**
 * 視圖 Json渲染引擎
 *
 * @package Cml\View
 */
class Json extends Base
{
	/**
	 * 輸出數據
	 *
	 */
	public function display()
	{
		exit($this->fetch());
	}

	/**
	 * 獲取json輸出
	 *
	 * @return string
	 */
	public function fetch()
	{
		header('Content-Type: application/json;charset=' . Config::get('default_charset'));
		if (Cml::$debug) {
			$sql = Debug::getSqls();
			if (Config::get('dump_use_php_console')) {
				\Cml\dumpUsePHPConsole([
					'sql' => $sql,
					'tipInfo' => Debug::getTipInfo()
				], strip_tags($_SERVER['REQUEST_URI']));
			}
			$this->args['sql'] = $sql;
		} else {
			$deBugLogData = \Cml\dump('', 1);
			if (!empty($deBugLogData)) {
				Config::get('dump_use_php_console') ? \Cml\dumpUsePHPConsole($deBugLogData, 'debug') : $this->args['cml_debug_info'] = $deBugLogData;
			}
		}
		return json_encode($this->args, JSON_UNESCAPED_UNICODE) ?: json_last_error_msg();
	}
}
