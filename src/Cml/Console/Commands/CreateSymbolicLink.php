<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-靜態文件軟鏈接命令
 * *********************************************************** */

namespace Cml\Console\Commands;

use Cml\Console\Command;
use Cml\Tools\StaticResource;

/**
 * 創建靜態文件資源目錄軟鏈接
 *
 * @package Cml\Console\Commands
 */
class CreateSymbolicLink extends Command
{
	protected $description = "create resource symbolic link command";

	protected $arguments = [
	];

	protected $options = [
		'--root-dir' => 'acquiescent symbolic link will create on project_dir/public. this option can set root dir'
	];

	/**
	 * 命令的入口方法
	 *
	 * @param array $args 傳遞給命令的參數
	 * @param array $options 傳遞給命令的選項
	 */
	public function execute(array $args, array $options = [])
	{
		$rootDir = null;
		if (isset($options['root-dir']) && !empty($options['root-dir'])) {
			$rootDir = $options['root-dir'];
		}
		StaticResource::createSymbolicLink($rootDir);
	}
}
