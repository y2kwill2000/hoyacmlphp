<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-11-03 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行驅動抽像接口
 * *********************************************************** */

namespace Cml\Interfaces;

/**
 *  命令行驅動抽像接口
 *
 * @package Cml\Interfaces
 */
interface Console
{
	/**
	 * 構造函數
	 *
	 * @param array $commands
	 */
	public function __construct(array $commands = []);

	/**
	 * 批量添加命令
	 *
	 * @param array $commands 命令列表
	 * @return $this
	 */
	public function addCommands(array $commands);

	/**
	 * 註冊一個命令
	 *
	 * @param string $class 類名
	 * @param null $alias 命令別名
	 *
	 * @return $this
	 */
	public function addCommand($class, $alias = null);

	/**
	 * 判斷是否有無命令
	 *
	 * @param string $name 命令的別名
	 *
	 * @return bool
	 */
	public function hasCommand($name);

	/**
	 * 獲取某個命令
	 *
	 * @param string $name 命令的別名
	 *
	 * @return mixed
	 */
	public function getCommand($name);

	/**
	 * 獲取所有命令列表
	 *
	 * @return array
	 */
	public function getCommands();

	/**
	 * 運行命令
	 *
	 * @param array|null $argv
	 *
	 * @return mixed
	 */
	public function run(array $argv = null);
}
