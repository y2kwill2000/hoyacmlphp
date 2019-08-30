<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 異常、錯誤捕獲 使用第三方錯誤捕獲插件 必須封裝實現本接口
 * *********************************************************** */

namespace Cml;

use Cml\Console\IO\Output;
use Cml\Http\Request;
use \Cml\Interfaces\ErrorOrException as ErrorOrExceptionInterface;

class ErrorOrException implements ErrorOrExceptionInterface
{
	/**
	 * 致命錯誤捕獲
	 *
	 * @param array $error 錯誤信息
	 *
	 */
	public function fatalError(&$error)
	{
		if (!Cml::$debug) {
			//正式環境 只顯示『系統錯誤』並將錯誤信息記錄到日誌
			Log::emergency('fatal_error', [$error]);
			$error = [];
			$error['message'] = Lang::get('_CML_ERROR_');
		} else {
			$error['exception'] = 'Fatal Error';
			$error['files'][0] = [
				'file' => $error['file'],
				'line' => $error['line']
			];
		}

		if (Request::isCli()) {
			Output::writeException(sprintf("%s\n[%s]\n%s", isset($error['files']) ? implode($error['files'][0], ':') : '', 'Fatal Error', $error['message']));
		} else {
			header('HTTP/1.1 500 Internal Server Error');
			View::getEngine('html')->reset()->assign('error', $error);
			Cml::showSystemTemplate(Config::get('html_exception'));
		}
	}

	/**
	 * 自定義異常處理
	 *
	 * @param mixed $e 異常對像
	 */
	public function appException(&$e)
	{
		$error = [];
		$exceptionClass = new \ReflectionClass($e);
		$error['exception'] = '\\' . $exceptionClass->name;
		$error['message'] = $e->getMessage();
		$trace = $e->getTrace();
		foreach ($trace as $key => $val) {
			$error['files'][$key] = $val;
		}

		if (substr($e->getFile(), -20) !== '\Tools\functions.php' || $e->getLine() !== 90) {
			array_unshift($error['files'], ['file' => $e->getFile(), 'line' => $e->getLine(), 'type' => 'throw']);
		}

		if (!Cml::$debug) {
			//正式環境 只顯示『系統錯誤』並將錯誤信息記錄到日誌
			Log::emergency($error['message'], [$error['files'][0]]);

			$error = [];
			$error['message'] = Lang::get('_CML_ERROR_');
		}

		if (Request::isCli()) {
			Output::writeException(sprintf("%s\n[%s]\n%s", isset($error['files']) ? implode($error['files'][0], ':') : '', get_class($e), $error['message']));
		} else {
			header('HTTP/1.1 500 Internal Server Error');
			View::getEngine('html')->reset()->assign('error', $error);
			Cml::showSystemTemplate(Config::get('html_exception'));
		}
	}
}
