<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Db 數據庫抽像基類
 * *********************************************************** */

namespace Cml\Db;

use Cml\Config;
use Cml\Http\Input;
use Cml\Interfaces\Db;
use Cml\Lang;
use Cml\Model;

/**
 * Orm 數據庫抽像基類
 *
 * @package Cml\Db
 */
abstract class Base implements Db
{
	/**
	 * 多個Model中共享db連接實例
	 *
	 * @var array
	 */
	protected static $dbInst = [
	];
	/**
	 * 表前綴方便外部讀取
	 *
	 * @var string
	 */
	public $tablePrefix;
	/**
	 * 啟用數據緩存
	 *
	 * @var bool
	 */
	protected $openCache = false;
	/**
	 * 單獨標記當前的query使不使用緩存
	 *
	 * @var bool
	 */
	protected $currentQueryUseCache = true;
	/**
	 * where操作需要加上and/or
	 * 0 : 初始化兩個都不加
	 * 1 : 要加and
	 * 2： 要加 or
	 *
	 * @var int
	 */
	protected $whereNeedAddAndOrOr = 0;
	/**
	 * 執行sql時綁定的參數
	 *
	 * @var array
	 */
	protected $bindParams = [];
	/**
	 * 配置信息
	 *
	 * @var array
	 */
	protected $conf;
	/**
	 * sql組裝
	 *
	 * @var array
	 */
	protected $sql = [
		'where' => '',
		'columns' => '',
		'limit' => '',
		'orderBy' => '',
		'groupBy' => '',
		'having' => '',
	];

	/**
	 * 操作的表
	 *
	 * @var array
	 */
	protected $table = [];

	/**
	 * 是否內聯 [表名 => 條件]
	 *
	 * @var array
	 */
	protected $join = [];

	/**
	 * 是否左聯結 寫法同內聯
	 *
	 * @var array
	 */
	protected $leftJoin = [];

	/**
	 * 是否右聯 寫法同內聯
	 *
	 * @var array
	 */
	protected $rightJoin = [];

	/**
	 * UNION 寫法同內聯
	 *
	 * @var string
	 */
	protected $union = '';

	/**
	 * orm參數是否自動重置
	 *
	 * @var bool
	 */
	protected $paramsAutoReset = true;

	/**
	 * $paramsAutoReset = false 的時候是否清除table.避免快捷方法重複調用table();
	 *
	 * @var bool
	 */
	protected $alwaysClearTable = false;

	/**
	 * $paramsAutoReset = false 的時候是否清除查詢的字段信息.主要用於按批獲取數據不用多次調用columns();
	 *
	 * @var bool
	 */
	protected $alwaysClearColumns = true;

	/**
	 * 魔術方法 自動獲取相應db實例
	 *
	 * @param string $db 要連接的數據庫類型
	 *
	 * @return  resource|false 數據庫 連接標識
	 */
	public function __get($db)
	{
		if (isset(self::$dbInst[$this->conf['mark'] . $db])) {
			return self::$dbInst[$this->conf['mark'] . $db];
		}
		return $this->connectDb($db);
	}

	/**
	 * 連接數據庫
	 *
	 * @param string $db rlink/wlink
	 * @param bool $reConnect 是否重連--用於某些db如mysql.長連接被服務端斷開的情況
	 *
	 * @return bool|false|mixed|resource
	 */
	protected function connectDb($db, $reConnect = false)
	{
		if ($db == 'rlink') {
			//如果沒有指定從數據庫，則使用 master
			if (empty($this->conf['slaves'])) {
				self::$dbInst[$this->conf['mark'] . $db] = $this->rlink = $reConnect ? $this->connectDb('wlink', true) : $this->wlink;
				return $this->rlink;
			}

			$n = mt_rand(0, count($this->conf['slaves']) - 1);
			$conf = $this->conf['slaves'][$n];
			empty($conf['engine']) && $conf['engine'] = '';
			self::$dbInst[$this->conf['mark'] . $db] = $this->rlink = $this->connect(
				$conf['host'],
				$conf['username'],
				$conf['password'],
				$conf['dbname'],
				$conf['charset'],
				$conf['engine'],
				$conf['pconnect']
			);
			return $this->rlink;
		} elseif ($db == 'wlink') {
			$conf = $this->conf['master'];
			empty($conf['engine']) && $conf['engine'] = '';
			self::$dbInst[$this->conf['mark'] . $db] = $this->wlink = $this->connect(
				$conf['host'],
				$conf['username'],
				$conf['password'],
				$conf['dbname'],
				$conf['charset'],
				$conf['engine'],
				$conf['pconnect']
			);
			return $this->wlink;
		}
		return false;
	}

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
	public function paginate($limit, $useMaster = false, $page = null, $fieldAsKey = false)
	{
		is_int($page) || $page = Input::requestInt(Config::get('var_page'), 1);
		$page < 1 && $page = 1;
		return call_user_func_array([$this, 'select'], [($page - 1) * $limit, $limit, $useMaster, $fieldAsKey]);
	}

