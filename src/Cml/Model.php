<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 系統默認Model
 * *********************************************************** */

namespace Cml;

use Cml\Interfaces\Cache;
use Cml\Interfaces\Db;

/**
 * 基礎Model類，在CmlPHP中負責數據的存取(目前包含db/cache以及為了簡化操作db而封裝的快捷方法)
 *
 * 以下方法只是為了方便配合Model中的快捷方法(http://doc.cmlphp.com/devintro/model/mysql/fastmethod/readme.html)使用
 * 並沒有列出db中的所有方法。其它未列出的方法建議還是通過$this->db()->xxx使用
 * @method Db|Model where(string | array $column, string | int $value = '')  where條件組裝-相等
 * @method Db|Model whereColumn(string $column, string $column2 = '')  where條件組裝-兩個列相等
 * @method Db|Model whereRaw(string $where, array $params = [])  where條件組裝-原生條件
 * @method Db|Model whereNot(string $column, string | int $value)  where條件組裝-不等
 * @method Db|Model whereGt(string $column, string | int $value = '')  where條件組裝-大於
 * @method Db|Model whereLt(string $column, string | int $value = '')  where條件組裝-小於
 * @method Db|Model whereGte(string $column, string | int $value = '')  where條件組裝-大於等於
 * @method Db|Model whereLte(string $column, string | int $value = '')  where條件組裝-小於等於
 * @method Db|Model whereIn(string $column, array $value) where條件組裝-IN
 * @method Db|Model whereNotIn(string $column, array $value) where條件組裝-NOT IN
 * @method Db|Model whereLike(string $column, bool $leftBlur = false, string $value, bool $rightBlur = false) where條件組裝-LIKE
 * @method Db|Model whereNotLike(string $column, bool $leftBlur = false, string $value, bool $rightBlur = false) where條件組裝-NOT LIKE
 * @method Db|Model whereRegExp(string $column, string $value) where條件組裝-RegExp
 * @method Db|Model whereBetween(string $column, int $value, int $value2 = null) where條件組裝-BETWEEN
 * @method Db|Model whereNotBetween(string $column, int $value, int $value2 = null) where條件組裝-NotBetween
 * @method Db|Model whereNull(string $column) where條件組裝-IS NULL
 * @method Db|Model whereNotNull(string $column) where條件組裝-IS NOT NULL
 * @method Db|Model columns(string | array $columns = '*') 選擇列
 * @method Db|Model orderBy(string $column, string $order = 'ASC') 排序
 * @method Db|Model groupBy(string $column) 分組
 * @method Db|Model having(string $column, $operator = '=', $value) 分組
 * @method Db|Model paramsAutoReset(bool $autoReset = true, bool $alwaysClearTable = false, bool $alwaysClearColumns = true) orm參數是否自動重置, 默認在執行語句後會重置orm參數, 包含查詢的表、字段信息、條件等信息
 * @method Db|Model noCache() 標記本次查詢不使用緩存
 * @method Db|Model table(string |array $table = '', string | null $tablePrefix = null) 定義操作的表
 * @method Db|Model lBrackets() where條件增加左括號
 * @method Db|Model rBrackets() where條件增加右括號
 * @method Db|Model join(string | array $table, string $on, string | null $tablePrefix = null) join內聯結
 * @method Db|Model leftJoin(string | array $table, string $on, string | null $tablePrefix = null) leftJoin左聯結
 * @method Db|Model rightJoin(string | array $table, string $on, string | null $tablePrefix = null) rightJoin右聯結
 * @method Db|Model _and(callable $callable = null) and條件操作
 * @method Db|Model _or(callable $callable = null) or條件操作
 * @method Db|Model transaction(callable $query) 執行事務操作
 *
 * @package Cml
 */
class Model
{
	/**
	 * Cache驅動實例
	 *
	 * @var array
	 */
	private static $cacheInstance = [];
	/**
	 * 快捷方法-讀是否強制使用主庫
	 *
	 * @var bool
	 */
	protected $useMaster = false;
	/**
	 * 查詢數據緩存時間
	 *
	 *  表數據有變動會自動更新緩存。設置為0表示表數據沒變動時緩存不過期。
	 * 這邊設置為3600意思是即使表數據沒變動也讓緩存每3600s失效一次,這樣可以讓緩存空間更合理的利用.
	 * 如果不想啟用緩存直接配置為false
	 * 默認為null： 使用 db配置中的cache_expire
	 *
	 * @var mixed
	 */
	protected $cacheExpire = null;
	/**
	 * 表前綴
	 *
	 * @var null|string
	 */
	protected $tablePrefix = null;
	/**
	 * 數據庫配置key
	 *
	 * @var string
	 */
	protected $db = 'default_db';
	/**
	 * 表名
	 *
	 * @var null|string
	 */
	protected $table = null;
	/**
	 * Db驅動實例
	 *
	 * @var array
	 */
	private $dbInstance = [];

