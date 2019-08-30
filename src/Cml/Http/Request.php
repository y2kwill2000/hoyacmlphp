<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-13 下午5:30
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 請求類
 * *********************************************************** */

namespace Cml\Http;

use Cml\Cml;
use Cml\Config;
use Cml\Log;
use Cml\Route;

/**
 * 請求處理類，獲取用戶請求信息以發起curl請求
 *
 * @package Cml\Http
 */
class Request
{
	/**
	 * 獲取IP地址
	 *
	 * @return string
	 */
	public static function ip()
	{
		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			return strip_tags($_SERVER['HTTP_CLIENT_IP']);
		}
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return strip_tags($_SERVER['HTTP_X_FORWARDED_FOR']);
		}
		if (isset($_SERVER['REMOTE_ADDR'])) {
			return strip_tags($_SERVER['REMOTE_ADDR']);
		}
		return 'unknown';
	}

	/**
	 * 獲取用戶標識
	 *
	 * @return string
	 */
	public static function userAgent()
	{
		return strip_tags($_SERVER['HTTP_USER_AGENT']);
	}

	/**
	 * 獲取帶全參數的url地址
	 *
	 * @param bool $addSufFix 是否添加偽靜態後綴
	 * @param bool $joinParams 是否帶上GET請求參數
	 *
	 * @return string
	 */
	public static function fullUrl($addSufFix = true, $joinParams = true)
	{
		$params = '';
		if ($joinParams) {
			$get = $_GET;
			unset($get[Config::get('var_pathinfo')]);
			$params = http_build_query($get);
			$params && $params = '?' . $params;
		}
		return Request::baseUrl() . '/' . implode('/', Route::getPathInfo()) . ($addSufFix ? Config::get('url_html_suffix') : '') . $params;
	}

	/**
	 * 獲取基本地址
	 *
	 * @param bool $joinPort 是否帶上端口
	 *
	 * @return string
	 */
	public static function baseUrl($joinPort = true)
	{
		$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
		return $protocol . self::host($joinPort);
	}

	/**
	 * 獲取主機名稱
	 *
	 * @param bool $joinPort 是否帶上端口
	 *
	 * @return string
	 */
	public static function host($joinPort = true)
	{
		$host = strip_tags(isset($_SERVER['HTTP_HOST']) ? explode(':', $_SERVER['HTTP_HOST'])[0] : $_SERVER['SERVER_NAME']);
		$joinPort && $host = $host . (in_array($_SERVER['SERVER_PORT'], [80, 443]) ? '' : ':' . $_SERVER['SERVER_PORT']);
		return $host;
	}

	/**
	 * 獲取請求時間
	 *
	 * @return mixed
	 */
	public static function requestTime()
	{
		return $_SERVER['REQUEST_TIME'];
	}

	/**
	 * 判斷是否為手機瀏覽器
	 *
	 * @return bool
	 */
	public static function isMobile()
	{
		if ($_GET['mobile'] === 'yes') {
			setcookie('ismobile', 'yes', 3600);
			return true;
		} elseif ($_GET['mobile'] === 'no') {
			setcookie('ismobile', 'no', 3600);
			return false;
		}

		$cookie = $_COOKIE('ismobile');
		if ($cookie === 'yes') {
			return true;
		} elseif ($cookie === 'no') {
			return false;
		} else {
			$cookie = null;
			static $mobileBrowserList = ['iphone', 'android', 'phone', 'mobile', 'wap', 'netfront', 'java', 'opera mobi', 'opera mini',
				'ucweb', 'windows ce', 'symbian', 'series', 'webos', 'sony', 'blackberry', 'dopod', 'nokia', 'samsung',
				'palmsource', 'xda', 'pieplus', 'meizu', 'midp', 'cldc', 'motorola', 'foma', 'docomo', 'up.browser',
				'up.link', 'blazer', 'helio', 'hosin', 'huawei', 'novarra', 'coolpad', 'webos', 'techfaith', 'palmsource',
				'alcatel', 'amoi', 'ktouch', 'nexian', 'ericsson', 'philips', 'sagem', 'wellcom', 'bunjalloo', 'maui', 'smartphone',
				'iemobile', 'spice', 'bird', 'zte-', 'longcos', 'pantech', 'gionee', 'portalmmm', 'jig browser', 'hiptop',
				'benq', 'haier', '^lct', '320x320', '240x320', '176x220'];
			foreach ($mobileBrowserList as $val) {
				$result = strpos(strtolower($_SERVER['HTTP_USER_AGENT']), $val);
				if (false !== $result) {
					setcookie('ismobile', 'yes', 3600);
					return true;
				}
			}
			setcookie('ismobile', 'no', 3600);
			return false;
		}
	}

	/**
	 * 判斷是否為POST請求
	 *
	 * @return bool
	 */
	public static function isPost()
	{
		return (strtolower(self::getService('REQUEST_METHOD')) == 'post') ? true : false;
	}

	/**
	 * 獲取SERVICE信息
	 *
	 * @param string $name SERVER的鍵值名稱
	 *
	 * @return string
	 */
	public static function getService($name = '')
	{
		if ($name == '') return $_SERVER;
		return (isset($_SERVER[$name])) ? strip_tags($_SERVER[$name]) : '';
	}

	/**
	 * 判斷是否為GET請求
	 *
	 * @return bool
	 */
	public static function isGet()
	{
		return (strtolower(self::getService('REQUEST_METHOD')) == 'get') ? true : false;
	}

	/**
	 * 判斷是否為AJAX請求
	 *
	 * @param bool $checkAccess 是否檢測HTTP_ACCESS頭
	 *
	 * @return bool
	 */
	public static function isAjax($checkAccess = false)
	{
		if (
			self::getService('HTTP_X_REQUESTED_WITH')
			&& strtolower(self::getService('HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest'
		) {
			return true;
		}

		if ($checkAccess) {
			return self::acceptJson();
		}

		return false;
	}

	/**
	 * 判斷請求類型是否為json
	 *
	 * @return bool
	 */
	public static function acceptJson()
	{
		$accept = self::getService('HTTP_ACCEPT');
		if (false !== strpos($accept, 'json') || false !== strpos($accept, 'javascript')) {
			return true;
		}
		return false;
	}

	/**
	 * 判斷是否以cli方式運行
	 *
	 * @return bool
	 */
	public static function isCli()
	{
		return php_sapi_name() === 'cli';
	}

	/**
	 * 獲取POST過來的二進制數據,與手機端交互
	 *
	 * @param bool $formatJson 獲取的數據是否為json並格式化為數組
	 * @param string $jsonField 獲取json格式化為數組的字段多維數組用.分隔  如top.son.son2
	 *
	 * @return bool|mixed|null|string
	 */
	public static function getBinaryData($formatJson = false, $jsonField = '')
	{
		if (isset($GLOBALS['HTTP_RAW_POST_DATA']) && !empty($GLOBALS['HTTP_RAW_POST_DATA'])) {
			$data = $GLOBALS['HTTP_RAW_POST_DATA'];
		} else {
			$data = file_get_contents('php://input');
		}
		if ($formatJson) {
			$data = json_decode($data, true);
			$jsonField && $data = Cml::doteToArr($jsonField, $data);
		}
		return $data;
	}

	/**
	 * 發起curl請求
	 *
	 * @param string $url 要請求的url
	 * @param array $parameter 請求參數
	 * @param array $header header頭信息
	 * @param string $type 請求的數據類型 json/post/file/get/raw
	 * @param int $connectTimeout 請求的連接超時時間默認10s
	 * @param int $execTimeout 等待執行輸出的超時時間默認30s
	 * @param bool $writeLog 是否寫入錯誤日誌
	 * @param null|callable $cusFunc 可自定義調用curl相關參數
	 *
	 * @return bool|mixed
	 */
	public static function curl($url, $parameter = [], $header = [], $type = 'json', $connectTimeout = 10, $execTimeout = 30, $writeLog = false, $cusFunc = null)
	{
		$ssl = substr($url, 0, 8) == "https://" ? true : false;
		$ch = curl_init();
		if ($ssl) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //信任任何證書
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //檢查證書中是否設置域名
		}

		$type = strtolower($type);
		if ($type == 'json' || $type == 'raw') {
			$type == 'json' && ($parameter = json_encode($parameter, JSON_UNESCAPED_UNICODE)) && ($header[] = 'Content-Type: application/json');
			//$queryStr = str_replace(['\/','[]'], ['/','{}'], $queryStr);//兼容
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter);
		} else if ($type == 'post') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameter));
		} else if ($type == 'file') {
			$isOld = substr($parameter['file'], 0, 1) == '@';
			if (function_exists('curl_file_create')) {
				$parameter['file'] = curl_file_create($isOld ? substr($parameter['file'], 1) : $parameter['file'], '');
			} else {
				$isOld || $parameter['file'] = '@' . $parameter['file'];
			}
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter);
		} else {
			$queryStr = '';
			if (is_array($parameter)) {
				foreach ($parameter as $key => $val) {
					$queryStr .= $key . '=' . $val . '&';
				}
				$queryStr = substr($queryStr, 0, -1);
				$queryStr && $url .= '?' . $queryStr;
			}
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $execTimeout);

		if (!empty($header)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}
		is_callable($cusFunc) && $cusFunc($ch);

		$ret = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		if (!$ret || !empty($error)) {
			$writeLog && Log::error('curl-error', [
				'url' => $url,
				'params' => $parameter,
				'error' => $error,
				'ret' => $ret
			]);
			return false;
		} else {
			return $ret;
		}
	}

	/**
	 * 返回操作系統類型
	 *
	 * @return bool true為win false為unix
	 */
	public static function operatingSystem()
	{
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			return true;
		} else {
			return false;
		}
	}
}