	/**
	 * 獲取表主鍵
	 *
	 * @param string $table 要獲取主鍵的表名
	 * @param string $tablePrefix 表前綴，不傳則獲取配置中配置的前綴
	 *
	 * @return string || false
	 */
	public function getPk($table, $tablePrefix = null)
	{
		$rows = $this->getDbFields($table, $tablePrefix);
		foreach ($rows as $val) {
			if ($val['primary']) {
				return $val['name'];
			}
		}
		return false;
	}

	/**
	 * 獲取一列
	 *
	 * @param string $column 列名
	 * @param bool $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return bool|mixed
	 */
	public function getOneValue($column, $useMaster = false)
	{
		$this->sql['columns'] == '' && $this->columns($column);
		$data = $this->getOne($useMaster);
		return isset($data[$column]) ? $data[$column] : false;
	}

	/**
	 * 選擇列
	 *
	 * @param string|array $columns 默認選取所有 ['id, 'name']
	 * 選取id,name兩列，['article.id' => 'aid', 'article.title' =>　'article_title'] 別名
	 *
	 * @return $this
	 */
	public function columns($columns = '*')
	{
		$result = '';
		if (is_array($columns)) {
			foreach ($columns as $key => $val) {
				$result .= ($result == '' ? '' : ', ') . (is_int($key) ? $val : ($key . " AS `{$val}`"));
			}
		} else {
			$result = implode(', ', func_get_args());
		}
		$this->sql['columns'] == '' || ($this->sql['columns'] .= ' ,');
		$this->sql['columns'] .= $result;
		return $this;
	}

	/**
	 * 獲取一條數據
	 *
	 * @param bool $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return array | bool
	 */
	public function getOne($useMaster = false)
	{
		$result = $this->select(0, 1, $useMaster);
		if (isset($result[0])) {
			return $result[0];
		} else {
			return false;
		}
	}

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
	public function plunk($column, $key = null, $limit = null, $useMaster = false)
	{
		$this->sql['columns'] == '' && $this->columns(is_null($key) ? $column : [$key, $column]);
		$result = $this->select(0, $limit, $useMaster);
		$return = [];
		foreach ($result as $row) {
			is_null($key) ? $return[] = $row[$column] : $return[$row[$key]] = $row[$column];
		}
		return $return;
	}

	/**
	 * 組塊結果集-此方法前調用paramsAutoReset無效
	 *
	 * @param int $num 每次獲取的條數
	 * @param callable $func 結果集處理函數。本回調函數內調用paramsAutoReset無效
	 */
	public function chunk($num = 100, callable $func)
	{
		$this->paramsAutoReset();
		$start = 0;
		$backComdition = $this->sql;//sql組裝
		$backTable = $this->table;//操作的表
		$backJoin = $this->join;//是否內聯
		$backleftJoin = $this->leftJoin;//是否左聯結
		$backrightJoin = $this->rightJoin;//是否右聯
		$backBindParams = $this->bindParams;

		while ($result = $this->select($start, $num)) {
			if ($func($result) === false) {
				break;
			}
			$start += count($result);

			$this->sql = $backComdition;//sql組裝
			$this->table = $backTable;//操作的表
			$this->join = $backJoin;//是否內聯
			$this->leftJoin = $backleftJoin;//是否左聯結
			$this->rightJoin = $backrightJoin;//是否右聯
			$this->bindParams = $backBindParams;
		}
		$this->paramsAutoReset();
		$this->reset();
		$this->clearBindParams();
	}

