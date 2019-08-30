<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 緩存驅動抽像接口
 * *********************************************************** */

namespace Cml\Interfaces;

/**
 * Orm 數據庫抽像接口
 *
 * @package Cml\Interfaces
 */
interface Db
{
	/**
	 * Db constructor.
	 *
	 * @param $conf
	 */
	public function __construct($conf);

	/**
	 * 定義操作的表
	 *
	 * @param string|array $table 表名 要取別名時使用 [不帶前綴表名 => 別名]
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return $this
	 */
	public function table($table = '', $tablePrefix = null);

	/**
	 * 獲取當前db所有表名
	 *
	 * @return array
	 */
	public function getTables();

	/**
	 * 獲取當前數據庫中所有表的信息
	 *
	 * @return array
	 */
	public function getAllTableStatus();

	/**
	 * 獲取表字段
	 *
	 * @param string $table 表名
	 * @param mixed $tablePrefix 表前綴，不傳則獲取配置中配置的前綴
	 * @param int $filter 0 獲取表字段詳細信息數組 1獲取字段以,號相隔組成的字符串
	 *
	 * @return mixed
	 */
	public function getDbFields($table, $tablePrefix = null, $filter = 0);


	/**
	 * 根據key取出數據
	 *
	 * @param string $key get('user-uid-123');
	 * @param bool $and 多個條件之間是否為and  true為and false為or
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫 此選項為字符串時為表前綴$tablePrefix
	 * @param null|string $tablePrefix 表前綴
	 *
	 * @return array
	 */
	public function get($key, $and = true, $useMaster = false, $tablePrefix = null);

	/**
	 * 根據key 新增 一條數據
	 *
	 * @param string $table
	 * @param array $data eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return bool|int
	 */
	public function set($table, $data, $tablePrefix = null);

	/**
	 * 新增多條數據
	 *
	 * @param string $table
	 * @param array $field 字段 eg: ['title', 'msg', 'status', 'ctime『]
	 * @param array $data eg: 多條數據的值 [['標題1', '內容1', 1, '2017'], ['標題2', '內容2', 1, '2017']]
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 * @param bool $openTransAction 是否開啟事務 默認開啟
	 * @return bool|array
	 * @throws \InvalidArgumentException
	 *
	 */
	public function setMulti($table, $field, $data, $tablePrefix = null, $openTransAction = true);

	/**
	 * 插入或替換一條記錄
	 * 若AUTO_INCREMENT存在則返回 AUTO_INCREMENT 的值.
	 *
	 * @param string $table 表名
	 * @param array $data 插入/更新的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return int
	 */
	public function replaceInto($table, array $data, $tablePrefix = null);

	/**
	 * 插入或更新一條記錄
	 *
	 * @param string $table 表名
	 * @param array $data 插入的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
	 * @param array $up 更新的值-會自動merge $data中的數據
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return int
	 */
	public function upSet($table, array $data, array $up = [], $tablePrefix = null);

	/**
	 * 根據key更新一條數據
	 *
	 * @param string|array $key eg: 'user'(表名)、'user-uid-$uid'(表名+條件) 、['xx'=>'xx' ...](即:$data數組如果條件是通用whereXX()、表名是通過table()設定。這邊可以直接傳$data的數組)
	 * @param array | null $data eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com'] 可以直接通過$key參數傳遞
	 * @param bool $and 多個條件之間是否為and  true為and false為or
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return boolean
	 */
	public function update($key, $data = null, $and = true, $tablePrefix = null);

	/**
	 * 根據key值刪除數據
	 *
	 * @param string $key eg: 'user'(表名，即條件通過where()傳遞)、'user-uid-$uid'(表名+條件)、啥也不傳(即通過table傳表名)
	 * @param bool $and 多個條件之間是否為and  true為and false為or
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return boolean
	 */
	public function delete($key = '', $and = true, $tablePrefix = null);

	/**
	 * 根據表名刪除數據
	 *
	 * @param string $tableName 要清空的表名
	 *
	 * @return boolean
	 */
	public function truncate($tableName);

	/**
	 * 構建sql
	 *
	 * @param null $offset 偏移量
	 * @param null $limit 返回的條數
	 * @param bool $isSelect 是否為select調用， 是則不重置查詢參數並返回cacheKey/否則直接返回sql並重置查詢參數
	 *
	 * @return string|array
	 */
	public function buildSql($offset = null, $limit = null, $isSelect = false);

