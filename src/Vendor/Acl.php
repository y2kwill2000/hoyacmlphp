<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-11 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 權限控制類
 * *********************************************************** */

namespace Cml\Vendor;

use Cml\Cml;
use Cml\Config;
use Cml\Encry;
use Cml\Http\Cookie;
use Cml\Model;

/**
 * 權限控制類
 *
 * 對方法註釋 @noacl 則不檢查該方法的權限
 * 對方法註釋 @acljump web/User/add 則將當前方法的權限檢查跳轉為檢查 web/User/add方法的權限
 * 加到normal.php配置中
 * //權限控制配置
 * 'administratorid'=>'1', //超管理員id
 *
 * 建庫語句
 *
 * CREATE TABLE `pr_admin_app` (
 * `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
 * `name` varchar(255) NOT NULL DEFAULT '' COMMENT '應用名',
 * PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
 *
 * CREATE TABLE `pr_admin_access` (
 * `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '權限ID',
 * `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所屬用戶權限ID',
 * `groupid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所屬群組權限ID',
 * `menuid` int(11) NOT NULL DEFAULT '0' COMMENT '權限模塊ID',
 * PRIMARY KEY (`id`),
 * KEY `idx_userid` (`userid`),
 * KEY `idx_groupid` (`groupid`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='用戶或者用戶組權限記錄';
 *
 * CREATE TABLE `pr_admin_groups` (
 * `id` smallint(3) unsigned NOT NULL AUTO_INCREMENT,
 * `name` varchar(150) NOT NULL DEFAULT '' COMMENT '用戶組名',
 * `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1正常，0刪除',
 * `remark` text NOT NULL COMMENT '備註',
 * PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
 *
 * CREATE TABLE `pr_admin_menus` (
 * `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
 * `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父模塊ID編號 0則為頂級模塊',
 * `title` varchar(64) NOT NULL DEFAULT '' COMMENT '標題',
 * `url` varchar(64) NOT NULL DEFAULT '' COMMENT 'url路徑',
 * `params` varchar(64) NOT NULL DEFAULT '' COMMENT 'url參數',
 * `isshow` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否顯示',
 * `sort` smallint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序倒序',
 *  `app` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '菜單所屬app，對應app表中的主鍵',
 * PRIMARY KEY (`id`),
 * KEY `idex_pid` (`pid`),
 * KEY `idex_order` (`sort`),
 * KEY `idx_action` (`url`),
 * KEY `idx_app` (`app`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='權限模塊信息表';
 *
 * CREATE TABLE `pr_admin_users` (
 * `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
 * `groupid` varchar(255) NOT NULL DEFAULT '0' COMMENT '用戶組id',
 * `username` varchar(40) NOT NULL DEFAULT '' COMMENT '用戶名',
 * `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '暱稱',
 * `password` char(32) NOT NULL DEFAULT '' COMMENT '密碼',
 * `lastlogin` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最後登錄時間',
 * `ctime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '創建時間',
 * `stime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改時間',
 * `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1正常，0刪除',
 * `remark` text NOT NULL,
 * `from_type` tinyint(3) unsigned DEFAULT '1' COMMENT '用戶類型。1為系統用戶。',
 * PRIMARY KEY (`id`),
 * UNIQUE KEY `username` (`username`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
 *
 * @package Cml\Vendor
 */
class Acl
{
	/**
	 * 有權限的時候保存權限的顯示名稱用於記錄log
	 *
	 * @var array
	 */
	public static $aclNames = [];
	/**
	 * 當前登錄的用戶信息
	 *
	 * @var null
	 */
	public static $authUser = null;
	/**
	 * 加密用的混淆key
	 *
	 * @var string
	 */
	private static $encryptKey = 'pnnle-oienngls-llentne-lnegxe';
	/**
	 * 定義表名
	 *
	 * @var array
	 */
	private static $tables = [
		'access' => 'access',
		'groups' => 'groups',
		'menus' => 'menus',
		'users' => 'users',
	];
	/**
	 * 單點登錄標識
	 *
	 * @var string
	 */
	private static $ssoSign = '';