	/**
	 * orm參數是否自動重置, 默認在執行語句後會重置orm參數,包含查詢的表、字段信息、條件等信息
	 *
	 * @param bool $autoReset 是否自動重置 查詢的表、字段信息、條件等信息
	 * @param bool $alwaysClearTable 用來控制在$paramsAutoReset = false 的時候是否清除查詢的table信息.避免快捷方法重複調用table();
	 * @param bool $alwaysClearColumns 用來控制在$paramsAutoReset = false 的時候是否清除查詢的字段信息.主要用於按批獲取數據不用多次調用columns();
	 *
	 * @return $this
	 */
	public function paramsAutoReset($autoReset = true, $alwaysClearTable = false, $alwaysClearColumns = true)
	{
		$this->paramsAutoReset = $autoReset;
		$this->alwaysClearTable = $alwaysClearTable;
		$this->alwaysClearColumns = $alwaysClearColumns;
		return $this;
	}

	/**
	 * orm參數重置
	 *
	 * @param bool $must 是否強制重置
	 *
	 */
	public function reset($must = false)
	{
		$must && $this->paramsAutoReset();
		if (!$this->paramsAutoReset) {
			$this->alwaysClearColumns && $this->sql['columns'] = '';
			if ($this->alwaysClearTable) {
				$this->table = []; //操作的表
				$this->join = []; //是否內聯
				$this->leftJoin = []; //是否左聯結
				$this->rightJoin = []; //是否右聯
			}
			return;
		}

		$this->sql = [  //sql組裝
			'where' => '',
			'columns' => '',
			'limit' => '',
			'orderBy' => '',
			'groupBy' => '',
			'having' => '',
		];

		$this->table = []; //操作的表
		$this->join = []; //是否內聯
		$this->leftJoin = []; //是否左聯結
		$this->rightJoin = []; //是否右聯
		$this->whereNeedAddAndOrOr = 0;
	}

	/**
	 * 清空綁定的參數
	 *
	 */
	protected function clearBindParams()
	{
		if ($this->paramsAutoReset) {
			$this->bindParams = [];
		}
	}

	/**
	 * where條件組裝 相等
	 *
	 * @param string|array $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名) 當$column為數組時 批量設置
	 * @param string |int $value 當$column為數組時  此時$value為false時條件為or 否則為and
	 *
	 * @return $this
	 */
	public function where($column, $value = '')
	{
		if (is_array($column)) {
			foreach ($column as $key => $val) {
				$this->whereNeedAddAndOrOr > 0 && ($value === false ? $this->_or() : $this->_and());
				$this->conditionFactory($key, $val, '=');
			}
		} else {
			$this->conditionFactory($column, $value, '=');
		}
		return $this;
	}

	/**
	 * 增加or條件操作符
	 *
	 * @param callable $callable 如果傳入函數則函數內執行的條件會被()包圍
	 *
	 * @return $this
	 */
	public function _or(callable $callable = null)
	{
		$history = $this->whereNeedAddAndOrOr;
		$this->whereNeedAddAndOrOr = 2;

		if (is_callable($callable)) {
			$history === 0 && $this->whereNeedAddAndOrOr = 0;
			$this->lBrackets();
			call_user_func($callable, $this);
			$this->rBrackets();
		}

		return $this;
	}

	/**
	 * where條件增加左括號
	 *
	 * @return $this
	 */
	public function lBrackets()
	{
		if ($this->sql['where'] == '') {
			$this->sql['where'] = 'WHERE ';
		} else {
			if ($this->whereNeedAddAndOrOr === 1) {
				$this->sql['where'] .= ' AND ';
			} else if ($this->whereNeedAddAndOrOr === 2) {
				$this->sql['where'] .= ' OR ';
			}
		}
		$this->sql['where'] .= ' (';
		//移除下一次where操作默認加上AND
		$this->whereNeedAddAndOrOr = 0;
		return $this;
	}

