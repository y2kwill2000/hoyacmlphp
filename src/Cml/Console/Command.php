<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-命令抽像類
 * *********************************************************** */

namespace Cml\Console;

use Cml\Console\Format\Format;
use Cml\Console\IO\Output;

/**
 * 控制台命令抽像類
 *
 * @package Cml\Console
 */
abstract class Command
{

	/**
	 * Console實例
	 *
	 * @var Console
	 */
	protected $console;

	/**
	 * Command constructor.
	 *
	 * @param Console $console
	 */
	public function __construct($console)
	{
		$this->console = $console;
	}

	/**
	 * 命令的入口方法
	 *
	 * @param array $args 傳遞給命令的參數
	 * @param array $options 傳遞給命令的選項
	 */
	abstract public function execute(array $args, array $options = []);

	/**
	 * 格式化輸出
	 *
	 * @param string $text 要輸出的內容
	 * @param array $option 格式化選項 @see Format
	 *
	 * @return $this
	 */
	public function write($text, $option = [])
	{
		Output::write($this->format($text, $option));
		return $this;
	}

	/**
	 * 格式化文本
	 *
	 * @param string $text 要格式化的文本
	 * @param array $option 格式化選項 @see Format
	 *
	 * @return string
	 */
	public function format($text, $option = [])
	{
		$format = new Format($option);
		return $format->format($text);
	}

	/**
	 * 格式化輸出
	 *
	 * @param string $text 要輸出的內容
	 * @param array $option 格式化選項 @see Format
	 *
	 * @return $this
	 */
	public function writeln($text, $option = [])
	{
		Output::writeln($this->format($text, $option));
		return $this;
	}
}
