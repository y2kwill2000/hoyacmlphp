<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架慣例配置文件
 * *********************************************************** */

return [
	//調試模式  默認關閉
	'debug' => false,
	'db_fields_cache' => true, //在debug模式實時獲取字段列表，線上模式是否開啟數據庫字段緩存到文件。自v2.6.3起。開啟本功能主要用於獲取主鍵緩存

	'time_zone' => 'PRC', //時區

	//數據庫配置
	'default_db' => [
		'driver' => 'MySql.Pdo', //數據庫驅動
		'master' => [
			'host' => 'localhost', //數據庫主機
			//'host' => 'unix_socket=/path/', //使用unix_socket
			'username' => 'root', //數據庫用戶名
			'password' => '', //數據庫密碼
			'dbname' => 'cmlphp', //數據庫名
			'charset' => 'utf8mb4', //數據庫編碼
			'tableprefix' => 'sun_', //數據表前綴
			'pconnect' => false, //是否開啟數據庫長連接
			'engine' => ''//數據庫引擎
		],
		'slaves' => [],

		//查詢數據緩存時間，表數據有變動會自動更新緩存。設置為0表示表數據沒變動時緩存不過期。
		//這邊設置為3600意思是即使表數據沒變動也讓緩存每3600s失效一次,這樣可以讓緩存空間更合理的利用.
		//如果不想啟用緩存直接配置為false
		'cache_expire' => 3600,

		//是否記錄執行慢的sql語句。只針對mysql有效。這個不能長期開，只能要分析sql性能的時候比如只開個5會鍾有一定的log後就應該關掉。默認為false。
		//為整形的時候則為執行時間大於這個時間的sql都記錄到log下 比如配置為1則為執行超過1s的sql都記錄到 notice.log裡
		'log_slow_sql' => false,
		//'sql_mode' => '',//是否要設置運行時sql_model mysql有效默認不開啟放服務器配置
	],

	/**
	 * 使用MongoDB
	 * 'db_mongo' => [
	 * 'driver' => 'MongoDB.MongoDB', //數據庫驅動
	 * 'master' => [
	 * 'host' => 'localhost:27017',
	 * 'username' => '',
	 * 'password' => '',
	 * 'dbname' => 'test',
	 * //'replicaSet' => '' //replicaSet名稱
	 * ],
	 * 'slaves'=>[],
	 * ],
	 **/

	// 緩存服務器的配置
	'default_cache' => [
		'on' => 0, //為1則啟用，或者不啟用
		'driver' => 'Memcache',
		'prefix' => 'cml_',
		'server' => [
			[
				'host' => '127.0.0.1',
				'port' => 11211,//必須是整形
				//'weight' => 100 //權重
				//'username'=>'sals_username'
				//'password'=>'sals_password'
			],
			//多台...
		],
	],
	/**
	 * //文件緩存
	 * 'default_cache' => [
	 * 'on' => 0, //為1則啟用，或者不啟用
	 * 'driver' => 'File',
	 * 'prefix' => 'cml_'
	 * ],
	 * //apc緩存
	 * 'default_cache' => [
	 * 'on' => 0, //為1則啟用，或者不啟用
	 * 'driver' => 'Apc',
	 * 'prefix' => 'cml_'
	 * ],
	 * //Redis緩存
	 * 'default_cache' => [
	 * 'on' => 0, //為1則啟用，或者不啟用
	 * 'driver' => 'Redis',
	 * 'prefix' => 'cml_',
	 * 'server' => [
	 * [
	 * 'host' => '127.0.0.1',
	 * 'port' => 6379,
	 * //'pconnect' => false //默認使用長連接
	 * //'db' => 6
	 * //'password' => '123456' //沒有密碼的時候不要配置
	 * ],
	 * //多台...
	 * ],
	 * //'back' => [//當server中有機器掛掉且back有開啟時。會自動使用back來替換掛掉的server方便處理異常情況
	 * //    'host' => '127.0.0.1',
	 * //    'port' => 6379
	 * //]
	 * ],
	 * //Redis集群
	 * 'default_cache' => [
	 * 'on' => 1, //為1則啟用，或者不啟用
	 * 'driver' => 'RedisCluster',
	 * 'prefix' => 'bx_',//配置緩存前綴防止衝突
	 * 'server' => [
	 * 'host1:port1',
	 * 'host2:port2',
	 * ],
	 * 'password' => 'pwd'
	 * ],
	 */

	/*模板設置*/
	'view_render_engine' => 'html',//默認的視圖渲染引擎，html/excel/json/xml
	'default_charset' => 'utf-8', // 默認輸出編碼
	'http_cache_control' => 'private', // 網頁緩存控制
	'output_encode' => true, // 頁面壓縮輸出

	/*Html引擎配置。只適用於html模板引擎*/
	'html_theme' => '', //默認只有單主題
	'html_template_suffix' => '.html',     // 默認模板文件後綴
	'html_left_deper' => '{{', //模板左定界符
	'html_right_deper' => '}}', //模板右定界符

	/*系統模板定義*/
	'html_exception' => CML_CORE_PATH . '/Tpl/cmlException.tpl', // 默認成功跳轉對應的模板文件
	'404_page' => CML_CORE_PATH . '/Tpl/404.tpl', // 404跳轉頁
	'debug_page' => CML_CORE_PATH . '/Tpl/debug.tpl', // debug調試信息模板


	/* URL設置 */
	'url_model' => 1,       // URL訪問模式,可選參數1、2、3,代表以下四種模式：
	// 1 (PATHINFO 模式顯示index.php); 2 (PATHINFO 不顯示index.php); 3 (兼容模式)  默認為PATHINFO 模式，提供最好的用戶體驗和SEO支持
	'url_pathinfo_depr' => '/', // PATHINFO模式下，各參數之間的分割符號
	'url_html_suffix' => '.html',  // URL偽靜態後綴設置
	'url_default_action' => 'web/Default/index', //默認操作
	'var_pathinfo' => 'r',  // PATHINFO 兼容模式獲取變量例如 ?r=/module/action/id/1中的s ,後面的分隔符/取決於url_pathinfo_depr
	//'static__path' => 'http://static.cml.com/', //模板替換的{{public}}靜態地址(訪問靜態資源用)  默認為 入口文件所在目錄

	/*安全過濾*/
	'auth_key' => 'a5et3e41d', //Encry加密key
	'check_csrf' => 1, //檢查csrf跨站攻擊 0、不檢查，1、只檢查post數據提交方式，2、get/post都檢查 默認只檢查post
	'form_token' => 0, //表單令牌 0不開啟，1開啟

	/*語言包設置*/
	'lang' => 'zh-cn',  //讀取zh-cn.php文件

	/*cookie設置*/
	'cookie_prefix' => 'cml_', //cookie前綴
	'cookie_expire' => 0,    // Coodie有效期
	'cookie_domain' => '',      // Cookie有效域名
	'cookie_path' => '/',     // Cookie路徑
	'userauthid' => 'CmlUserAuth',  //用戶登錄成功之後的cookie標識

	/*Session設置*/
	'session_prefix' => 'cml_', //session前綴
	'session_user' => 0, //SESSION保存位置自定義 0不開啟、1開啟
	'session_user_loc' => 'db', //自定義保存SESSION的位置時 定義保存的位置  db、cache兩種
	'session_user_loc_table' => 'session', //自定義保存SESSION的保存位置設置為db時的表名
	'session_user_loc_tableprefix' => 'cml_', //自定義保存SESSION的保存位置設置為db時的表前綴

	/**鎖配置**/
	'lock_prefix' => 'cml_',
	'locker_use_cache' => 'default_cache', //上鎖使用的緩存

	/**日誌配置**/
	'log_warn_log' => false, //警告級別的日誌默認不記錄
	'log_prefix' => 'cml_log', //會顯示到日誌內容中,同時當以redis為驅動的時候會做為隊列的前綴
	//Log類使用redis為驅動時使用的緩存配置key
	'redis_log_use_cache' => 'default_cache',//只有在該緩存的驅動為redis的時候才有效,否則會報錯

	/**隊列配置**/
	//Redis隊列使用的緩存
	'redis_queue_use_cache' => 'default_cache',//只有在該緩存的驅動為redis的時候才有效,否則會報錯

	/*系統路由-統一用小寫url*/
	'cmlframework_system_route' => [
		'cmlframeworkstaticparse' => '\\Cml\\Tools\\StaticResource::parseResourceFile',//解析靜態資源
	],
	'static_file_version' => 'v1', //開發模式會自動在靜態文件後加時間綴，實時過期,線上模板版本號固定，如有需要在這裡改版本號強制過期

	/*php-console配置*/
	'dump_use_php_console' => false, //開啟本功能需要先安裝php-console composer require php-console/php-console ~3.0
	'php_console_password' => 'cmlphp_php_console_pw123456',

	/**
	 * 是否開啟全局緊急模式--慎用。主要用於在系統mysql負載過高(如遇到攻擊)mysql壓力過大。先將所有查詢轉移至緩存。消化壓力高峰
	 *
	 * 開啟時 mysql的查詢緩存不會在數據變更時實時更新。
	 * 所以如果要開啟請確定開啟後不會對業務造成影響。如:扣積分前的查詢積分數，這種對數據實時要求高的，在開啟本模式時要做下判斷並屏蔽。
	 */
	'emergency_mode_not_real_time_refresh_mysql_query_cache' => false, //配置成int型則為緩存刷新週期。如配置成 300 則為數據變更時每五分鐘更新一次mysql查詢緩存

	'var_page' => 'page', //分頁時傳遞當前頁數的變量名

	'route_app_hierarchy' => 1, //路由找控制器的時候遍歷應用目錄的層級，默認為1
	'controller_suffix' => 'Controller', //控制器後綴名
];