	/**
	 * where條件增加右括號
	 *
	 * @return $this
	 */
	public function rBrackets()
	{
		$this->sql['where'] .= ') ';
		return $this;
	}

	/**
	 * 增加 and條件操作符
	 *
	 * @param callable $callable 如果傳入函數則函數內執行的條件會被()包圍
	 *
	 * @return $this
	 */
	public function _and(callable $callable = null)
	{
		$history = $this->whereNeedAddAndOrOr;
		$this->whereNeedAddAndOrOr = 1;

		if (is_callable($callable)) {
			$history === 0 && $this->whereNeedAddAndOrOr = 0;
			$this->lBrackets();
			call_user_func($callable, $this);
			$this->rBrackets();
		}

		return $this;
	}

	/**
	 * where 語句組裝工廠
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param array|int|string $value 值
	 * @param string $operator 操作符
	 *
	 * @return $this
	 */
	public function conditionFactory($column, $value, $operator = '=')
	{
		if ($this->sql['where'] == '') $this->sql['where'] = 'WHERE ';

		if ($this->whereNeedAddAndOrOr === 1) {
			$this->sql['where'] .= ' AND ';
		} else if ($this->whereNeedAddAndOrOr === 2) {
			$this->sql['where'] .= ' OR ';
		}

		//下一次where操作默認加上AND
		$this->whereNeedAddAndOrOr = 1;

		if ($operator == 'IN' || $operator == 'NOT IN') {
			//empty($value) && $value = [0];
			if (empty($value)) {
				$this->sql['where'] .= ' 1 =  -1 ';//強制返回一個空的結果
				return $this;
			}
			$inValue = '(';
			foreach ($value as $val) {
				$inValue .= '%s ,';
				$this->bindParams[] = $val;
			}
			$this->sql['where'] .= "{$column} {$operator} " . rtrim($inValue, ',') . ') ';
		} elseif ($operator == 'BETWEEN' || $operator == 'NOT BETWEEN') {
			$betweenValue = '%s AND %s ';
			$this->bindParams[] = $value[0];
			$this->bindParams[] = $value[1];
			$this->sql['where'] .= "{$column} {$operator} {$betweenValue} ";
		} else if ($operator == 'IS NULL' || $operator == 'IS NOT NULL') {
			$this->sql['where'] .= "{$column} {$operator} ";
		} else if ($operator == 'column') {
			substr(trim($column), 0, 1) != '`' && $column = "`{$column}` ";
			substr(trim($value), 0, 1) != '`' && $value = "`{$value}` ";
			$this->sql['where'] .= "{$column} = {$value} ";
		} else if ($operator == 'raw') {
			$this->sql['where'] .= str_replace('?', '%s', $column) . ' ';
			$value && $this->bindParams = array_merge($this->bindParams, $value);
		} else {
			$this->sql['where'] .= "{$column} {$operator} ";
			if ($operator) {//兼容類式find_in_set()這類的函數查詢
				$this->sql['where'] .= "%s ";
				$this->bindParams[] = $value;
			}

		}
		return $this;
	}

	/**
	 * where條件組裝 兩個列相等
	 *
	 * @param string $column eg：username | `user`.`username`
	 * @param string $column2 eg: nickname | `user`.`nickname`
	 *
	 * @return $this
	 */
	public function whereColumn($column, $column2)
	{
		$this->conditionFactory($column, $column2, 'column');
		return $this;
	}

	/**
	 * where條件原生條件
	 *
	 * @param string $where eg：utime > ctime + ?
	 * @param array $params eg: [10]
	 *
	 * @return $this
	 */
	public function whereRaw($where, $params)
	{
		$this->conditionFactory($where, $params, 'raw');
		return $this;
	}

	/**
	 * 根據條件是否成立執行對應的閉包
	 *
	 * @param bool $condition 條件
	 * @param callable $trueCallback 條件成立執行的閉包
	 * @param callable|null $falseCallback 條件不成立執行的閉包
	 *
	 * @return $this
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

	/**
	 * where條件組裝 不等
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int $value
	 *
	 * @return $this
	 */
	public function whereNot($column, $value)
	{
		$this->conditionFactory($column, $value, '!=');
		return $this;
	}