	/**
	 * 獲取model實例並同時執行mapDbAndTable
	 *
	 * @param null|string $table 表名
	 * @param null|string $tablePrefix 表前綴
	 *
	 * @return Db
	 */
	public static function getInstanceAndRunMapDbAndTable($table = null, $tablePrefix = null)
	{
		return self::getInstance($table, $tablePrefix)->mapDbAndTable();
	}

	/**
	 * 自動根據 db屬性執行$this->db(xxx)方法; table/tablePrefix屬性執行$this->db('xxx')->table('tablename', 'tablePrefix')方法
	 *
	 * @return  Db
	 */
	public function mapDbAndTable()
	{
		return $this->db($this->getDbConf())->table($this->getTableName(), $this->tablePrefix);
	}

	/**
	 * 初始化一個Model實例
	 *
	 * @param null|string $table 表名
	 * @param null|string $tablePrefix 表前綴
	 * @param null|string|array $db db配置，默認default_db
	 *
	 * @return Db | $this
	 */
	public static function getInstance($table = null, $tablePrefix = null, $db = null)
	{
		static $mInstance = [];
		$class = get_called_class();
		$classKey = $class . '-' . $tablePrefix . $table;
		if (!isset($mInstance[$classKey])) {
			$mInstance[$classKey] = new $class();
			is_null($table) || $mInstance[$classKey]->table = $table;
			is_null($tablePrefix) || $mInstance[$classKey]->tablePrefix = $tablePrefix;
			is_null($db) || $mInstance[$classKey]->db = $db;
		}
		return $mInstance[$classKey];
	}

	/**
	 * 靜態方式獲取cache實例
	 *
	 * @param string $conf 使用的緩存配置;
	 *
	 * @return Cache
	 */
	public static function staticCache($conf = 'default_cache')
	{
		return self::getInstance()->cache($conf);
	}

	/**
	 * 獲取cache實例
	 *
	 * @param string $conf 使用的緩存配置;
	 *
	 * @return Cache
	 */
	public function cache($conf = 'default_cache')
	{
		if (is_array($conf)) {
			$config = $conf;
			$conf = md5(json_encode($conf));
		} else {
			$config = Config::get($conf);
		}

		if (isset(self::$cacheInstance[$conf])) {
			return self::$cacheInstance[$conf];
		} else {
			if ($config['on']) {
				self::$cacheInstance[$conf] = Cml::getContainer()->make('cache_' . strtolower($config['driver']), $config);
				return self::$cacheInstance[$conf];
			} else {
				throw new \InvalidArgumentException(Lang::get('_NOT_OPEN_', $conf));
			}
		}
	}

	/**
	 * 當訪問model中不存在的方法時直接調用相關model中的db()的相關方法
	 *
	 * @param $dbMethod
	 * @param $arguments
	 *
	 * @return \Cml\Db\MySql\Pdo | \Cml\Db\MongoDB\MongoDB | self
	 */
	public static function __callStatic($dbMethod, $arguments)
	{
		$res = call_user_func_array([static::getInstance()->db(static::getInstance()->getDbConf()), $dbMethod], $arguments);
		if ($res instanceof Interfaces\Db) {
			return self::getInstance();//不是返回數據直接返回model實例
		} else {
			return $res;
		}
	}

	/**
	 * 當程序連接N個db的時候用於釋放於用連接以節省內存
	 *
	 * @param string $conf 使用的數據庫配置;
	 */
	public function closeDb($conf = 'default_db')
	{
		//$this->db($conf)->close();釋放對像時會執行析構回收
		unset($this->dbInstance[$conf]);
	}

	/**
	 * 設置查詢數據緩存時間
	 *
	 *  表數據有變動會自動更新緩存。設置為0表示表數據沒變動時緩存不過期。
	 * 這邊設置為3600意思是即使表數據沒變動也讓緩存每3600s失效一次,這樣可以讓緩存空間更合理的利用.
	 * 如果不想啟用緩存直接配置為false
	 * 默認為null： 使用 db配置中的cache_expire
	 * @param mixed $cacheExpire
	 *
	 * @return $this
	 */
	public function setCacheExpire($cacheExpire = null)
	{
		$this->cacheExpire = $cacheExpire;

		return $this;
	}