	/**
	 * 獲取多條數據
	 *
	 * @param int $offset 偏移量
	 * @param int $limit 返回的條數
	 * @param bool $useMaster 是否使用主庫 默認讀取從庫
	 * @param mixed $fieldAsKey 返回以某個字段做為key的數組
	 *
	 * @return array
	 */
	public function select($offset = null, $limit = null, $useMaster = false, $fieldAsKey = false);

	/**
	 * 分頁獲取數據
	 *
	 * @param int $limit 每頁返回的條數
	 * @param bool $useMaster 是否使用主庫 默認讀取從庫
	 * @param null|int $page 當前頁數-不傳則獲取配置中var_page配置的request值
	 * @param mixed $fieldAsKey 返回以某個字段做為key的數組
	 *
	 * @return array
	 */
	public function paginate($limit, $useMaster = false, $page = null, $fieldAsKey = false);

	/**
	 * 獲取表主鍵
	 *
	 * @param string $table 要獲取主鍵的表名
	 * @param string $tablePrefix 表前綴
	 *
	 * @return string || false
	 */
	public function getPk($table, $tablePrefix = null);

	/**
	 * 獲取一條數據
	 *
	 * @param bool $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return array | bool
	 */
	public function getOne($useMaster = false);

	/**
	 * 獲取一列
	 *
	 * @param string $column 列名
	 * @param bool $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return bool|mixed
	 */
	public function getOneValue($column, $useMaster = false);

	/**
	 * 獲取數據列值列表
	 *
	 * @param string $column 列名
	 * @param null $key 返回數組中為列值指定自定義鍵（該自定義鍵必須是該表的其它字段列名）
	 * @param int $limit 返回的條數
	 * @param bool $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return array
	 */
	public function plunk($column, $key = null, $limit = null, $useMaster = false);

	/**
	 * 組塊結果集-此方法前調用paramsAutoReset無效
	 *
	 * @param int $num 每次獲取的條數
	 * @param callable $func 結果集處理函數
	 */
	public function chunk($num = 100, callable $func);

	/**
	 * where條件組裝 相等
	 *
	 * @param string|array $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名) 當$column為數組時 批量設置
	 * @param string |int $value 當$column為數組時  此時$value為false時條件為or 否則為and
	 *
	 * @return $this
	 */
	public function where($column, $value = '');

	/**
	 * where條件組裝 兩個列相等
	 *
	 * @param string $column eg：username | `user`.`username`
	 * @param string $column2 eg: nickname | `user`.`nickname`
	 *
	 * @return $this
	 */
	public function whereColumn($column, $column2);

	/**
	 * where條件原生條件
	 *
	 * @param string $where eg：utime > ctime + ?
	 * @param array $params eg: [10]
	 *
	 * @return $this
	 */
	public function whereRaw($where, $params);

	/**
	 * 根據條件是否成立執行對應的閉包
	 *
	 * @param bool $condition 條件
	 * @param callable $trueCallback 條件成立執行的閉包
	 * @param callable|null $falseCallback 條件不成立執行的閉包
	 *
	 * @return $this
	 */
	public function when($condition, callable $trueCallback, callable $falseCallback = null);

	/**
	 * where條件組裝 不等
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int $value
	 *
	 * @return $this
	 */
	public function whereNot($column, $value);

	/**
	 * where條件組裝 大於
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int $value
	 *
	 * @return $this
	 */
	public function whereGt($column, $value);

	/**
	 * where條件組裝 小於
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int $value
	 *
	 * @return $this
	 */
	public function whereLt($column, $value);

	/**
	 * where條件組裝 大於等於
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int $value
	 *
	 * @return $this
	 */
	public function whereGte($column, $value);

	/**
	 * where條件組裝 小於等於
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int $value
	 *
	 * @return $this
	 */
	public function whereLte($column, $value);

	/**
	 * where條件組裝 in
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param array $value
	 *
	 * @return $this
	 */
	public function whereIn($column, $value);

	/**
	 * where條件組裝 not in
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param array $value [1,2,3]
	 *
	 * @return $this
	 */
	public function whereNotIn($column, $value);

