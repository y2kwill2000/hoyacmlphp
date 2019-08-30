<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Html 擴展類 靜態頁面生成
 * *********************************************************** */

namespace Cml\Vendor;

use Cml\Cml;


/**
 * Html 擴展類 靜態頁面生成
 *
 * @package Cml\Vendor
 */
class Html
{

	private $htmlPath = 'data/html/'; //靜態頁面目錄
	private $key; //生成的HTML靜態頁面對應的KEY值
	private $ismd5 = false; //生成的文件名是否MD5加密

	/**
	 * 靜態頁面-開啟ob_start(),打開緩衝區
	 *
	 * @return bool
	 */
	public function start()
	{
		return ob_start();
	}

	/**
	 * 靜態頁面-生成靜態頁面，$key值是生成頁面的唯一標識符
	 *
	 * @param string $key 靜態頁面標識符，可以用id代替
	 *
	 * @return bool
	 */
	public function end($key)
	{
		$this->key = $key;
		$this->html(); //生成HTML文件
		return ob_end_clean(); //清空緩衝
	}

	/**
	 * 靜態頁面-生成靜態頁面
	 *
	 * @return bool
	 */
	private function html()
	{
		$filename = $this->getFilename($this->key);
		if (!$filename) return false;
		return @file_put_contents($filename, ob_get_contents(), LOCK_EX);
	}

	/**
	 * 靜態頁面-靜態頁面文件
	 *
	 * @param string $key 靜態頁面標識符，可以用id代替
	 *
	 * @return string
	 */
	private function getFilename($key)
	{
		$filename = ($this->ismd5 == true) ? md5($key) : $key;
		if (!is_dir($this->htmlPath)) return false;
		return $this->htmlPath . '/' . $filename . '.htm';
	}

	/**
	 * 靜態頁面-獲取靜態頁面
	 *
	 * @param string $key 靜態頁面標識符，可以用id代替
	 *
	 * @return  bool
	 */
	public function get($key)
	{
		$filename = $this->getFilename($key);
		if (!$filename || !is_file($filename)) return false;
		Cml::requireFile($filename);
		return true;
	}
}
