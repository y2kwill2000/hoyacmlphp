<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 cml_error_or_exception服務Whoops實現 使用請先安裝依賴composer require filp/whoops
 * *********************************************************** */

namespace Cml\Service;

use Cml\Cml;
use Cml\Config;
use Cml\Console\IO\Output;
use Cml\Http\Request;
use Cml\Interfaces\ErrorOrException;
use Cml\Lang;
use Cml\Log;
use Cml\View;
use Whoops\Exception\ErrorException;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * cml_error_or_exception服務Whoops實現
 *
 * @package Cml\Service
 */
class Whoops implements ErrorOrException
{
	/**
	 * 致命錯誤捕獲
	 *
	 * @param array $error 錯誤信息
	 */
	public function fatalError(&$error)
	{
		if (Cml::$debug) {
			$run = new Run();
			$run->pushHandler(Request::isCli() ? new PlainTextHandler() : new PrettyPageHandler());
			$run->handleException(new ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']));
		} else {
			//正式環境 只顯示『系統錯誤』並將錯誤信息記錄到日誌
			Log::emergency('fatal_error', [$error]);
			$error = [];
			$error['message'] = Lang::get('_CML_ERROR_');

			if (Request::isCli()) {
				Output::writeException(sprintf("[%s]\n%s", 'Fatal Error', $error['message']));
			} else {
				header('HTTP/1.1 500 Internal Server Error');
				View::getEngine('html')->reset()->assign('error', $error);
				Cml::showSystemTemplate(Config::get('html_exception'));
			}
		}
		exit;
	}

	/**
	 * 自定義異常處理
	 *
	 * @param mixed $e 異常對像
	 */
	public function appException(&$e)
	{
		if (Cml::$debug) {
			$run = new Run();
			$run->pushHandler(Request::isCli() ? new PlainTextHandler() : new PrettyPageHandler());
			$run->handleException($e);
		} else {
			$error = [];
			$error['message'] = $e->getMessage();
			$trace = $e->getTrace();
			$error['files'][0] = $trace[0];

			if (substr($e->getFile(), -20) !== '\Tools\functions.php' || $e->getLine() !== 90) {
				array_unshift($error['files'], ['file' => $e->getFile(), 'line' => $e->getLine(), 'type' => 'throw']);
			}

			//正式環境 只顯示『系統錯誤』並將錯誤信息記錄到日誌
			Log::emergency($error['message'], [$error['files'][0]]);

			$error = [];
			$error['message'] = Lang::get('_CML_ERROR_');

			if (Request::isCli()) {
				\Cml\pd($error);
			} else {
				header('HTTP/1.1 500 Internal Server Error');
				View::getEngine('html')->reset()->assign('error', $error);
				Cml::showSystemTemplate(Config::get('html_exception'));
			}
		}
		exit;
	}
}
