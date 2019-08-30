<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 文件操作類
 * *********************************************************** */

namespace Cml\Vendor;

/**
 * 文件操作類
 *
 * @package Cml\Vendor
 */
class File
{

	/**
	 * 寫文件
	 *
	 * @param string $filename 文件名稱
	 * @param string $content 寫入文件的內容
	 * @param int $type 類型，1=清空文件內容，寫入新內容，2=再內容後街上新內容
	 *
	 * @return bool
	 */
	public static function writeFile($filename, $content, $type = 1)
	{
		if ($type == 1) {
			is_file($filename) && self::delFile($filename); //刪除文件
			self::createFile($filename);
			self::writeFile($filename, $content, 2);
			return true;
		} else {
			if (!is_writable($filename)) return false;
			$handle = fopen($filename, 'a');
			if (!$handle) return false;
			$result = fwrite($handle, $content);
			if (!$result) return false;
			fclose($handle);
			return true;
		}
	}

	/**
	 * 刪除文件
	 *
	 * @param string $filename 文件名稱
	 *
	 * @return bool
	 */
	public static function delFile($filename)
	{
		if (!is_file($filename) || !is_writable($filename)) return true;
		return unlink($filename);
	}

	/**
	 * 創建空文件
	 *
	 * @param string $filename 需要創建的文件
	 *
	 * @return mixed
	 */
	public static function createFile($filename)
	{
		if (is_file($filename)) return false;
		self::createDir(dirname($filename)); //創建目錄
		return file_put_contents($filename, '');
	}

	/**
	 * 創建目錄
	 *
	 * @param string $path 目錄
	 *
	 * @return bool
	 */
	public static function createDir($path)
	{
		if (is_dir($path)) return false;
		self::createDir(dirname($path));
		mkdir($path);
		chmod($path, 0777);
		return true;
	}

	/**
	 * 拷貝一個新文件
	 *
	 * @param string $filename 文件名稱
	 * @param string $newfilename 新文件名稱
	 *
	 * @return bool
	 */
	public static function copyFile($filename, $newfilename)
	{
		if (!is_file($filename) || !is_writable($filename)) return false;
		self::createDir(dirname($newfilename)); //創建目錄
		return copy($filename, $newfilename);
	}

	/**
	 * 移動文件
	 *
	 * @param string $filename 文件名稱
	 * @param string $newfilename 新文件名稱
	 *
	 * @return bool
	 */
	public static function moveFile($filename, $newfilename)
	{
		if (!is_file($filename) || !is_writable($filename)) return false;
		self::createDir(dirname($newfilename)); //創建目錄
		return rename($filename, $newfilename);
	}

	/**
	 * 獲取文件信息
	 *
	 * @param string $filename 文件名稱
	 *
	 * @return bool | array  ['上次訪問時間','inode 修改時間','取得文件修改時間','大小'，'類型']
	 */
	public static function getFileInfo($filename)
	{
		if (!is_file($filename)) {
			return false;
		}
		return [
			'atime' => date("Y-m-d H:i:s", fileatime($filename)),
			'ctime' => date("Y-m-d H:i:s", filectime($filename)),
			'mtime' => date("Y-m-d H:i:s", filemtime($filename)),
			'size' => filesize($filename),
			'type' => filetype($filename)
		];
	}

	/**
	 * 刪除目錄
	 *
	 * @param string $path 目錄
	 *
	 * @return bool
	 */
	public static function delDir($path)
	{
		$succeed = true;
		if (is_dir($path)) {
			$objDir = opendir($path);
			while (false !== ($fileName = readdir($objDir))) {
				if (($fileName != '.') && ($fileName != '..')) {
					chmod("$path/$fileName", 0777);
					if (!is_dir("$path/$fileName")) {
						if (!unlink("$path/$fileName")) {
							$succeed = false;
							break;
						}
					} else {
						self::delDir("$path/$fileName");
					}
				}
			}
			if (!readdir($objDir)) {
				closedir($objDir);
				if (!rmdir($path)) {
					$succeed = false;
				}
			}
		}
		return $succeed;
	}
}
