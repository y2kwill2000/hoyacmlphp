<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 URL解析類
 * *********************************************************** */

namespace Cml;

use Cml\Http\Request;

/**
 * Url解析類,負責路由及Url的解析
 *
 * @package Cml
 */
class Route
{
	/**
	 * pathIfo數據用來提供給插件做一些其它事情
	 *
	 * @var array
	 */
	private static $pathInfo = [];


	/**
	 * 解析url獲取pathinfo
	 *
	 * @return void
	 */
	public static function parsePathInfo()
	{
		$urlModel = Config::get('url_model');

		$pathInfo = self::$pathInfo;
		if (empty($pathInfo)) {
			$isCli = Request::isCli(); //是否為命令行訪問
			if ($isCli) {
				isset($_SERVER['argv'][1]) && $pathInfo = explode('/', $_SERVER['argv'][1]);
			} else {
				//修正可能由於nginx配置不當導致的子目錄獲取有誤
				if (false !== ($fixScriptName = stristr($_SERVER['SCRIPT_NAME'], '.php', true))) {
					$_SERVER['SCRIPT_NAME'] = $fixScriptName . '.php';
				}

				$urlPathInfoDeper = Config::get('url_pathinfo_depr');
				if ($urlModel === 1 || $urlModel === 2) { //pathInfo模式(含顯示、隱藏index.php兩種)SCRIPT_NAME
					if (isset($_GET[Config::get('var_pathinfo')])) {
						$param = str_replace(Config::get('url_html_suffix'), '', $_GET[Config::get('var_pathinfo')]);
					} else {
						$param = preg_replace('/(.*)\/(.+)\.php(.*)/i', '\\1\\3', preg_replace(
							[
								'/\\' . Config::get('url_html_suffix') . '/',
								'/\&.*/', '/\?.*/'
							],
							'',
							$_SERVER['REQUEST_URI']
						));//這邊替換的結果是帶index.php的情況。不帶index.php在以下處理
						$scriptName = dirname($_SERVER['SCRIPT_NAME']);
						if ($scriptName && $scriptName != '/') {//假如項目在子目錄這邊去除子目錄含模式1和模式2兩種情況(偽靜態到子目錄)
							$param = substr($param, strpos($param, $scriptName) + strlen($scriptName));//之所以要strpos是因為子目錄或請求string裡可能會有多個/而SCRIPT_NAME裡只會有1個
						}
					}
					$param = trim($param, '/' . $urlPathInfoDeper);
				} elseif ($urlModel === 3 && isset($_GET[Config::get('var_pathinfo')])) {//兼容模式
					$urlString = $_GET[Config::get('var_pathinfo')];
					unset($_GET[Config::get('var_pathinfo')]);
					$param = trim(str_replace(
						Config::get('url_html_suffix'),
						'',
						ltrim($urlString, '/')
					), $urlPathInfoDeper);
				}

				$pathInfo = explode($urlPathInfoDeper, $param);
			}
		}

		isset($pathInfo[0]) && empty($pathInfo[0]) && $pathInfo = ['/'];

		self::$pathInfo = $pathInfo;

		Plugin::hook('cml.after_parse_path_info');
	}

	/**
	 * 增加get訪問方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string|array $action 執行的操作
	 *
	 * @return void
	 */
	public static function get($pattern, $action)
	{
		Cml::getContainer()->make('cml_route')->get($pattern, $action);
	}

	/**
	 * 增加post訪問方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string|array $action 執行的操作
	 *
	 * @return void
	 */
	public static function post($pattern, $action)
	{
		Cml::getContainer()->make('cml_route')->post($pattern, $action);
	}

	/**
	 * 增加put訪問方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string|array $action 執行的操作
	 *
	 * @return void
	 */
	public static function put($pattern, $action)
	{
		Cml::getContainer()->make('cml_route')->put($pattern, $action);
	}

	/**
	 * 增加patch訪問方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string|array $action 執行的操作
	 *
	 * @return void
	 */
	public static function patch($pattern, $action)
	{
		Cml::getContainer()->make('cml_route')->patch($pattern, $action);
	}

	/**
	 * 增加delete訪問方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string|array $action 執行的操作
	 *
	 * @return void
	 */
	public static function delete($pattern, $action)
	{
		Cml::getContainer()->make('cml_route')->delete($pattern, $action);
	}

	/**
	 * 增加options訪問方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string|array $action 執行的操作
	 *
	 * @return void
	 */
	public static function options($pattern, $action)
	{
		Cml::getContainer()->make('cml_route')->options($pattern, $action);
	}

	/**
	 * 增加任意訪問方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string|array $action 執行的操作
	 *
	 * @return void
	 */
	public static function any($pattern, $action)
	{
		Cml::getContainer()->make('cml_route')->any($pattern, $action);
	}

	/**
	 * 增加REST方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string|array $action 執行的操作
	 *
	 * @return void
	 */
	public static function rest($pattern, $action)
	{
		Cml::getContainer()->make('cml_route')->rest($pattern, $action);
	}

	/**
	 * 分組路由
	 *
	 * @param string $namespace 分組名
	 * @param callable $func 閉包
	 */
	public static function group($namespace, callable $func)
	{
		Cml::getContainer()->make('cml_route')->group($namespace, $func);
	}

	/**
	 * 獲取解析後的pathInfo信息
	 *
	 * @return array
	 */
	public static function getPathInfo()
	{
		return self::$pathInfo;
	}

	/**
	 * 設置pathInfo信息
	 *
	 * @param array $pathInfo
	 *
	 * @return array
	 */
	public static function setPathInfo($pathInfo)
	{
		return self::$pathInfo = $pathInfo;
	}

	/**
	 * 修改解析得到的請求信息 含應用名、控制器、操作
	 *
	 * @param string|array $key path|controller|action|root
	 * @param string $val
	 *
	 * @return void
	 */
	public static function setUrlParams($key, $val)
	{
		Cml::getContainer()->make('cml_route')->setUrlParams($key, $val);
	}

	/**
	 * 訪問Cml::getContainer()->make('cml_route')中其餘方法
	 *
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public static function __callStatic($name, $arguments)
	{
		return call_user_func_array([Cml::getContainer()->make('cml_route'), $name], $arguments);
	}

	/**
	 * 載入應用單獨的路由
	 *
	 * @param string $app 應用名稱
	 * @param string $inConfigDir 配置文件是否在Config目錄中
	 */
	public static function loadAppRoute($app = 'web', $inConfigDir = true)
	{
		static $loaded = [];
		if (isset($loaded[$app])) {
			return;
		}
		$path = $app . DIRECTORY_SEPARATOR . ($inConfigDir ? Cml::getApplicationDir('app_config_path_name') . DIRECTORY_SEPARATOR : '') . 'route.php';
		$appRoute = Cml::getApplicationDir('apps_path') . DIRECTORY_SEPARATOR . $path;
		if (!is_file($appRoute)) {
			throw new \InvalidArgumentException(Lang::get('_NOT_FOUND_', $path));
		}

		$loaded[$app] = 1;
		Cml::requireFile($appRoute);
	}

	/**
	 * 執行閉包路由
	 *
	 * @param callable $call 閉包
	 * @param string $route 路由string
	 */
	public static function executeCallableRoute(callable $call, $route = '')
	{
		call_user_func($call);
		Cml::$debug && Debug::addTipInfo(Lang::get('_CML_EXECUTION_ROUTE_IS_', "callable route:{{$route}}", Config::get('url_model')));
		Cml::cmlStop();
	}
}