	/**
	 * where條件組裝 大於
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int $value
	 *
	 * @return $this
	 */
	public function whereGt($column, $value)
	{
		$this->conditionFactory($column, $value, '>');
		return $this;
	}

	/**
	 * where條件組裝 小於
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int $value
	 *
	 * @return $this
	 */
	public function whereLt($column, $value)
	{
		$this->conditionFactory($column, $value, '<');
		return $this;
	}

	/**
	 * where條件組裝 大於等於
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int $value
	 *
	 * @return $this
	 */
	public function whereGte($column, $value)
	{
		$this->conditionFactory($column, $value, '>=');
		return $this;
	}

	/**
	 * where條件組裝 小於等於
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int $value
	 *
	 * @return $this
	 */
	public function whereLte($column, $value)
	{
		$this->conditionFactory($column, $value, '<=');
		return $this;
	}

	/**
	 * where條件組裝 in
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param array $value
	 *
	 * @return $this
	 */
	public function whereIn($column, $value)
	{
		$this->conditionFactory($column, $value, 'IN');
		return $this;
	}

	/**
	 * where條件組裝 not in
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param array $value [1,2,3]
	 *
	 * @return $this
	 */
	public function whereNotIn($column, $value)
	{
		$this->conditionFactory($column, $value, 'NOT IN');
		return $this;
	}

	/**
	 * where條件組裝 REGEXP
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int $value
	 *
	 * @return $this
	 */
	public function whereRegExp($column, $value)
	{
		$this->conditionFactory($column, $value, 'REGEXP');
		return $this;
	}

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
	public function whereLike($column, $leftBlur = false, $value, $rightBlur = false)
	{
		$this->conditionFactory(
			$column,
			($leftBlur ? '%' : '') . $this->filterLike($value) . ($rightBlur ? '%' : ''),
			'LIKE'
		);
		return $this;
	}

	/**
	 * where 用戶輸入過濾
	 *
	 * @param string $val
	 *
	 * @return string
	 */
	protected function filterLike($val)
	{
		return str_replace(['_', '%'], ['\_', '\%'], $val);
	}

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
	public function whereNotLike($column, $leftBlur = false, $value, $rightBlur = false)
	{
		$this->conditionFactory(
			$column,
			($leftBlur ? '%' : '') . $this->filterLike($value) . ($rightBlur ? '%' : ''),
			'NOT LIKE'
		);
		return $this;
	}

	/**
	 * where條件組裝 BETWEEN
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int | array $value
	 * @param string |int | null $value2
	 *
	 * @return $this
	 */
	public function whereBetween($column, $value, $value2 = null)
	{
		if (is_null($value2)) {
			if (!is_array($value)) {
				throw new \InvalidArgumentException(Lang::get('_DB_PARAM_ERROR_WHERE_BETWEEN_'));
			}
			$val = $value;
		} else {
			$val = [$value, $value2];
		}
		$this->conditionFactory($column, $val, 'BETWEEN');
		return $this;
	}

	/**
	 * where條件組裝 NOT BETWEEN
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param string |int | array $value
	 * @param string |int | null $value2
	 *
	 * @return $this
	 */
	public function whereNotBetween($column, $value, $value2 = null)
	{
		if (is_null($value2)) {
			if (!is_array($value)) {
				throw new \InvalidArgumentException(Lang::get('_DB_PARAM_ERROR_WHERE_BETWEEN_'));
			}
			$val = $value;
		} else {
			$val = [$value, $value2];
		}
		$this->conditionFactory($column, $val, 'NOT BETWEEN');
		return $this;
	}

	/**
	 * where條件組裝 IS NULL
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 *
	 * @return $this
	 */
	public function whereNull($column)
	{
		$this->conditionFactory($column, '', 'IS NULL');
		return $this;
	}

	/**
	 * where條件組裝 IS NOT NULL
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 *
	 * @return $this
	 */
	public function whereNotNull($column)
	{
		$this->conditionFactory($column, '', 'IS NOT NULL');
		return $this;
	}

