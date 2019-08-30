<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-添加一個後台任務
 * *********************************************************** */

namespace Cml\Console\Commands\DaemonProcessManage;

use Cml\Console\Command;
use Cml\Tools\Daemon\ProcessManage;

/**
 * 添加一個後台任務
 *
 * @package Cml\Console\Commands\DaemonProcessManage
 */
class AddTask extends Command
{
	protected $description = "add a task";

	protected $arguments = [
		'action' => 'eg: \\\\web\\\\Controller\\\\DefaultController::index',
		'frequency' => '%d seconds to run at a time; eg: 60'
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

		if (isset($args[1])) {
			$frequency = abs(intval($args[1]));
		} else {
			$frequency = null;
		}
		ProcessManage::addTask($action, $frequency);
	}
}
