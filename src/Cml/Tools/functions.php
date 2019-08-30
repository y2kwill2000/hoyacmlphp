<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架公用函數庫
 * *********************************************************** */

namespace Cml;

use \Cml\dBug as outDebug;
use  \PhpConsole\Connector as PhpConsoleConnector;

/**
 * 友好的變量輸出
 *
 * @param mixed $var 變量
 * @param int $getArgs 獲取要打印的值
 *
 * @return string
 */
function dump($var, $getArgs = 0)
{
	if (Cml::$debug) {
		new outDebug($var);    //deBug模式直接輸出
	} else {
		static $args = [];
		if (($getArgs == 1)) return $args;
		$args[] = $var;//輸出到瀏覽器控制台
	}
	return '';
}

/**
 * 打印數據到chrome控制台
 *
 * @param mixed $var 要打印的變量
 * @param string $tag 標籤
 *
 * @return void
 */
function dumpUsePHPConsole($var, $tag = 'debug')
{
	if (!Config::get('dump_use_php_console')) {
		throw new \BadFunctionCallException(Lang::get('_NOT_OPEN_', 'dump_use_php_console'));
	}
	static $connector = false;
	if ($connector === false) {
		$connector = PhpConsoleConnector::getInstance();
		$password = Config::get('php_console_password');
		$password && $connector->setPassword($password);
	}
	$connector->getDebugDispatcher()->dispatchDebug($var, $tag);
}

/**
 * 友好的變量輸出並且終止程序(只在調試模式下才終止程序)
 *
 * @param mixed $var 變量
 *
 * @return void|string
 */
function dd($var)
{
	dump($var);
	Cml::$debug && exit();
}


/**
 * print_r && exit
 *
 * @param mixed $var
 */
function pd($var)
{
	print_r($var);
	exit();
}

/**
 * 自定義異常處理
 *
 * @param string $msg 異常消息
 * @param integer $code 異常代碼 默認為0
 *
 * @throws \Exception
 */
function throwException($msg, $code = 0)
{
	throw new \Exception($msg, $code);
}

/**
 * 快速文件數據讀取和保存 針對簡單類型數據 字符串、數組
 *
 * @param string $name 緩存名稱
 * @param mixed $value 緩存值
 * @param string $path 緩存路徑
 *
 * @return mixed
 */
function simpleFileCache($name, $value = '', $path = null)
{
	is_null($path) && $path = Cml::getApplicationDir('global_store_path') . DIRECTORY_SEPARATOR . 'Data';
	static $_cache = [];
	$filename = $path . '/' . $name . '.php';
	if ($value !== '') {
		if (is_null($value)) {
			// 刪除緩存
			return false !== @unlink($filename);
		} else if (is_array($value)) {
			// 緩存數據
			$dir = dirname($filename);
			// 目錄不存在則創建
			is_dir($dir) || mkdir($dir, 0700, true);
			$_cache[$name] = $value;
			return file_put_contents($filename, "<?php\treturn " . var_export($value, true) . ";?>", LOCK_EX);
		} else {
			return false;
		}
	}
	if (isset($_cache[$name])) return $_cache[$name];
	// 獲取緩存數據
	if (is_file($filename)) {
		$value = Cml::requireFile($filename);
		$_cache[$name] = $value;
	} else {
		$value = false;
	}
	return $value;
}

/**
 * 生成友好的時間格式
 *
 * @param $from
 *
 * @return bool|string
 */
function friendlyDate($from)
{
	static $now = NULL;
	$now == NULL && $now = time();
	!is_numeric($from) && $from = strtotime($from);
	$seconds = $now - $from;
	$minutes = floor($seconds / 60);
	$hours = floor($seconds / 3600);
	$day = round((strtotime(date('Y-m-d', $now)) - strtotime(date('Y-m-d', $from))) / 86400);
	if ($seconds == 0) {
		return Lang::get('friendly date 0', '剛剛');//語言包配置: 'friendly date 0' => '剛剛'
	}
	if (($seconds >= 0) && ($seconds <= 60)) {
		return Lang::get('friendly date 1', ['seconds' => $seconds]) ?: "{$seconds}秒前";//語言包配置: 'friendly date 1' => '{seconds}秒前'
	}
	if (($minutes >= 0) && ($minutes <= 60)) {
		return Lang::get('friendly date 2', ['minutes' => $minutes]) ?: "{$minutes}分鐘前";//語言包配置: 'friendly date 2' => '{minutes}分鐘前'
	}
	if (($hours >= 0) && ($hours <= 24)) {
		return Lang::get('friendly date 3', ['hours' => $hours]) ?: "{$hours}小時前";//語言包配置: 'friendly date 3' => '{hours}小時前'
	}
	if ((date('Y') - date('Y', $from)) > 0) {
		return date('Y-m-d', $from);
	}

	switch ($day) {
		case 0:
			return date(Lang::get('friendly date 4', '今天H:i'), $from);
			break;
		case 1:
			return date(Lang::get('friendly date 5', '昨天H:i'), $from);
			break;
		default:
			return Lang::get('friendly date 6', ['day' => $day]) ?: "{$day}天前";//語言包配置: 'friendly date 6' => '{day}天前'
	}
}

/**
 * 生成唯一id
 *
 * @return string
 */
function createUnique()
{
	$data = $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'] . Cml::$nowMicroTime . rand();
	return sha1($data);
}

/**
 * 駝峰轉成下劃線
 *
 * @param string $str
 *
 * @return string
 */
function humpToLine($str)
{
	$str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
		return '_' . strtolower($matches[0]);
	}, $str);
	return $str;
}

/**
 * 下劃線轉駝峰
 *
 * @param string $value
 *
 * @return string
 */
function studlyCase($value)
{
	return str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $value)));
}

/**
 * 過濾數組的值.
 *
 * @param array $array 要處理的數組
 * @param array $field 要包含/要排除的字段
 * @param int $type 1 只包含 0排除
 *
 * @return array
 */
function filterArrayValue(array &$array, array $field = [], $type = 1)
{
	foreach ($array as $key => $item) {
		if ($type == 1) {
			if (!in_array($key, $field)) {
				unset($array[$key]);
			}
		} else {
			if (in_array($key, $field)) {
				unset($array[$key]);
			}
		}
	}
	return $array;
}

/**
 * 將n級的關聯數組格式化為索引數組-經常用於is tree插件
 *
 * @param array $array 待處理的數組
 * @param string $childrenKey 子極的key
 * @param array|callable $push 要額外添加的項
 *
 * @return array
 */
function arrayAssocKeyToNumber(array &$array, $childrenKey = 'children', $push = [])
{
	$array = array_values($array);
	foreach ($array as &$item) {
		if (is_callable($push)) {
			$pushData = call_user_func($push, $item);
		} else {
			$pushData = $push;
		}
		$pushData && $item = array_merge($item, $pushData);
		if (isset($item[$childrenKey]) && $item[$childrenKey]) {
			$item[$childrenKey] = arrayAssocKeyToNumber($item[$childrenKey], $childrenKey, $push);
		}
	}
	return $array;
}
