<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 項目基類
 * *********************************************************** */

namespace Cml;

use Cml\Exception\ControllerNotFoundException;
use Cml\Http\Request;
use Cml\Http\Response;

/**
 * 框架基礎類,負責初始化應用的一系列工作,如配置初始化、語言包載入、錯誤異常機制的處理等
 *
 * @package Cml
 */
class Cml
{
	/**
	 * 版本
	 */
	const VERSION = 'v2.8.7';
	/**
	 * 是否為debug模式
	 *
	 * @var bool
	 */
	public static $debug = false;
	/**
	 * 應用容器
	 *
	 * @var null|Container
	 */
	public static $container = null;
	/**
	 * 當前時間
	 *
	 * @var int
	 */
	public static $nowTime = 0;
	/**
	 * 當前時間含微秒
	 *
	 * @var int
	 */
	public static $nowMicroTime = 0;
	/**
	 * 執行app/只是初始化環境
	 *
	 * @var bool
	 */
	private static $run = false;
	/**
	 * 應用路徑
	 *
	 * @var array
	 */
	private static $appDir = [];
	/**
	 * 致命錯誤記錄日誌的等級列表
	 *
	 * @var array
	 */
	private static $fatalErrorLogLevel = [
		E_ERROR,
		E_PARSE,
		E_CORE_ERROR,
		E_CORE_WARNING,
		E_COMPILE_ERROR,
		E_COMPILE_WARNING,
		E_RECOVERABLE_ERROR
	];

	/**
	 * 警告日誌的等級列表
	 *
	 * @var array
	 */
	private static $warningLogLevel = [
		E_NOTICE,
		E_STRICT,
		E_DEPRECATED,
		E_USER_DEPRECATED,
		E_USER_NOTICE
	];

	/**
	 * 自動加載類庫
	 * 要注意的是 使用autoload的時候  不能手動拋出異常
	 * 因為在自動加載靜態類時手動拋出異常會導致自定義的致命錯誤捕獲機制和自定義異常處理機制失效
	 * 而 new Class 時自動加載不存在文件時，手動拋出的異常可以正常捕獲
	 * 這邊即使文件不存在時沒有拋出自定義異常也沒關係，因為自定義的致命錯誤捕獲機制會捕獲到錯誤
	 *
	 * @param string $className
	 */
	public static function autoloadComposerAdditional($className)
	{
		$className == 'Cml\Server' && class_alias('Cml\Service', 'Cml\Server');//兼容舊版本
		self::$debug && Debug::addTipInfo(Lang::get('_CML_DEBUG_ADD_CLASS_TIP_', $className), Debug::TIP_INFO_TYPE_INCLUDE_LIB);//在debug中顯示包含的類
	}

	/**
	 * 啟動應用
	 *
	 * @param callable $initDi 注入依賴
	 */
	public static function runApp(callable $initDi)
	{
		self::$run = true;

		self::onlyInitEnvironmentNotRunController($initDi);

		Plugin::hook('cml.before_run_controller');

		$controllerAction = Cml::getContainer()->make('cml_route')->getControllerAndAction();

		if ($controllerAction) {
			Cml::$debug && Debug::addTipInfo(Lang::get('_CML_EXECUTION_ROUTE_IS_', "{$controllerAction['route']}{ {$controllerAction['class']}::{$controllerAction['action']} }", Config::get('url_model')));
			$controller = new $controllerAction['class']();
			call_user_func([$controller, "runAppController"], $controllerAction['action']);//運行
		} else {
			self::montFor404Page();
			if (self::$debug) {
				throw new ControllerNotFoundException(Lang::get('_CONTROLLER_NOT_FOUND_'));
			} else {
				Response::show404Page();
			}
		}
		//輸出Debug模式的信息
		self::cmlStop();
	}

	/**
	 * 某些場景(如：跟其它項目混合運行的時候)只希望使用CmlPHP中的組件而不希望運行控制器，用來替代runApp
	 *
	 * @param callable $initDi 注入依賴
	 */
	public static function onlyInitEnvironmentNotRunController(callable $initDi)
	{
		//初始化依賴
		$initDi();

		//系統初始化
		self::init();
	}

