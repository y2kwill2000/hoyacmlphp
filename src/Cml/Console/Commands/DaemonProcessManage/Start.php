<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-啟動守護進程
 * *********************************************************** */

namespace Cml\Console\Commands\DaemonProcessManage;

use Cml\Console\Command;
use Cml\Tools\Daemon\ProcessManage;

/**
 * 啟動守護進程
 *
 * @package Cml\Console\Commands\DaemonProcessManage
 */
class Start extends Command
{
	protected $description = "start daemon process";

	protected $arguments = [
	];

	protected $options = [
	];

	/**
	 * 啟動守護進程
	 *
	 * @param array $args 傳遞給命令的參數
	 * @param array $options 傳遞給命令的選項
	 */
	public function execute(array $args, array $options = [])
	{
		ProcessManage::start();
	}
}