	/**
	 * where條件組裝 REGEXP
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int $value
	 *
	 * @return $this
	 */
	public function whereRegExp($column, $value);

	/**
	 * where條件組裝 LIKE
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param bool $leftBlur 是否開始左模糊匹配
	 * @param string |int $value
	 * @param bool $rightBlur 是否開始右模糊匹配
	 *
	 * @return $this
	 */
	public function whereLike($column, $leftBlur = false, $value, $rightBlur = false);

	/**
	 * where條件組裝 LIKE
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param bool $leftBlur 是否開始左模糊匹配
	 * @param string |int $value
	 * @param bool $rightBlur 是否開始右模糊匹配
	 *
	 * @return $this
	 */
	public function whereNotLike($column, $leftBlur = false, $value, $rightBlur = false);


	/**
	 * where條件組裝 BETWEEN
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int | array $value
	 * @param string |int | null $value2
	 *
	 * @return $this
	 */
	public function whereBetween($column, $value, $value2 = null);

	/**
	 * where條件組裝 NOT BETWEEN
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int | array $value
	 * @param string |int | null $value2
	 *
	 * @return $this
	 */
	public function whereNotBetween($column, $value, $value2 = null);

	/**
	 * where條件組裝 IS NULL
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 *
	 * @return $this
	 */
	public function whereNull($column);

	/**
	 * where條件組裝 IS NOT NULL
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 *
	 * @return $this
	 */
	public function whereNotNull($column);

	/**
	 * where 語句組裝工廠
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param array|int|string $value 值
	 * @param string $operator 操作符
	 *
	 * @return $this
	 */
	public function conditionFactory($column, $value, $operator = '=');

	/**
	 * 增加 and條件操作符
	 *
	 * @param callable $callable 如果傳入函數則函數內執行的條件會被()包圍
	 *
	 * @return $this
	 */
	public function _and(callable $callable = null);

	/**
	 * 增加or條件操作符
	 *
	 * @param callable $callable 如果傳入函數則函數內執行的條件會被()包圍
	 *
	 * @return $this
	 */
	public function _or(callable $callable = null);

	/**
	 * where條件增加左括號
	 *
	 * @return $this
	 */
	public function lBrackets();

	/**
	 * where條件增加右括號
	 *
	 * @return $this
	 */
	public function rBrackets();

	/**
	 * 選擇列
	 *
	 * @param string|array $columns 默認選取所有 ['id, 'name']
	 * 選取id,name兩列，['article.id' => 'aid', 'article.title' =>　'article_title'] 別名
	 *
	 * @return $this
	 */
	public function columns($columns = '*');

	/**
	 * LIMIT
	 *
	 * @param int $offset 偏移量
	 * @param int $limit 返回的條數
	 *
	 * @return $this
	 */
	public function limit($offset = 0, $limit = 10);

	/**
	 * 排序
	 *
	 * @param string $column 要排序的字段
	 * @param string $order 方向,默認為正序
	 *
	 * @return $this
	 */
	public function orderBy($column, $order = 'ASC');

	/**
	 * 分組
	 *
	 * @param string $column 要設置分組的字段名
	 *
	 * @return $this
	 */
	public function groupBy($column);

	/**
	 * having語句
	 *
	 * @param string $column 字段名
	 * @param string $operator 操作符
	 * @param string $value 值
	 *
	 * @return $this
	 */
	public function having($column, $operator = '=', $value);

	/**
	 * join內聯結
	 *
	 * @param string|array $table 表名 要取別名時使用 [不帶前綴表名 => 別名]
	 * @param string $on 聯結的條件 如：'c.cid = a.cid'
	 * @param mixed $tablePrefix 表前綴
	 *
	 * @return $this
	 */
	public function join($table, $on, $tablePrefix = null);

	/**
	 * leftJoin左聯結
	 *
	 * @param string|array $table 表名 要取別名時使用 [不帶前綴表名 => 別名]
	 * @param string $on 聯結的條件 如：'c.cid = a.cid'
	 * @param mixed $tablePrefix 表前綴
	 *
	 * @return $this
	 */
	public function leftJoin($table, $on, $tablePrefix = null);