	/**
	 * 單個用戶歸屬多個用戶組時多個id在mysql中的分隔符
	 *
	 * @var string
	 */
	private static $multiGroupDeper = '|';

	/**
	 * 設置權限除了檢查url之外的參數。如當前請求的url為web/Index/index  這邊傳參?id=1則檢查權限的時候是檢查有無url為web/Index/index?id=1的菜單
	 *
	 * @var string
	 */
	private static $otherAclParams = '';

	/**
	 * 獲取單個用戶歸屬多個用戶組時多個id在mysql中的分隔符
	 *
	 * @return string
	 */
	public static function getMultiGroupDeper()
	{
		return self::$multiGroupDeper;
	}

	/**
	 * 設置單個用戶歸屬多個用戶組時多個id在mysql中的分隔符
	 *
	 * @param string $deper 分隔符
	 */
	public static function setMultiGroupDeper($deper = '|')
	{
		self::$multiGroupDeper = $deper;
	}

	/**
	 * 設置權限除了檢查url之外的params參數。如當前請求的url為web/Index/index  這邊傳參?id=1則檢查權限的時候是檢查url為web/Index/index並且params字段為?id=1的菜單
	 *
	 * @param string $otherAclParams
	 */
	public static function setOtherAclParams($otherAclParams = '')
	{
		self::$otherAclParams = $otherAclParams;
	}

	/**
	 * 設置加密用的混淆key Cookie::set本身有一重加密 這裡再加一重
	 *
	 * @param string $key
	 */
	public static function setEncryptKey($key)
	{
		self::$encryptKey = $key;
	}

	/**
	 * 自定義表名
	 *
	 * @param string|array $type
	 * @param string $tableName
	 */
	public static function setTableName($type = 'access', $tableName = 'access')
	{
		if (is_array($type)) {
			self::$tables = array_merge(self::$tables, $type);
		} else {
			self::$tables[$type] = $tableName;
		}
	}

	/**
	 * 獲取表名
	 * @param string $type
	 *
	 * @return mixed
	 */
	public static function getTableName($type = 'access')
	{
		if (isset(self::$tables[$type])) {
			return self::$tables[$type];
		} else {
			throw new \InvalidArgumentException($type);
		}
	}