	/**
	 * 通過某個字段獲取單條數據-快捷方法
	 *
	 * @param mixed $val 值
	 * @param string $column 字段名 不傳會自動分析表結構獲取主鍵
	 * @param string $tableName 表名 不傳會自動從當前Model中$table屬性獲取
	 * @param mixed $tablePrefix 表前綴 不傳會自動從當前Model中$tablePrefix屬性獲取再沒有則獲取配置中配置的前綴
	 *
	 * @return bool|array
	 */
	public function getByColumn($val, $column = null, $tableName = null, $tablePrefix = null)
	{
		is_null($tableName) && $tableName = $this->getTableName();
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		is_null($column) && $column = $this->db($this->getDbConf())->getPk($tableName, $tablePrefix);
		return $this->db($this->getDbConf())->table($tableName, $tablePrefix)
			->where($column, $val)
			->getOne($this->useMaster);
	}

	/**
	 * 獲取表名
	 *
	 * @param bool $addTablePrefix 是否返回帶表前綴的完整表名
	 * @param bool $addDbName 是否帶上dbname
	 *
	 * @return string
	 */
	public function getTableName($addTablePrefix = false, $addDbName = false)
	{
		if (is_null($this->table)) {
			$tmp = get_class($this);
			$this->table = strtolower(substr($tmp, strrpos($tmp, '\\') + 1, -5));
		}

		$dbName = $addDbName ? Config::get($this->getDbConf() . '.master.dbname') . '.' : '';

		if ($addTablePrefix) {
			$tablePrefix = $this->tablePrefix;
			$tablePrefix || $tablePrefix = Config::get($this->getDbConf() . '.master.tableprefix');
			return $dbName . $tablePrefix . $this->table;
		}
		return $dbName . $this->table;
	}

	/**
	 * 獲取當前Model的數據庫配置串
	 *
	 * @return string
	 */
	public function getDbConf()
	{
		return $this->db;
	}

	/**
	 * 獲取db實例
	 *
	 * @param string $conf 使用的數據庫配置;
	 *
	 * @return Db
	 */
	public function db($conf = '')
	{
		$conf == '' && $conf = $this->getDbConf();
		if (is_array($conf)) {
			$config = $conf;
			$conf = md5(json_encode($conf));
		} else {
			$config = Config::get($conf);
		}
		$config['mark'] = $conf;

		if (isset($this->dbInstance[$conf])) {
			return $this->dbInstance[$conf];
		} else {
			$pos = strpos($config['driver'], '.');
			is_null($this->cacheExpire) || $config['cache_expire'] = $this->cacheExpire;
			$this->dbInstance[$conf] = Cml::getContainer()->make('db_' . strtolower($pos ? substr($config['driver'], 0, $pos) : $config['driver']), $config);
			return $this->dbInstance[$conf];
		}
	}

	/**
	 * 通過某個字段獲取多條數據-快捷方法
	 *
	 * @param mixed $val 值
	 * @param string $column 字段名 不傳會自動分析表結構獲取主鍵
	 * @param string $tableName 表名 不傳會自動從當前Model中$table屬性獲取
	 * @param mixed $tablePrefix 表前綴 不傳會自動從當前Model中$tablePrefix屬性獲取再沒有則獲取配置中配置的前綴
	 *
	 * @return bool|array
	 */
	public function getMultiByColumn($val, $column = null, $tableName = null, $tablePrefix = null)
	{
		is_null($tableName) && $tableName = $this->getTableName();
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		is_null($column) && $column = $this->db($this->getDbConf())->getPk($tableName, $tablePrefix);
		return $this->db($this->getDbConf())->table($tableName, $tablePrefix)
			->where($column, $val)
			->select(null, null, $this->useMaster);
	}

	/**
	 * 增加一條數據-快捷方法
	 *
	 * @param array $data 要新增的數據
	 * @param string $tableName 表名 不傳會自動從當前Model中$table屬性獲取
	 * @param mixed $tablePrefix 表前綴 不傳會自動從當前Model中$tablePrefix屬性獲取再沒有則獲取配置中配置的前綴
	 *
	 * @return int
	 */
	public function set($data, $tableName = null, $tablePrefix = null)
	{
		is_null($tableName) && $tableName = $this->getTableName();
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		return $this->db($this->getDbConf())->set($tableName, $data, $tablePrefix);
	}