	/**
	 * rightJoin右聯結
	 *
	 * @param string|array $table 表名 要取別名時使用 [不帶前綴表名 => 別名]
	 * @param string $on 聯結的條件 如：'c.cid = a.cid'
	 * @param mixed $tablePrefix 表前綴
	 *
	 * @return $this
	 */
	public function rightJoin($table, $on, $tablePrefix = null);

	/**
	 * union聯結
	 *
	 * @param string|array $sql 要union的sql
	 * @param bool $all 是否為union all
	 *
	 * @return $this
	 */
	public function union($sql, $all = false);

	/**
	 * 獲取 COUNT(字段名或*) 的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool $isMulti 結果集是否為多條 默認只有一條
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function count($field = '*', $isMulti = false, $useMaster = false);

	/**
	 * 獲取 MAX(字段名或*) 的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時相當於執行了 groupBy($isMulti)
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function max($field = '*', $isMulti = false, $useMaster = false);

	/**
	 * 獲取 MIN(字段名或*) 的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時相當於執行了 groupBy($isMulti)
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function min($field = '*', $isMulti = false, $useMaster = false);

	/**
	 * 獲取 SUM(字段名或*) 的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時相當於執行了 groupBy($isMulti)
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function sum($field = '*', $isMulti = false, $useMaster = false);

	/**
	 * 獲取 AVG(字段名或*) 的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時相當於執行了 groupBy($isMulti)
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function avg($field = '*', $isMulti = false, $useMaster = false);

	/**
	 * 返回INSERT，UPDATE 或 DELETE 查詢所影響的記錄行數。
	 *
	 * @param resource $handle mysql link
	 * @param int $type 執行的類型1:insert、2:update、3:delete
	 *
	 * @return int
	 */
	public function affectedRows($handle, $type);

	/**
	 *獲取上一INSERT的主鍵值
	 *
	 * @param resource $link
	 *
	 * @return int
	 */
	public function insertId($link = null);

	/**
	 * 指定字段的值+1
	 *
	 * @param string $key 操作的key eg: user-id-1
	 * @param int $val
	 * @param string $field 要改變的字段
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return bool
	 */
	public function increment($key, $val = 1, $field = null, $tablePrefix = null);

	/**
	 * 指定字段的值-1
	 *
	 * @param string $key 操作的key user-id-1
	 * @param int $val
	 * @param string $field 要改變的字段
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return bool
	 */
	public function decrement($key, $val = 1, $field = null, $tablePrefix = null);

	/**
	 * Db連接
	 *
	 * @param string $host 數據庫host
	 * @param string $username 數據庫用戶名
	 * @param string $password 數據庫密碼
	 * @param string $dbName 數據庫名
	 * @param string $charset 字符集
	 * @param string $engine 引擎
	 * @param bool $pConnect 是否為長連接
	 *
	 * @return mixed
	 */
	public function connect($host, $username, $password, $dbName, $charset = 'utf8', $engine = '', $pConnect = false);

	/**
	 * 析構函數
	 *
	 */
	public function __destruct();

	/**
	 * 獲取數據庫 版本
	 *
	 * @param resource $link
	 *
	 * @return string
	 */
	public function version($link = null);

	/**
	 * 開啟事務
	 *
	 * @return bool
	 */
	public function startTransAction();

	/**
	 * 提交事務
	 *
	 * @return bool
	 */
	public function commit();

	/**
	 * 設置一個事務保存點
	 *
	 * @param string $pointName 保存點名稱
	 *
	 * @return bool
	 */
	public function savePoint($pointName);

	/**
	 * 回滾事務
	 *
	 * @param bool $rollBackTo 是否為還原到某個保存點
	 *
	 * @return bool
	 */
	public function rollBack($rollBackTo = false);

	/**
	 * 調用存儲過程
	 * 如 : callProcedure('user_check ?,?  ', [1, 1], true) pdo
	 *
	 * @param string $procedureName 要調用的存儲過程名稱
	 * @param array $bindParams 綁定的參數
	 * @param bool|true $isSelect 是否為返回數據集的語句
	 *
	 * @return array|int
	 */
	public function callProcedure($procedureName = '', $bindParams = [], $isSelect = true);

	/**
	 * 關閉連接
	 *
	 */
	public function close();

}