	/**
	 * LIMIT
	 *
	 * @param int $offset 偏移量
	 * @param int $limit 返回的條數
	 *
	 * @return $this
	 */
	public function limit($offset = 0, $limit = 10)
	{
		$offset = intval($offset);
		$limit = intval($limit);
		$offset < 0 && $offset = 0;
		$limit < 1 && $limit = 100;
		$this->sql['limit'] = "LIMIT {$offset}, {$limit}";
		return $this;
	}

	/**
	 * 排序
	 *
	 * @param string $column 要排序的字段
	 * @param string $order 方向,默認為正序
	 *
	 * @return $this
	 */
	public function orderBy($column, $order = 'ASC')
	{
		if ($this->sql['orderBy'] == '') {
			$this->sql['orderBy'] = "ORDER BY {$column} {$order} ";
		} else {
			$this->sql['orderBy'] .= ", {$column} {$order} ";
		}
		return $this;
	}

	/**
	 * 分組
	 *
	 * @param string $column 要設置分組的字段名
	 *
	 * @return $this
	 */
	public function groupBy($column)
	{
		if ($this->sql['groupBy'] == '') {
			$this->sql['groupBy'] = "GROUP BY {$column} ";
		} else {
			$this->sql['groupBy'] .= ",{$column} ";
		}
		return $this;
	}

	/**
	 * having語句
	 *
	 * @param string $column 字段名
	 * @param string $operator 操作符
	 * @param string|array $value 值
	 * @param string $logic 邏輯AND OR
	 *
	 * @return $this
	 */
	public function having($column, $operator = '=', $value, $logic = 'AND')
	{
		$having = $this->sql['having'] == '' ? 'HAVING' : " {$logic} ";
		$this->sql['having'] .= "{$having} {$column} {$operator} ";
		if ($value) {
			if (is_array($value)) {//手動傳%s
				$this->bindParams = array_merge($this->bindParams, $value);
			} else {
				$this->sql['having'] .= ' %s ';
				$this->bindParams[] = $value;
			}
		}
		return $this;
	}

	/**
	 * join內聯結
	 *
	 * @param string|array $table 表名 要取別名時使用 [不帶前綴表名 => 別名]
	 * @param string $on 聯結的條件 如：'c.cid = a.cid'
	 * @param mixed $tablePrefix 表前綴
	 *
	 * @return $this
	 */
	public function join($table, $on, $tablePrefix = null)
	{
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;

		$this->table($table, $tablePrefix);
		$hasAlias = is_array($table) ? true : false;

		$tableName = $tablePrefix . ($hasAlias ? key($table) : $table);
		$this->join[count($this->table) - 1 . '_' . $tableName] = is_array($on) ? $this->parseOn($table, $on) : addslashes($on);
		return $this;
	}

	/**
	 * 定義操作的表
	 *
	 * @param string|array $table 表名 要取別名時使用 [不帶前綴表名 => 別名]
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return $this
	 */
	public function table($table = '', $tablePrefix = null)
	{
		$hasAlias = is_array($table) ? true : false;
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$tableName = $tablePrefix . ($hasAlias ? key($table) : $table);

		$this->table[count($this->table) . '_' . $tableName] = $hasAlias ? current($table) : null;
		return $this;
	}

	/**
	 * 解析聯結的on參數
	 *
	 * @param string $table 要聯結的表名
	 * @param array $on ['on條件1', 'on條件2' => true] on條件為數字索引時多條件默認為and為非數字引時 條件=>true為and 條件=>false為or
	 *
	 * @return string
	 */
	protected function parseOn(&$table, $on)
	{
		if (empty($on)) {
			throw new \InvalidArgumentException(Lang::get('_DB_PARAM_ERROR_PARSE_ON_', $table));
		}
		$result = '';
		foreach ($on as $key => $val) {
			if (is_numeric($key)) {
				$result == '' || $result .= ' AND ';
				$result .= $val;
			} else {
				$result == '' || $result .= ($val === true ? ' AND ' : ' OR ');
				$result .= $key;
			}
		}
		return addslashes($result); //on條件是程序員自己寫死的表字段名不存在注入以防萬一還是過濾一下
	}

