<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-終止後台進程
 * *********************************************************** */

namespace Cml\Console\Commands\DaemonProcessManage;

use Cml\Console\Command;
use Cml\Tools\Daemon\ProcessManage;

/**
 * 終止後台進程
 *
 * @package Cml\Console\Commands\DaemonProcessManage
 */
class Stop extends Command
{
	protected $description = "stop worker";

	protected $arguments = [
	];

	protected $options = [
	];

	/**
	 * 查看守護進程運行狀態
	 *
	 * @param array $args 傳遞給命令的參數
	 * @param array $options 傳遞給命令的選項
	 *
	 * @throws \InvalidArgumentException
	 */
	public function execute(array $args, array $options = [])
	{
		ProcessManage::stop();
	}
}