	/**
	 * 增加多條數據-快捷方法
	 *
	 * @param array $field 要插入的字段 eg: ['title', 'msg', 'status', 'ctime』]
	 * @param array $data 多條數據的值 eg:  [['標題1', '內容1', 1, '2017'], ['標題2', '內容2', 1, '2017']]
	 * @param string $tableName 表名 不傳會自動從當前Model中$table屬性獲取
	 * @param mixed $tablePrefix 表前綴 不傳會自動從當前Model中$tablePrefix屬性獲取再沒有則獲取配置中配置的前綴
	 * @param bool $openTransAction 是否開啟事務 默認開啟
	 *
	 * @return bool | array
	 */
	public function setMulti($field, $data, $tableName = null, $tablePrefix = null, $openTransAction = true)
	{
		is_null($tableName) && $tableName = $this->getTableName();
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		return $this->db($this->getDbConf())->setMulti($tableName, $field, $data, $tablePrefix, $openTransAction);
	}

	/**
	 * 插入或更新一條記錄，當UNIQUE index or PRIMARY KEY存在的時候更新，不存在的時候插入
	 * 若AUTO_INCREMENT存在則返回 AUTO_INCREMENT 的值.
	 *
	 * @param array $data 插入的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
	 * @param array $up 更新的值-會自動merge $data中的數據
	 * @param string $tableName 表名 不傳會自動從當前Model中$table屬性獲取
	 * @param mixed $tablePrefix 表前綴 不傳會自動從當前Model中$tablePrefix屬性獲取再沒有則獲取配置中配置的前綴
	 *
	 * @return int
	 */
	public function upSet(array $data, array $up = [], $tableName = null, $tablePrefix = null)
	{
		is_null($tableName) && $tableName = $this->getTableName();
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		return $this->db($this->getDbConf())->upSet($tableName, $data, $up, $tablePrefix);
	}

	/**
	 * 插入或替換多條記錄
	 *
	 * @param array $field 要插入的字段 eg: ['title', 'msg', 'status', 'ctime』]
	 * @param array $data 多條數據的值 eg:  [['標題1', '內容1', 1, '2017'], ['標題2', '內容2', 1, '2017']]
	 * @param string $tableName 表名 不傳會自動從當前Model中$table屬性獲取
	 * @param mixed $tablePrefix 表前綴 不傳會自動從當前Model中$tablePrefix屬性獲取再沒有則獲取配置中配置的前綴
	 * @param bool $openTransAction 是否開啟事務 默認開啟
	 *
	 * @return bool | array
	 */
	public function replaceMulti($field, $data, $tableName = null, $tablePrefix = null, $openTransAction = true)
	{
		is_null($tableName) && $tableName = $this->getTableName();
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		return $this->db($this->getDbConf())->replaceMulti($tableName, $field, $data, $tablePrefix, $openTransAction);
	}

	/**
	 * 插入或替換一條記錄
	 * 若AUTO_INCREMENT存在則返回 AUTO_INCREMENT 的值.
	 *
	 * @param array $data 插入/更新的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
	 * @param string $tableName 表名 不傳會自動從當前Model中$table屬性獲取
	 * @param mixed $tablePrefix 表前綴 不傳會自動從當前Model中$tablePrefix屬性獲取再沒有則獲取配置中配置的前綴
	 *
	 * @return int
	 */
	public function replaceInto(array $data, $tableName = null, $tablePrefix = null)
	{
		is_null($tableName) && $tableName = $this->getTableName();
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		return $this->db($this->getDbConf())->replaceInto($tableName, $data, $tablePrefix);
	}

	/**
	 * 通過字段更新數據-快捷方法
	 *
	 * @param int $val 字段值
	 * @param array $data 更新的數據
	 * @param string $column 字段名 不傳會自動分析表結構獲取主鍵
	 * @param string $tableName 表名 不傳會自動從當前Model中$table屬性獲取
	 * @param mixed $tablePrefix 表前綴 不傳會自動從當前Model中$tablePrefix屬性獲取再沒有則獲取配置中配置的前綴
	 *
	 * @return bool
	 */
	public function updateByColumn($val, $data, $column = null, $tableName = null, $tablePrefix = null)
	{
		is_null($tableName) && $tableName = $this->getTableName();
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		is_null($column) && $column = $this->db($this->getDbConf())->getPk($tableName, $tablePrefix);
		return $this->db($this->getDbConf())->where($column, $val)
			->update($tableName, $data, true, $tablePrefix);
	}