	/**
	 * 初始化運行環境
	 *
	 */
	private static function init()
	{
		define('CML_PATH', dirname(__DIR__)); //框架的路徑
		define('CML_CORE_PATH', CML_PATH . DIRECTORY_SEPARATOR . 'Cml');// 系統核心類庫目錄
		define('CML_EXTEND_PATH', CML_PATH . DIRECTORY_SEPARATOR . 'Vendor');// 系統擴展類庫目錄

		self::handleConfigLang();

		//後面自動載入的類都會自動收集到Debug類下
		spl_autoload_register('Cml\Cml::autoloadComposerAdditional', true, true);

		//包含框架中的框架函數庫文件
		Cml::requireFile(CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Tools' . DIRECTORY_SEPARATOR . 'functions.php');

		//設置自定義捕獲致命異常函數
		//普通錯誤由Cml\Debug::catcher捕獲 php默認在display_errors為On時致命錯誤直接輸出 為off時 直接顯示服務器錯誤或空白頁,體驗不好
		register_shutdown_function(function () {
			if ($error = error_get_last()) {//獲取最後一個發生的錯誤的信息。 包括提醒、警告、致命錯誤
				if (in_array($error['type'], self::$fatalErrorLogLevel)) { //當捕獲到的錯誤為致命錯誤時 報告
					if (Plugin::hook('cml.before_fatal_error', $error) == 'jump') {
						return;
					}

					Cml::getContainer()->make('cml_error_or_exception')->fatalError($error);

					Plugin::hook('cml.after_fatal_error', $error);
				}
			}

			Plugin::hook('cml.before_cml_stop');
		}); //捕獲致命異常

		//設置自定義的異常處理函數。
		set_exception_handler(function ($e) {
			if (Plugin::hook('cml.before_throw_exception', $e) === 'resume') {
				return;
			}

			Cml::getContainer()->make('cml_error_or_exception')->appException($e);
		}); //手動拋出的異常由此函數捕獲

		ini_set('display_errors', 'off');//屏蔽系統自帶的錯誤輸出

		//載入插件配置文件
		$pluginConfig = Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . 'plugin.php';
		is_file($pluginConfig) && Cml::requireFile($pluginConfig);

		Plugin::hook('cml.before_set_time_zone');//用於動態設置時區等。

		date_default_timezone_set(Config::get('time_zone')); //設置時區

		self::$nowTime = time();
		self::$nowMicroTime = microtime(true);

		//全局的自定義語言包
		$globalLang = Cml::getApplicationDir('global_lang_path') . DIRECTORY_SEPARATOR . Config::get('lang') . '.php';
		is_file($globalLang) && Lang::set(Cml::requireFile($globalLang));

		//設置調試模式
		if (Cml::$debug) {
			Debug::start();//記錄開始運行時間\內存初始使用
			//設置捕獲系統異常 使用set_error_handler()後，error_reporting將會失效。所有的錯誤都會交給set_error_handler。
			set_error_handler('\Cml\Debug::catcher');

			array_map(function ($class) {
				Debug::addTipInfo(Lang::get('_CML_DEBUG_ADD_CLASS_TIP_', $class), Debug::TIP_INFO_TYPE_INCLUDE_LIB);
			}, [
				'Cml\Cml',
				'Cml\Config',
				'Cml\Lang',
				'Cml\Http\Request',
				'Cml\Debug',
				'Cml\Interfaces\Debug',
				'Cml\Container',
				'Cml\Interfaces\Environment',
				get_class(self::getContainer()->make('cml_environment'))
			]);
			$runTimeClassList = null;
		} else {
			$GLOBALS['debug'] = false;//關閉debug
			//ini_set('error_reporting', E_ALL & ~E_NOTICE);//記錄除了notice之外的錯誤
			ini_set('log_errors', 'off'); //關閉php自帶錯誤日誌
			//嚴重錯誤已經通過fatalError記錄。為了防止日誌過多,默認不記錄致命錯誤以外的日誌。有需要可以修改配置開啟
			if (Config::get('log_warn_log')) {
				set_error_handler('\Cml\Log::catcherPhpError');
			}

			//線上模式包含runtime.php
			$runTimeFile = Cml::getApplicationDir('global_store_path') . DIRECTORY_SEPARATOR . '_runtime_.php';
			if (!is_file($runTimeFile)) {
				//程序運行必須的類
				$runTimeClassList = [
					CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Controller.php',
					CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Response.php',
					CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Route.php',
					CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Secure.php',
				];
				Config::get('session_user') && $runTimeClassList[] = CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Session.php';

				$runTimeContent = '<?php';
				foreach ($runTimeClassList as $file) {
					$runTimeContent .= str_replace(['<?php', '?>'], '', php_strip_whitespace($file));
				}
				file_put_contents($runTimeFile, $runTimeContent, LOCK_EX);
				$runTimeContent = null;
			}
			Cml::requireFile($runTimeFile);
		}

		if (Request::isCli()) {
			//兼容舊版直接運行方法
			if (self::$run && ($_SERVER['argc'] != 2 || strpos($_SERVER['argv'][1], '/') < 1)) {
				$console = Cml::getContainer()->make('cml_console');
				$userCommand = Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . 'command.php';
				if (is_file($userCommand)) {
					$commandList = Cml::requireFile($userCommand);
					if (is_array($commandList) && count($commandList) > 0) {
						$console->addCommands($commandList);
					}
				}

				if ($console->run() !== 'don_not_exit') {
					exit(0);
				}
			}
		} else {
			header('X-Powered-By:CmlPHP');
			// 頁面壓縮輸出支持
			if (Config::get('output_encode')) {
				$zlib = ini_get('zlib.output_compression');
				if (empty($zlib)) {
					///@ob_end_clean () ; //防止在啟動ob_start()之前程序已經有輸出(比如配置文件尾多敲了換行)會導致服務器303錯誤
					ob_start('ob_gzhandler') || ob_start();
					define('CML_OB_START', true);
				} else {
					define('CML_OB_START', false);
				}
			}
		}

		Plugin::hook('cml.before_parse_url');

		//載入路由
		$routeConfigFile = Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . 'route.php';
		is_file($routeConfigFile) && Cml::requireFile($routeConfigFile);

		Cml::getContainer()->make('cml_route')->parseUrl();//解析處理URL

		Plugin::hook('cml.after_parse_url');

		//載入模塊配置
		$appConfig = Cml::getApplicationDir('apps_path')
			. '/' . Cml::getContainer()->make('cml_route')->getAppName() . '/'
			. Cml::getApplicationDir('app_config_path_name') . '/' . 'normal.php';
		is_file($appConfig) && Config::set(Cml::requireFile($appConfig));

		//載入模塊語言包
		$appLang = Cml::getApplicationDir('apps_path')
			. '/' . Cml::getContainer()->make('cml_route')->getAppName() . '/'
			. Cml::getApplicationDir('app_lang_path_name') . '/' . Config::get('lang') . '.php';
		is_file($appLang) && Lang::set(Cml::requireFile($appLang));

		//載入模塊插件
		$appPlugin = dirname($appConfig) . '/' . 'plugin.php';
		is_file($appPlugin) && Config::set(Cml::requireFile($appPlugin));
	}

	/**
	 * 處理配置及語言包相關
	 *
	 */
	private static function handleConfigLang()
	{
		//引入框架慣例配置文件
		$cmlConfig = Cml::requireFile(CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php');
		Config::init();

		//應用正式配置文件
		$appConfig = Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . Config::$isLocal . DIRECTORY_SEPARATOR . 'normal.php';

		is_file($appConfig) ? $appConfig = Cml::requireFile($appConfig)
			: exit('Config File [' . Config::$isLocal . '/normal.php] Not Found Please Check！');
		is_array($appConfig) || $appConfig = [];

		$commonConfig = Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . 'common.php';
		$commonConfig = is_file($commonConfig) ? Cml::requireFile($commonConfig) : [];

		Config::set(array_merge($cmlConfig, $commonConfig, $appConfig));//合併配置

		if (Config::get('debug')) {
			self::$debug = true;
			$GLOBALS['debug'] = true;//開啟debug
			Debug::addTipInfo(CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php', Debug::TIP_INFO_TYPE_INCLUDE_FILE);
			Debug::addTipInfo(Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . Config::$isLocal . DIRECTORY_SEPARATOR . 'normal.php', Debug::TIP_INFO_TYPE_INCLUDE_FILE);
			empty($commonConfig) || Debug::addTipInfo(Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . 'common.php', Debug::TIP_INFO_TYPE_INCLUDE_FILE);
		}

		//引入系統語言包
		Lang::set(Cml::requireFile((CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . Config::get('lang') . '.php')));
	}

	/**
	 * require 引入文件
	 *
	 * @param string $file 要引入的文件
	 * @param array $args 要釋放的變量
	 *
	 * @return mixed
	 */
	public static function requireFile($file, $args = [])
	{
		empty($args) || extract($args, EXTR_PREFIX_SAME, "xxx");
		Cml::$debug && Debug::addTipInfo($file, Debug::TIP_INFO_TYPE_INCLUDE_FILE);
		return require $file;
	}

	/**
	 * 獲取應用路徑
	 *
	 * @param string $dir
	 *
	 * @return string | bool
	 */
	public static function getApplicationDir($dir)
	{
		return isset(self::$appDir[$dir]) ? self::$appDir[$dir] : '';
	}

	/**
	 * 獲得容器
	 *
	 * @return Container
	 */
	public static function getContainer()
	{
		if (is_null(self::$container)) {
			self::$container = new Container();
		}
		return self::$container;
	}

	/**
	 * 未找到控制器的時候設置勾子
	 *
	 */
	public static function montFor404Page()
	{
		Plugin::mount('cml.before_show_404_page', [
			function () {
				$cmdLists = Config::get('cmlframework_system_route');
				$pathInfo = Route::getPathInfo();
				$cmd = strtolower(trim($pathInfo[0], '/'));
				if ($pos = strpos($cmd, '/')) {
					$cmd = substr($cmd, 0, $pos);
				}
				if (isset($cmdLists[$cmd])) {
					call_user_func($cmdLists[$cmd]);
				}
			}
		]);
		Plugin::hook('cml.before_show_404_page');
	}

	/**
	 * 程序中並輸出調試信息
	 *
	 */
	public static function cmlStop()
	{
		//輸出Debug模式的信息
		if (self::$debug) {
			header('Content-Type:text/html; charset=' . Config::get('default_charset'));
			Debug::stop();
		} else {
			$deBugLogData = dump('', 1);
			if (!empty($deBugLogData)) {
				Config::get('dump_use_php_console') ? dumpUsePHPConsole($deBugLogData) : Cml::requireFile(CML_CORE_PATH . DIRECTORY_SEPARATOR . 'ConsoleLog.php', ['deBugLogData' => $deBugLogData]);
			};
			Plugin::hook('cml.before_ob_end_flush');
			CML_OB_START && ob_end_flush();
		}
		exit();
	}

	/**
	 * 以.的方式獲取數組的值
	 *
	 * @param string $key
	 * @param array $arr
	 * @param null $default
	 *
	 * @return null
	 */
	public static function doteToArr($key = '', &$arr = [], $default = null)
	{
		if (!strpos($key, '.')) {
			return isset($arr[$key]) ? $arr[$key] : $default;
		}

		// 獲取多維數組
		$key = explode('.', $key);
		$tmp = null;
		foreach ($key as $k) {
			if (is_null($tmp)) {
				if (isset($arr[$k])) {
					$tmp = $arr[$k];
				} else {
					return $default;
				}
			} else {
				if (isset($tmp[$k])) {
					$tmp = $tmp[$k];
				} else {
					return $default;
				}
			}
		}
		return $tmp;
	}

	/**
	 * 是否開啟全局緊急模式
	 *
	 * @return bool
	 */
	public static function isEmergencyMode()
	{
		return Config::get('emergency_mode_not_real_time_refresh_mysql_query_cache') !== false;
	}

	/**
	 * 渲染顯示系統模板
	 *
	 * @param string $tpl 要渲染的模板文件
	 */
	public static function showSystemTemplate($tpl)
	{
		$configSubFix = Config::get('html_template_suffix');
		Config::set('html_template_suffix', '');
		echo View::getEngine('html')
			->setHtmlEngineOptions('templateDir', dirname($tpl) . DIRECTORY_SEPARATOR)
			->fetch(basename($tpl), false, true, true);
		Config::set('html_template_suffix', $configSubFix);
	}

	/**
	 * 設置應用路徑
	 *
	 * @param array $dir
	 */
	public static function setApplicationDir(array $dir)
	{
		if (DIRECTORY_SEPARATOR == '\\') {//windows
			array_walk($dir, function (&$val) {
				$val = str_replace('/', DIRECTORY_SEPARATOR, $val);
			});
		}
		self::$appDir = array_merge(self::$appDir, $dir);
	}

	/**
	 * 動態獲取容器綁定的實例
	 *
	 * @param string $name 要獲取的綁定的實例名
	 * @param string $arguments 第一個參數為綁定名稱的前綴，默認為cml，目前有cml/view/db/cache幾種前綴
	 *
	 * @return object
	 */
	public static function __callStatic($name, $arguments)
	{
		$prefix = isset($arguments[0]) ? $arguments[0] : 'cml';
		return Cml::getContainer()->make($prefix . humpToLine($name));
	}

	/**
	 * 獲取警告日誌的等級列表
	 *
	 * @return array
	 */
	public static function getWarningLogLevel()
	{
		return self::$warningLogLevel;
	}

	/**
	 * 設置警告日誌的等級列表
	 *
	 * @param array|int $level
	 */
	public static function setWarningLogLevel($level)
	{
		if (is_array($level)) {
			self::$warningLogLevel = $level;
		} else {
			self::$warningLogLevel[] = $level;
		}
	}

	/**
	 * 設置警告日誌的等級列表
	 *
	 * @return array
	 */
	public static function getFatalErrorLogLevel()
	{
		return self::$fatalErrorLogLevel;
	}

	/**
	 * 設置警告日誌的等級列表
	 *
	 * @param array|int $level
	 */
	public static function setFatalErrorLogLevel($level)
	{
		if (is_array($level)) {
			self::$fatalErrorLogLevel = $level;
		} else {
			self::$fatalErrorLogLevel[] = $level;
		}
	}
}
