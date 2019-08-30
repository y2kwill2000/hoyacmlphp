<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 視圖渲染引擎 抽像基類
 * *********************************************************** */

namespace Cml\View;

use Cml\Interfaces\View;

/**
 * 視圖渲染引擎 抽像基類
 *
 * @package Cml\View
 */
abstract class Base implements View
{
	/**
	 * 要傳到模板的數據
	 *
	 * @var array
	 */
	protected $args = [];

	/**
	 * 變量賦值
	 *
	 * @param string | array $key 賦值到模板的key,數組或字符串為數組時批量賦值
	 * @param mixed $val 賦值到模板的值
	 *
	 * @return $this
	 */
	public function assign($key, $val = null)
	{
		if (is_array($key)) {
			$this->args = array_merge($this->args, $key);
		} else {
			$this->args[$key] = $val;
		}
		return $this;
	}

	/**
	 * 引用賦值
	 *
	 * @param string | array $key 賦值到模板的key,數組或字符串為數組時批量賦值
	 * @param mixed $val
	 *
	 * @return $this
	 */
	public function assignByRef($key, &$val = null)
	{
		if (is_array($key)) {
			foreach ($key as $k => &$v) {
				$this->args[$k] = $v;
			}
		} else {
			$this->args[$key] = $val;
		}
		return $this;
	}

	/**
	 * 獲取賦到模板的值
	 *
	 * @param string $key 要獲取的值的key,數組或字符串為數組時批量賦值
	 *
	 * @return mixed
	 */
	public function getValue($key = null)
	{
		if (is_null($key)) {//返回所有
			return $this->args;
		} elseif (isset($this->args[$key])) {
			return $this->args[$key];
		} else {
			return null;
		}
	}
}
