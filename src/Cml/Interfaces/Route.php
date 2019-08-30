<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 路由接口。使用第三方路由必須封裝實現本接口
 * *********************************************************** */

namespace Cml\Interfaces;

/**
 * 路由驅動抽像接口
 *
 * @package Cml\Interfaces
 */
interface Route
{

	/**
	 * 修改解析得到的請求信息 含應用名、控制器、操作
	 *
	 * @param string $key path|controller|action|root
	 * @param string $val
	 *
	 * @return void
	 */
	public function setUrlParams($key = 'path', $val = '');

	/**
	 * 獲取子目錄路徑。若項目在子目錄中的時候為子目錄的路徑如/sub_dir/、否則為/
	 *
	 * @return string
	 */
	public function getSubDirName();

	/**
	 * 獲取應用目錄可以是多層目錄。如web、admin等.404的時候也必須有值用於綁定系統命令
	 *
	 * @return string
	 */
	public function getAppName();

	/**
	 * 獲取控制器名稱不帶Controller後綴
	 *
	 * @return string
	 */
	public function getControllerName();

	/**
	 * 獲取控制器名稱方法名稱
	 *
	 * @return string
	 */
	public function getActionName();

	/**
	 * 獲取不含子目錄的完整路徑 如: web/Goods/add
	 *
	 * @return string
	 */
	public function getFullPathNotContainSubDir();

	/**
	 * 解析url參數
	 * 框架在完成必要的啟動步驟後。會調用 Cml::getContainer()->make('cml_route')->parseUrl();進行路由地址解析供上述幾個方法調用。
	 *
	 * @return mixed
	 */
	public function parseUrl();

	/**
	 * 返回要執行的控制器及方法。必須返回一個包含 controller和action鍵的數組
	 * 如:['class' => 'adminbase/Controller/IndexController', 'action' => 'index']
	 * 在parseUrl之後框架會根據解析得到的參數去自動載入相關的配置文件然後調用Cml::getContainer()->make('cml_route')->getControllerAndAction();執行相應的方法
	 *
	 * @return mixed
	 */
	public function getControllerAndAction();

	/**
	 * 增加get訪問方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string $action 執行的操作
	 *
	 * @return void
	 */
	public function get($pattern, $action);

	/**
	 * 增加post訪問方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string $action 執行的操作
	 *
	 * @return void
	 */
	public function post($pattern, $action);

	/**
	 * 增加put訪問方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string $action 執行的操作
	 *
	 * @return void
	 */
	public function put($pattern, $action);

	/**
	 * 增加patch訪問方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string $action 執行的操作
	 *
	 * @return void
	 */
	public function patch($pattern, $action);

	/**
	 * 增加delete訪問方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string $action 執行的操作
	 *
	 * @return void
	 */
	public function delete($pattern, $action);

	/**
	 * 增加options訪問方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string $action 執行的操作
	 *
	 * @return void
	 */
	public function options($pattern, $action);

	/**
	 * 增加任意訪問方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string $action 執行的操作
	 *
	 * @return void
	 */
	public function any($pattern, $action);

	/**
	 * 增加REST方式路由
	 *
	 * @param string $pattern 路由規則
	 * @param string $action 執行的操作
	 *
	 * @return void
	 */
	public function rest($pattern, $action);

	/**
	 * 分組路由
	 *
	 * @param string $namespace 分組名
	 * @param callable $func 閉包
	 */
	public function group($namespace, callable $func);
}