	/**
	 * leftJoin左聯結
	 *
	 * @param string|array $table 表名 要取別名時使用 [不帶前綴表名 => 別名]
	 * @param string $on 聯結的條件 如：'c.cid = a.cid'
	 * @param mixed $tablePrefix 表前綴
	 *
	 * @return $this
	 */
	public function leftJoin($table, $on, $tablePrefix = null)
	{
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;

		$this->table($table, $tablePrefix);
		$hasAlias = is_array($table) ? true : false;

		$tableName = $tablePrefix . ($hasAlias ? key($table) : $table);
		$this->leftJoin[count($this->table) - 1 . '_' . $tableName] = is_array($on) ? $this->parseOn($table, $on) : addslashes($on);
		return $this;
	}

	/**
	 * rightJoin右聯結
	 *
	 * @param string|array $table 表名 要取別名時使用 [不帶前綴表名 => 別名]
	 * @param string $on 聯結的條件 如：'c.cid = a.cid'
	 * @param mixed $tablePrefix 表前綴
	 *
	 * @return $this
	 */
	public function rightJoin($table, $on, $tablePrefix = null)
	{
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;

		$this->table($table, $tablePrefix);
		$hasAlias = is_array($table) ? true : false;

		$tableName = $tablePrefix . ($hasAlias ? key($table) : $table);
		$this->rightJoin[count($this->table) - 1 . '_' . $tableName] = is_array($on) ? $this->parseOn($table, $on) : addslashes($on);
		return $this;
	}

	/**
	 * union聯結
	 *
	 * @param string|array $sql 要union的sql
	 * @param bool $all 是否為union all
	 *
	 * @return $this
	 */
	public function union($sql, $all = false)
	{
		if (is_array($sql)) {
			foreach ($sql as $s) {
				$this->union .= $all ? ' UNION ALL ' : ' UNION ';
				$this->union .= $this->filterUnionSql($s);
			}
		} else {
			$this->union .= $all ? ' UNION ALL ' : ' UNION ';
			$this->union .= $this->filterUnionSql($sql) . ' ';
		}
		return $this;
	}

	protected function filterUnionSql($sql)
	{
		return str_ireplace([
			'insert', "update", "delete", "\/\*", "\.\.\/", "\.\/", "union", "into", "load_file", "outfile"
		],
			["", "", "", "", "", "", "", "", "", ""],
			$sql);
	}

	/**
	 * 重置所有orm參數及綁定
	 *
	 * @return $this
	 */
	public function resetAndClear()
	{
		$this->reset();
		$this->clearBindParams();
		return $this;
	}

	/**
	 * 根據表名獲取cache版本號
	 *
	 * @param string $table
	 *
	 * @return mixed
	 */
	public function getCacheVer($table)
	{
		if (!$this->openCache) {
			return '';
		}

		$version = Model::getInstance()->cache()->get($this->conf['mark'] . '_db_cache_version_' . $table);
		if (!$version) {
			$version = microtime(true);
			Model::getInstance()->cache()->set($this->conf['mark'] . '_db_cache_version_' . $table, $version, $this->conf['cache_expire']);
		}
		return $version;
	}

	/**
	 * 標記本次查詢不使用緩存
	 *
	 * @return $this
	 */
	public function noCache()
	{
		$this->currentQueryUseCache = false;
		return $this;
	}

	/**
	 * 設置cache版本號
	 *
	 * @param string $table
	 */
	public function setCacheVer($table)
	{
		if (!$this->openCache) {
			return;
		}

		$isOpenEmergencyMode = Config::get('emergency_mode_not_real_time_refresh_mysql_query_cache');
		if ($isOpenEmergencyMode !== false && $isOpenEmergencyMode > 0) {//開啟了緊急模式
			$expireTime = Model::getInstance()->cache()->get("emergency_mode_not_real_time_refresh_mysql_query_cache_{$table}");
			if ($expireTime && $isOpenEmergencyMode + $expireTime > time()) {
				return;
			}
			Model::getInstance()->cache()->set("emergency_mode_not_real_time_refresh_mysql_query_cache_{$table}", time(), 3600);
		}

		Model::getInstance()->cache()->set($this->conf['mark'] . '_db_cache_version_' . $table, microtime(true), $this->conf['cache_expire']);
	}

