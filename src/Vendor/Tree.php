<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 無限級分類
 * *********************************************************** */

namespace Cml\Vendor;

/**
 * 無限級分類處理
 *
 * @package Cml\Vendor
 */
class Tree
{
	/**
	 * @var array 默認配置
	 */
	private static $config = [
		'pid' => 'pid', //低級id字段名
		'id' => 'id', //主鍵字段名
		'name' => 'name' //名稱字段名
	];

	/**
	 * @var int 當前為第幾層樹
	 */
	private static $times = 0;

	/**
	 * 修改配置
	 *
	 * @param array $config ['pid'=>'', 'id' => '', 'name' =>'name']
	 *
	 * @return mixed
	 */
	public static function setConfig($config = [])
	{
		if (!is_array($config)) {
			return false;
		}
		self::$config = array_merge(self::$config, $config);
		return true;
	}

	/**
	 * 獲取樹--返回格式化後的數據
	 *
	 * @param array $list 數據列表數組
	 * @param int $pid 初始化樹時候，代表獲取pid下的所有子集
	 * @param int $selectedId 選中的ID值
	 * @param string $str 組裝後的字串
	 * @param string|array $prefix 前綴 如：|--表示每一層都會以|--分隔、['    ', '|--']表示只有最後一層是用|--其餘層級用空格縮進
	 * @param string $selectedString 選中時的字串 如selected checked
	 * @param int $returnType 1為返回字符串 2為返回數組
	 *
	 * @return string|array
	 */
	public static function getTree(
		$list,
		$pid = 0,
		$selectedId = 0,
		$str = "<option value='\$id' \$selected>\$tempPrefix\$name</option>",
		$prefix = '|--',
		$selectedString = 'selected',
		$returnType = 1
	)
	{
		$string = $returnType === 1 ? '' : [];
		if (!is_array($list)) { //遍歷結束
			self::$times = 0;
			return $string;
		}
		$tempPrefix = '';
		self::$times += 1;
		for ($i = 0; $i < self::$times; $i++) {
			$tempPrefix .= is_array($prefix) ? ($i + 1 == self::$times ? $prefix[1] : $prefix[0]) : $prefix;
		}

		foreach ($list as $v) {
			if ($v[self::$config['pid']] == $pid) { //獲取pid下的子集
				$id = $v[self::$config['id']]; //主鍵id
				$name = $v[self::$config['name']]; //顯示的名稱
				$selected = ($id == $selectedId) ? $selectedString : ''; //被選中的id
				$tempCode = '';
				eval("\$tempCode = \"{$str}\";");//轉化
				if ($returnType === 1) {
					$string .= $tempCode;
					$string .= self::getTree($list, $id, $selectedId, $str, $prefix, $selectedString, $returnType);
				} else {
					$string[$id] = $tempCode;
					$sub = self::getTree($list, $id, $selectedId, $str, $prefix, $selectedString, $returnType);
					$sub && $string = $string + $sub;
				}
			}
		}
		self::$times--;

		return $string;
	}

	/**
	 * 獲取樹--返回數組
	 *
	 * @param array $list 數據列表數組
	 * @param int $pid 初始化樹時候，代表獲取pid下的所有子集
	 * @param string $sonNodeName 子級的key
	 *
	 * @return string|array
	 */
	public static function getTreeNoFormat(&$list, $pid = 0, $sonNodeName = 'sonNode')
	{
		$res = [];
		if (!is_array($list)) { //遍歷結束
			return $res;
		}

		foreach ($list as $v) {
			if (isset($v[self::$config['pid']]) && $v[self::$config['pid']] == $pid) { //獲取pid下的子集
				$v[$sonNodeName] = self::getTreeNoFormat($list, $v[self::$config['id']], $sonNodeName);
				$res[$v[self::$config['id']]] = $v;
			}
		}
		return $res;
	}

	/**
	 * 獲取子集
	 *
	 * @param array $list 樹的數組
	 * @param int $id 父類ID
	 *
	 * @return string|array
	 */
	public static function getChild($list, $id)
	{
		if (!is_array($list)) return [];
		$temp = [];
		foreach ($list as $v) {
			if ($v[self::$config['pid']] == $id) {
				$temp[] = $v;
			}
		}
		return $temp;
	}

	/**
	 * 獲取父集
	 *
	 * @param array $list 樹的數組
	 * @param int $id 子集ID
	 *
	 * @return string|array
	 */
	public static function getParent($list, $id)
	{
		if (!is_array($list)) return [];
		$temp = [];
		foreach ($list as $v) {
			$temp[$v[self::$config['id']]] = $v;
		}
		$parentid = $temp[$id][self::$config['pid']];
		return $temp[$parentid];
	}
}
