<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 數據庫遷移命令
 * 修改自https://github.com/robmorgan/phinx/tree/0.6.x-dev/src/Phinx/Console/Command
 * *********************************************************** */

namespace Cml\Console\Commands\Migrate;

use Cml\Console\IO\Output;

/**
 * 數據庫遷移-獲取遷移信息
 *
 * @package Cml\Console\Commands\Migrate
 */
class Status extends AbstractCommand
{
	protected $description = "show migration status";

	protected $arguments = [
		'name' => 'What is the name of the seeder?',
	];

	protected $options = [
		'--f=xxx | --format=xxx' => 'The output format: text or json. Defaults to text.',
		'--env=xxx' => "the environment [cli, product, development] load accordingly config",
	];

	protected $help = <<<EOT
The status command prints a list of all migrations, along with their current status

php index.php migrate:status
php index.php migrate:status --f=json
EOT;

	/**
	 * 獲取遷移信息
	 *
	 * @param array $args 參數
	 * @param array $options 選項
	 */
	public function execute(array $args, array $options = [])
	{
		$this->bootstrap($args, $options);

		$format = isset($options['format']) ? $options['format'] : $options['f'];

		if (null !== $format) {
			Output::writeln('using format ' . $format);
		}

		$this->getManager()->printStatus($format);
	}
}
