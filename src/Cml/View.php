<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 視圖渲染引擎 視圖調度工廠
 * *********************************************************** */

namespace Cml;

/**
 * 視圖渲染引擎 視圖調度工廠
 *
 * @package Cml
 */
class View
{
	/**
	 * 獲取渲染引擎
	 *
	 * @param string $engine 視圖引擎 內置html/json/xml/excel
	 *
	 * @return \Cml\View\Html
	 */
	public static function getEngine($engine = null)
	{
		is_null($engine) && $engine = Config::get('view_render_engine');
		return Cml::getContainer()->make('view_' . strtolower($engine));
	}
}