	/**
	 * 檢查對應的權限
	 *
	 * @param object|string $controller 傳入控制器實例對象，用來判斷當前訪問的方法是不是要跳過權限檢查。
	 * 如當前訪問的方法為web/User/list則傳入new \web\Controller\User()獲得的實例。最常用的是在基礎控制器的init方法或構造方法裡傳入$this。
	 * 傳入字符串如web/User/list時會自動 new \web\Controller\User()獲取實例用於判斷
	 *
	 * @return bool
	 * @throws \Exception
	 *
	 */
	public static function checkAcl($controller)
	{
		$authInfo = self::getLoginInfo();
		if (!$authInfo) return false; //登錄超時

		//當前登錄用戶是否為超級管理員
		if (self::isSuperUser()) {
			return true;
		}

		$checkUrl = Cml::getContainer()->make('cml_route')->getFullPathNotContainSubDir();
		$checkAction = Cml::getContainer()->make('cml_route')->getActionName();

		if (is_string($controller)) {
			$checkUrl = trim($controller, '/\\');
			$controller = str_replace('/', '\\', $checkUrl);
			$actionPosition = strrpos($controller, '\\');
			$checkAction = substr($controller, $actionPosition + 1);
			$offset = $appPosition = 0;
			for ($i = 0; $i < Config::get('route_app_hierarchy', 1); $i++) {
				$appPosition = strpos($controller, '\\', $offset);
				$offset = $appPosition + 1;
			}
			$appPosition = $offset - 1;

			$subString = substr($controller, 0, $appPosition) . '\\' . Cml::getApplicationDir('app_controller_path_name') . substr($controller, $appPosition, $actionPosition - $appPosition);
			$controller = "\\{$subString}" . Config::get('controller_suffix');

			if (class_exists($controller)) {
				$controller = new $controller;
			} else {
				return false;
			}
		}

		$checkUrl = ltrim(str_replace('\\', '/', $checkUrl), '/');
		$origUrl = $checkUrl;

		if (is_object($controller)) {
			//判斷是否有標識 @noacl 不檢查權限
			$reflection = new \ReflectionClass($controller);
			$methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
			foreach ($methods as $method) {
				if ($method->name == $checkAction) {
					$annotation = $method->getDocComment();
					if (strpos($annotation, '@noacl') !== false) {
						return true;
					}

					$checkUrlArray = [];

					if (preg_match('/@acljump([^\n]+)/i', $annotation, $aclJump)) {
						if (isset($aclJump[1]) && $aclJump[1]) {
							$aclJump[1] = explode('|', $aclJump[1]);
							foreach ($aclJump[1] as $val) {
								$val = trim($val);
								substr($val, 0, 3) == '../' && $val = '../' . $val;
								if ($times = preg_match_all('#\./#i', $val)) {
									$origUrlArray = explode('/', $origUrl);
									$val = explode('./', $val);

									for ($i = 0; $i < $times; $i++) {
										array_pop($origUrlArray);
										array_shift($val);
									}
									$val = implode('/', array_merge($origUrlArray, $val));
								}
								$val && $checkUrlArray[] = ltrim(str_replace('\\', '/', trim($val)), '/') . self::$otherAclParams;
							}
						}
						empty($checkUrlArray) || $checkUrl = $checkUrlArray;
					}
				}
			}
		}

		$acl = Model::getInstance()->table([self::$tables['access'] => 'a'])
			->join([self::$tables['menus'] => 'm'], 'a.menuid=m.id')
			->_and(function ($model) use ($authInfo) {
				$model->whereIn('a.groupid', $authInfo['groupid'])
					->_or()
					->where('a.userid', $authInfo['id']);
			})->when(self::$otherAclParams, function ($model) {
				$model->where('m.params', self::$otherAclParams);
			})->when(is_array($checkUrl), function ($model) use ($checkUrl) {
				$model->whereIn('m.url', $checkUrl);
			}, function ($model) use ($checkUrl) {
				$model->where('m.url', $checkUrl);
			})->count('1');
		return $acl > 0;
	}

	/**
	 * 獲取當前登錄用戶的信息
	 *
	 * @return array
	 */
	public static function getLoginInfo()
	{
		if (is_null(self::$authUser)) {
			//Cookie::get本身有一重解密 這裡解第二重
			self::$authUser = Encry::decrypt(Cookie::get(Config::get('userauthid')), self::$encryptKey);
			empty(self::$authUser) || self::$authUser = json_decode(self::$authUser, true);

			if (
				empty(self::$authUser)
				|| (self::$authUser['expire'] > 0 && self::$authUser['expire'] < Cml::$nowTime)
				|| self::$authUser['ssosign'] != Model::getInstance()->cache()
					->get("SSOSingleSignOn" . self::$authUser['uid'])
			) {
				self::$authUser = false;
				self::$ssoSign = '';
			} else {
				self::$ssoSign = self::$authUser['ssosign'];

				$user = Model::getInstance(self::$tables['users'])->where('status', 1)->getByColumn(self::$authUser['uid']);
				if (empty($user)) {
					self::$authUser = false;
				} else {
					$authUser = [
						'id' => $user['id'],
						'username' => $user['username'],
						'nickname' => $user['nickname'],
						'groupid' => array_values(array_filter(explode(self::$multiGroupDeper, trim($user['groupid'], self::$multiGroupDeper)), function ($v) {
							return !empty($v);
						})),
						'from_type' => $user['from_type']
					];

					$authUser['groupname'] = Model::getInstance(self::$tables['groups'])->mapDbAndTable()
						->whereIn('id', $authUser['groupid'])
						->where('status', 1)
						->plunk('name');
					$authUser['groupname'] = implode(',', $authUser['groupname']);
					//有操作登錄超時時間重新設置為expire時間
					if (self::$authUser['expire'] > 0 && (
							(self::$authUser['expire'] - Cml::$nowTime) < (self::$authUser['not_op'] / 2)
						)
					) {
						self::setLoginStatus($user['id'], false, 0, self::$authUser['not_op']);
					}

					unset($user, $group);
					self::$authUser = $authUser;
				}
			}
		}
		return self::$authUser;
	}

