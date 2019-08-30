<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-刪除一個後台任務
 * *********************************************************** */

namespace Cml\Console\Commands\DaemonProcessManage;

use Cml\Console\Command;
use Cml\Tools\Daemon\ProcessManage;

/**
 * 刪除一個後台任務
 *
 * @package Cml\Console\Commands\DaemonProcessManage
 */
class RmTask extends Command
{
	protected $description = "remove a task";

	protected $arguments = [
		'action' => 'eg: \\\\web\\\\Controller\\\\DefaultController::index',
	];

	protected $options = [
	];

	/**
	 * 添加一個後台任務
	 *
	 * @param array $args 傳遞給命令的參數
	 * @param array $options 傳遞給命令的選項
	 *
	 * @throws \InvalidArgumentException
	 */
	public function execute(array $args, array $options = [])
	{
		if (!isset($args[0])) {
			throw new \InvalidArgumentException('arg action must be input');
		}
		$action = explode('::', $args[0]);
		if (!class_exists($action[0])) {
			throw new \InvalidArgumentException('action not not found!');
		}

		ProcessManage::rmTask($action);
	}
}
