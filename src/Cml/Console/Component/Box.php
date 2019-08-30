<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-121 下午19:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-輸出內容格式化為長方形框組件
 * *********************************************************** */

namespace Cml\Console\Component;

/**
 * 命令行工具-輸出內容格式化為長方形框組件
 *
 * @package Cml\Console\Component
 */
class Box
{
	/**
	 * 要顯示的文本
	 *
	 * @var string
	 */
	protected $text;

	/**
	 * 外圍標識符
	 *
	 * @var string
	 */
	protected $periphery;

	/**
	 * 間隔
	 *
	 * @var int
	 */
	protected $padding;

	/**
	 * Box constructor.
	 *
	 * @param string $text 要處理的文本
	 * @param string $periphery 外圍字符
	 * @param int $padding 內容與左右邊框的距離
	 */
	public function __construct($text = '', $periphery = '*', $padding = 2)
	{
		$this->text = $text;
		$this->periphery = $periphery;
		$this->padding = $padding;
	}

	/**
	 * 渲染文本並返回
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * 渲染文本並返回
	 *
	 * @return string
	 */
	public function render()
	{
		$lines = explode("\n", $this->text);
		$maxWidth = 0;
		foreach ($lines as $line) {
			if (strlen($line) > $maxWidth) {
				$maxWidth = strlen($line);
			}
		}

		$maxWidth += $this->padding * 2 + 2;
		$output = str_repeat($this->periphery, $maxWidth) . "\n";//first line
		foreach ($lines as $line) {
			$space = $maxWidth - (strlen($line) + 2 + $this->padding * 2);
			$output .= $this->periphery . str_repeat(' ', $this->padding) . $line . str_repeat(' ', $space + $this->padding) . $this->periphery . "\n";
		}
		$output .= str_repeat($this->periphery, $maxWidth);
		return $output;
	}
}
