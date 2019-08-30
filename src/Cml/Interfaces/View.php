<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 視圖驅動抽像接口
 * *********************************************************** */

namespace Cml\Interfaces;

/**
 * 視圖驅動抽像接口
 *
 * @package Cml\Interfaces
 */
interface View
{
	/**
	 * 變量賦值
	 *
	 * @param string | array $key 賦值到模板的key,數組或字符串為數組時批量賦值
	 * @param mixed $val 賦值到模板的值
	 *
	 * @return $this
	 */
	public function assign($key, $val = null);

	/**
	 * 引用賦值
	 *
	 * @param string | array $key 賦值到模板的key,數組或字符串為數組時批量賦值
	 * @param mixed $val
	 *
	 * @return $this
	 */
	public function assignByRef($key, &$val = null);

	/**
	 * 獲取賦到模板的值
	 *
	 * @param string $key 要獲取的值的key,數組或字符串為數組時批量賦值
	 *
	 * @return mixed
	 */
	public function getValue($key = null);

	/**
	 * 抽像display
	 *
	 * @return mixed
	 */
	public function display();
}
