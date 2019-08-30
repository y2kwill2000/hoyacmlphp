<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-輸出管道
 * *********************************************************** */

namespace Cml\Console\IO;

use Cml\Console\Format\Colour;
use Cml\Console\Format\Format;
use Cml\Console\Component\Box;

/**
 * 命令行工具-輸出管道
 *
 * @package Cml\Console\IO
 */
class Output
{
	/**
	 * 輸出異常錯誤信息
	 *
	 * @param mixed $e
	 */
	public static function writeException($e)
	{
		if ($e instanceof \Exception) {
			$text = sprintf("%s\n[%s]\n%s", $e->getFile() . ':' . $e->getLine(), get_class($e), $e->getMessage());
		} else {
			$text = $e;
		}

		$box = new Box($text, '*');
		$out = Colour::colour($box, [Colour::WHITE, 0], Colour::RED);
		$format = new Format(['indent' => 2]);
		$out = $format->format($out);
		self::writeln($out, STDERR);
	}

	/**
	 * 輸出內容並換行
	 *
	 * @param string $text
	 * @param mixed $pipe
	 */
	public static function writeln($text = '', $pipe = STDOUT)
	{
		self::write("$text\n", $pipe);
	}

	/**
	 * 輸出內容
	 *
	 * @param string $text
	 * @param mixed $pipe
	 */
	public static function write($text, $pipe = STDOUT)
	{
		fwrite($pipe, $text);
	}
}
