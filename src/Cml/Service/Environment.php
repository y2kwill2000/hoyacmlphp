<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-13 上午11:01
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 自帶環境解析實現
 * *********************************************************** */

namespace Cml\Service;

use Cml\Http\Request;
use Cml\Interfaces\Environment as EnvironmentInterface;

/**
 * 自帶環境解析實現development/product/cli三種
 *
 * @package Cml
 */
class Environment implements EnvironmentInterface
{
	/**
	 * 獲取當前環境名稱
	 *
	 * @return string
	 */
	public function getEnv()
	{
		if (Request::isCli()) {
			return 'cli';
		}

		$host = Request::host(false);

		switch ($host) {
			case $_SERVER['SERVER_ADDR'] :
				// no break
			case '127.0.0.1':
				//no break
			case 'localhost':
				return 'development';
		}

		$domain = substr($host, strrpos($host, '.') + 1);

		if ($domain == 'dev' || $domain == 'loc' || $domain == 'test') {
			return 'development';
		}

		if (substr($_SERVER['HTTP_HOST'], 0, 7) == '192.168') {
			return 'development';
		}
		return 'product';
	}
}