	/**
	 * 通過主鍵刪除數據-快捷方法
	 *
	 * @param mixed $val
	 * @param string $column 字段名 不傳會自動分析表結構獲取主鍵
	 * @param string $tableName 表名 不傳會自動從當前Model中$table屬性獲取
	 * @param mixed $tablePrefix 表前綴 不傳會自動從當前Model中$tablePrefix屬性獲取再沒有則獲取配置中配置的前綴
	 *
	 * @return bool
	 */
	public function delByColumn($val, $column = null, $tableName = null, $tablePrefix = null)
	{
		is_null($tableName) && $tableName = $this->getTableName();
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		is_null($column) && $column = $this->db($this->getDbConf())->getPk($tableName, $tablePrefix);
		return $this->db($this->getDbConf())->where($column, $val)
			->delete($tableName, true, $tablePrefix);
	}

	/**
	 * 獲取數據的總數
	 *
	 * @param null $pkField 主鍵的字段名
	 * @param string $tableName 表名 不傳會自動從當前Model中$table屬性獲取
	 * @param mixed $tablePrefix 表前綴 不傳會自動從當前Model中$tablePrefix屬性獲取再沒有則獲取配置中配置的前綴
	 *
	 * @return mixed
	 */
	public function getTotalNums($pkField = null, $tableName = null, $tablePrefix = null)
	{
		is_null($tableName) && $tableName = $this->getTableName();
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		is_null($pkField) && $pkField = $this->db($this->getDbConf())->getPk($tableName, $tablePrefix);
		return $this->db($this->getDbConf())->table($tableName, $tablePrefix)->count($pkField, false, $this->useMaster);
	}

	/**
	 * 獲取數據列表
	 *
	 * @param int $offset 偏移量
	 * @param int $limit 返回的條數
	 * @param string|array $order 傳asc 或 desc 自動取主鍵 或 ['id'=>'desc', 'status' => 'asc']
	 * @param string $tableName 表名 不傳會自動從當前Model中$table屬性獲取
	 * @param mixed $tablePrefix 表前綴 不傳會自動從當前Model中$tablePrefix屬性獲取再沒有則獲取配置中配置的前綴
	 *
	 * @return array
	 */
	public function getList($offset = 0, $limit = 20, $order = 'DESC', $tableName = null, $tablePrefix = null)
	{
		is_null($tableName) && $tableName = $this->getTableName();
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		is_array($order) || $order = [$this->db($this->getDbConf())->getPk($tableName, $tablePrefix) => $order];

		$dbInstance = $this->db($this->getDbConf())->table($tableName, $tablePrefix);
		foreach ($order as $key => $val) {
			$dbInstance->orderBy($key, $val);
		}
		return $dbInstance->limit($offset, $limit)
			->select(null, null, $this->useMaster);
	}

	/**
	 * 以分頁的方式獲取數據列表
	 *
	 * @param int $limit 每頁返回的條數
	 * @param string|array $order 傳asc 或 desc 自動取主鍵 或 ['id'=>'desc', 'status' => 'asc']
	 * @param string $tableName 表名 不傳會自動從當前Model中$table屬性獲取
	 * @param mixed $tablePrefix 表前綴 不傳會自動從當前Model中$tablePrefix屬性獲取再沒有則獲取配置中配置的前綴
	 *
	 * @return array
	 */
	public function getListByPaginate($limit = 20, $order = 'DESC', $tableName = null, $tablePrefix = null)
	{
		is_null($tableName) && $tableName = $this->getTableName();
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		is_array($order) || $order = [$this->db($this->getDbConf())->getPk($tableName, $tablePrefix) => $order];

		$dbInstance = $this->db($this->getDbConf())->table($tableName, $tablePrefix);
		foreach ($order as $key => $val) {
			$dbInstance->orderBy($key, $val);
		}
		return $dbInstance->paginate($limit, $this->useMaster);
	}

	/**
	 * 當訪問model中不存在的方法時直接調用$this->db()的相關方法
	 *
	 * @param $dbMethod
	 * @param $arguments
	 *
	 * @return \Cml\Db\MySql\Pdo | \Cml\Db\MongoDB\MongoDB | $this
	 */
	public function __call($dbMethod, $arguments)
	{
		$res = call_user_func_array([$this->db($this->getDbConf()), $dbMethod], $arguments);
		if ($res instanceof Interfaces\Db) {
			return $this;//不是返回數據直接返回model實例
		} else {
			return $res;
		}
	}

	/**
	 * 根據條件是否成立執行對應的閉包
	 *
	 * @param bool $condition 條件
	 * @param callable $trueCallback 條件成立執行的閉包
	 * @param callable|null $falseCallback 條件不成立執行的閉包
	 *
	 * @return Db | $this
	 */
	public function when($condition, callable $trueCallback, callable $falseCallback = null)
	{
		if ($condition) {
			call_user_func($trueCallback, $this);
		} else {
			is_callable($falseCallback) && call_user_func($falseCallback, $this);
		}
		return $this;
	}
}
