<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-13 下午3:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 請求響應類
 * *********************************************************** */

namespace Cml\Http;

use Cml\Cml;
use Cml\Config;
use Cml\Lang;
use Cml\Secure;

/**
 * 請求響應類,負責url跳轉、url組裝、http狀態碼響應等
 *
 * @package Cml\Http
 */
class Response
{
	/**
	 * 重定向
	 *
	 * @param string $url 重寫向的目標地址
	 * @param int $time 等待時間
	 *
	 * @return void
	 */
	public static function redirect($url, $time = 0)
	{
		strpos($url, 'http') === false && $url = self::url($url, 0);
		if (!headers_sent()) {
			($time === 0) && header("Location: {$url}");
			header("refresh:{$time};url={$url}");
			exit();
		} else {
			exit("<meta http-equiv='Refresh' content='{$time};URL={$url}'>");
		}
	}

	/**
	 * URL組裝 支持不同URL模式
	 * eg: \Cml\Http\Response::url('Home/Blog/cate/id/1')
	 *
	 * @param string $url URL表達式 路徑/控制器/操作/參數1/參數1值/.....
	 * @param bool $echo 是否輸出  true輸出 false return
	 *
	 * @return string
	 */
	public static function url($url = '', $echo = true)
	{
		$return = '';
		// 解析URL
		if (empty($url)) {
			throw new \InvalidArgumentException(Lang::get('_NOT_ALLOW_EMPTY_', 'url')); //'U方法參數出錯'
		}
		// URL組裝
		$delimiter = Config::get('url_pathinfo_depr');
		$url = ltrim($url, '/');
		$url = implode($delimiter, explode('/', $url));

		if (Config::get('url_model') == 1) {
			$return = $_SERVER['SCRIPT_NAME'] . '/' . $url;
		} elseif (Config::get('url_model') == 2) {
			$return = Cml::getContainer()->make('cml_route')->getSubDirName() . $url;
		} elseif (Config::get('url_model') == 3) {
			$return = $_SERVER['SCRIPT_NAME'] . '?' . Config::get('var_pathinfo') . '=/' . $url;
		}

		$return .= (Config::get('url_model') == 2 ? Config::get('url_html_suffix') : '');

		$return = Secure::filterScript($return);
		if ($echo) {
			echo $return;
			return '';
		} else {
			return $return;
		}
	}

	/**
	 * 顯示404頁面
	 *
	 * @param string $tpl 模板路徑
	 *
	 * @return void
	 */
	public static function show404Page($tpl = null)
	{
		self::sendHttpStatus(404);
		is_null($tpl) && $tpl = Config::get('404_page');
		is_file($tpl) && Cml::requireFile($tpl);
		exit();
	}

	/**
	 * 發送http狀態碼相對應的信息
	 *
	 * @param int $code 要設置的http code
	 */
	public static function sendHttpStatus($code)
	{
		static $_status = [
			// Informational 1xx
			100 => 'Continue',
			101 => 'Switching Protocols',

			// Success 2xx
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',

			// Redirection 3xx
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',  // 1.1
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			// 306 is deprecated but reserved
			307 => 'Temporary Redirect',

			// Client Error 4xx
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',

			// Server Error 5xx
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			509 => 'Bandwidth Limit Exceeded'
		];
		if (isset($_status[$code])) {
			header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
		}
	}

	/**
	 * URL組裝(帶域名端口) 支持不同URL模式
	 * eg: \Cml\Http\Response::fullUrl('Home/Blog/cate/id/1')
	 *
	 * @param string $url URL表達式 路徑/控制器/操作/參數1/參數1值/.....
	 * @param bool $echo 是否輸出  true輸出 false return
	 *
	 * @return string
	 */
	public static function fullUrl($url = '', $echo = true)
	{
		$url = Request::baseUrl() . self::url($url, false);
		if ($echo) {
			echo $url;
			return '';
		} else {
			return $url;
		}
	}

	/**
	 * 通過後綴名輸出contentType並返回
	 *
	 * @param string $subFix
	 *
	 * @return string
	 */
	public static function sendContentTypeBySubFix($subFix = 'html')
	{
		$mines = [
			'html' => 'text/html',
			'htm' => 'text/html',
			'shtml' => 'text/html',
			'css' => 'text/css',
			'xml' => 'text/xml',
			'gif' => 'image/gif',
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'js' => 'application/x-javascript',
			'atom' => 'application/atom+xml',
			'rss' => 'application/rss+xml',
			'mml' => 'text/mathml',
			'txt' => 'text/plain',
			'wml' => 'text/vnd.wap.wml',
			'jad' => 'text/vnd.sun.j2me.app-descriptor',
			'htc' => 'text/x-component',
			'png' => 'image/png',
			'tiff' => 'image/tiff',
			'tif' => 'image/tiff',
			'wbmp' => 'image/vnd.wap.wbmp',
			'ico' => 'image/x-icon',
			'jng' => 'image/x-jng',
			'bmp' => 'image/x-ms-bmp',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',
			'webp' => 'image/webp',
			'doc' => 'application/msword',
			'pdf' => 'application/pdf',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',
			'rar' => 'application/x-rar-compressed',
			'swf' => 'application/x-shockwave-flash',
			'zip' => 'application/xhtml+xml',
			'xhtml' => 'application/xhtml+xml',
			'mp3' => 'audio/mpeg',
			'ogg' => 'audio/ogg',
			'm4a' => 'audio/ogg',
			'mp4' => 'video/mp4 ',
			'wmv' => 'video/x-ms-wmv',
			'avi' => 'video/x-msvideo',
			'woff' => 'application/font-woff',
			'eot' => 'application/vnd.ms-fontobject'
		];
		$mine = isset($mines[$subFix]) ? $mines[$subFix] : 'text/html';
		header("Content-Type:{$mine};charset=utf-8");
		return $mine;
	}
}