	/**
	 * 保存當前登錄用戶的信息
	 *
	 * @param int $uid 用戶id
	 * @param bool $sso 是否為單點登錄，即踢除其它登錄用戶
	 * @param int $cookieExpire 登錄的過期時間，為0則默認保持到瀏覽器關閉，> 0的值為登錄有效期的秒數。默認為0
	 * @param int $notOperationAutoLogin 當$cookieExpire設置為0時，這個值為用戶多久不操作則自動退出。默認為1個小時
	 * @param string $cookiePath path
	 * @param string $cookieDomain domain
	 */
	public static function setLoginStatus($uid, $sso = true, $cookieExpire = 0, $notOperationAutoLogin = 3600, $cookiePath = '', $cookieDomain = '')
	{
		$cookieExpire > 0 && $notOperationAutoLogin = 0;
		$user = [
			'uid' => $uid,
			'expire' => $notOperationAutoLogin > 0 ? Cml::$nowTime + $notOperationAutoLogin : 0,
			'ssosign' => $sso ? (string)Cml::$nowMicroTime : self::$ssoSign
		];
		$notOperationAutoLogin > 0 && $user['not_op'] = $notOperationAutoLogin;

		//Cookie::set本身有一重加密 這裡再加一重
		if ($sso) {
			Model::getInstance()->cache()->set("SSOSingleSignOn{$uid}", $user['ssosign'], 86400 + $cookieExpire);
		} else {
			//如果是剛剛從要單點切換成不要單點。這邊要把ssosign置為cache中的
			empty($user['ssosign']) && $user['ssosign'] = Model::getInstance()->cache()->get("SSOSingleSignOn{$uid}");
		}
		Cookie::set(Config::get('userauthid'), Encry::encrypt(json_encode($user, JSON_UNESCAPED_UNICODE), self::$encryptKey), $cookieExpire, $cookiePath, $cookieDomain);
	}

	/**
	 * 判斷當前登錄用戶是否為超級管理員
	 *
	 * @return bool
	 */
	public static function isSuperUser()
	{
		$authInfo = self::getLoginInfo();
		if (!$authInfo) {//登錄超時
			return false;
		}
		$admin = Config::get('administratorid');
		return is_array($admin) ? in_array($authInfo['id'], $admin) : ($authInfo['id'] === $admin);
	}

	/**
	 * 獲取有權限的菜單列表
	 *
	 * @param bool $format 是否格式化返回
	 * @param string $columns 要額外獲取的字段
	 *
	 * @return array
	 */
	public static function getMenus($format = true, $columns = '')
	{
		$res = [];
		$authInfo = self::getLoginInfo();
		if (!$authInfo) { //登錄超時
			return $res;
		}

		$result = Model::getInstance()->table([self::$tables['menus'] => 'm'])
			->columns(['distinct m.id', 'm.pid', 'm.title', 'm.url', 'm.params' . ($columns ? " ,{$columns}" : '')])
			->when(!self::isSuperUser(), function ($model) use ($authInfo) {//當前登錄用戶是否為超級管理員
				$model->join([self::$tables['access'] => 'a'], 'a.menuid=m.id')
					->_and(function ($model) use ($authInfo) {
						$model->whereIn('a.groupid', $authInfo['groupid'])
							->_or()
							->where('a.userid', $authInfo['id']);
					});
			})->where('m.isshow', 1)
			->orderBy('m.sort', 'DESC')
			->orderBy('m.id', 'ASC')
			->select(0, 5000);

		$res = $format ? Tree::getTreeNoFormat($result, 0) : $result;
		return $res;
	}

	/**
	 * 登出
	 *
	 */
	public static function logout()
	{
		$user = Acl::getLoginInfo();
		$user && Model::getInstance()->cache()->delete("SSOSingleSignOn" . $user['id']);
		Cookie::delete(Config::get('userauthid'));
	}
}