	/**
	 * 執行事務操作
	 *
	 * @param callable $query
	 *
	 * @return bool
	 * @throws
	 *
	 */
	public function transaction(callable $query)
	{
		try {
			$this->startTransAction();
			$query();
			return $this->commit();
		} catch (\Exception $e) {
			$this->rollBack();
			throw $e;
		}
	}

	/**
	 * 析構函數
	 *
	 */
	public function __destruct()
	{
		$this->close();
	}

	/**
	 * SQL語句條件組裝
	 *
	 * @param array $arr 要組裝的數組
	 *
	 * @return string
	 */
	protected function arrToCondition($arr)
	{
		$s = $p = '';
		$params = [];
		foreach ($arr as $k => $v) {
			if (is_array($v)) { //自增或自減
				switch (key($v)) {
					case '+':
					case 'inc':
						$p = "`{$k}`= `{$k}`+" . abs(intval(current($v)));
						break;
					case '-':
					case 'dec':
						$p = "`{$k}`= `{$k}`-" . abs(intval(current($v)));
						break;
					case 'func':
						$func = strtoupper(key(current($v)));
						$funcParams = current(current($v));
						foreach ($funcParams as $key => $val) {
							if (substr($val, 0, 1) !== '`') {
								$funcParams[$key] = '%s';
								$params[] = $val;
							}
						}
						$p = "`{$k}`= {$func}(" . implode($funcParams, ',') . ')';
						break;
					case 'column':
						$p = "`{$k}`= `" . current($v) . "`";
						break;
					case 'raw':
						$p = "`{$k}`= " . addslashes(current($v));//flags = (flags | 2) ^ 3
						break;
					default ://計算類型
						$conKey = key($v);
						if (!in_array(key(current($v)), ['+', '-', '*', '/', '%', '^', '&', '|', '<<', '>>', '~'])) {
							throw new \InvalidArgumentException(Lang::get('_PARSE_UPDATE_SQL_PARAMS_ERROR_'));
						}
						$p = "`{$k}`= `{$conKey}`" . key(current($v)) . abs(intval(current(current($v))));
						break;
				}
			} else {
				$p = "`{$k}`= %s";
				$params[] = $v;
			}

			$s .= (empty($s) ? '' : ',') . $p;
		}
		$this->bindParams = array_merge($params, $this->bindParams);
		return $s;
	}

	/**
	 * SQL語句條件組裝
	 *
	 * @param string $key eg: 'forum-fid-1-uid-2'
	 * @param bool $and 多個條件之間是否為and  true為and false為or
	 * @param bool $noCondition 是否為無條件操作  set/delete/update操作的時候 condition為空是正常的不報異常
	 * @param bool $noTable 是否可以沒有數據表 當delete/update等操作的時候已經執行了table() table為空是正常的
	 *
	 * @return array eg: ['forum', "`fid` = '1' AND `uid` = '2'"]
	 */
	protected function parseKey($key, $and = true, $noCondition = false, $noTable = false)
	{
		$condition = '';
		$arr = explode('-', $key);
		$len = count($arr);
		for ($i = 1; $i < $len; $i += 2) {
			isset($arr[$i + 1]) && $condition .= ($condition ? ($and ? ' AND ' : ' OR ') : '') . "`{$arr[$i]}` = %s";
			$this->bindParams[] = $arr[$i + 1];
		}
		$table = strtolower($arr[0]);
		if (empty($table) && !$noTable) {
			throw new \InvalidArgumentException(Lang::get('_DB_PARAM_ERROR_PARSE_KEY_', $key, 'table'));
		}
		if (empty($condition) && !$noCondition) {
			throw new \InvalidArgumentException(Lang::get('_DB_PARAM_ERROR_PARSE_KEY_', $key, 'condition'));
		}
		empty($condition) || $condition = "($condition)";
		return [$table, $condition];
	}
}
