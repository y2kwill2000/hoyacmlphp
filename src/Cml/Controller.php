<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 系統默認控制器類
 * *********************************************************** */

namespace Cml;

use Cml\Http\Request;
use Cml\Http\Response;

/**
 * 框架基礎控制器,所有控制器都要繼承該類
 *
 * @package Cml
 */
class Controller
{

	/**
	 * 當執行的控制器方法返回數組且http請求頭HTTP_ACCEPT為html時。默認渲染的tpl為"控制器名/方法名"
	 * 這邊配置[請求的控制器方法=>對應渲染的模板]則渲染配置的模板。當[請求的控制器方法=>對應渲染的模板為string]自動調用display方法
	 * 當[請求的控制器方法=>對應渲染的模板為 array]自動調用html engine的displayWithLayout方法
	 *
	 * @var array
	 */
	protected $htmlEngineRenderTplArray = [];

	/**
	 * 運行對應的控制器
	 *
	 * @param string $method 要執行的控制器方法
	 *
	 * @return void
	 * @throws \Exception
	 *
	 */
	final public function runAppController($method)
	{
		//檢測csrf跨站攻擊
		Secure::checkCsrf(Config::get('check_csrf'));

		// 關閉GPC過濾 防止數據的正確性受到影響 在db層防注入
		if (get_magic_quotes_gpc()) {
			Secure::stripslashes($_GET);
			Secure::stripslashes($_POST);
			Secure::stripslashes($_COOKIE);
			Secure::stripslashes($_REQUEST); //在程序中對get post cookie的改變不影響 request的值
		}

		//session保存方式自定義
		if (Config::get('session_user')) {
			Session::init();
		} else {
			ini_get('session.auto_start') || session_start(); //自動開啟session
		}

		header('Cache-control: ' . Config::get('http_cache_control'));  // 頁面緩存控制

		//如果有子類中有init()方法 執行Init() eg:做權限控制
		if (method_exists($this, "init")) {
			$this->init();
		}

		//根據動作去找對應的方法
		if (method_exists($this, $method)) {
			try {
				$response = $this->$method();
			} catch (\Exception $e) {
				$this->customHandlerActionException($e);
			}

			if (is_array($response)) {
				if (Request::acceptJson()) {
					View::getEngine('Json')
						->assign($response)
						->display();
				} else {
					$tpl = isset($this->htmlEngineRenderTplArray[$method])
						? $this->htmlEngineRenderTplArray[$method]
						: Cml::getContainer()->make('cml_route')->getControllerName() . '/' . $method;

					call_user_func_array([View::getEngine('Html')->assign($response), is_array($tpl) ? 'displayWithLayout' : 'display'], is_array($tpl) ? $tpl : [$tpl]);
				}
			}
		} elseif (Cml::$debug) {
			Cml::montFor404Page();
			throw new \BadMethodCallException(Lang::get('_ACTION_NOT_FOUND_', $method));
		} else {
			Cml::montFor404Page();
			Response::show404Page();
		}
	}

	/**
	 * 自定義異常處理
	 *
	 * @param \Exception $e
	 *
	 * @throws \Exception
	 */
	protected function customHandlerActionException(\Exception $e)
	{
		throw $e;
	}

	/**
	 * 獲取模型方法
	 *
	 * @return \Cml\Model
	 */
	public function model()
	{
		return Model::getInstance();
	}

	/**
	 * 獲取Lock實例
	 *
	 * @param string|null $useCache 使用的鎖的配置
	 *
	 * @return \Cml\Lock\Redis | \Cml\Lock\Memcache | \Cml\Lock\File | false
	 * @throws \Exception
	 */
	public function locker($useCache = null)
	{
		return Lock::getLocker($useCache);
	}

	/**
	 * 掛載插件鉤子
	 *
	 */
	public function __destruct()
	{
		Plugin::hook('cml.run_controller_end');
	}
}
