<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-3-1 下午18:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 MongoDB數據庫MongoDB驅動類 http://php.net/manual/zh/set.mongodb.php
 * *********************************************************** */

namespace Cml\Db\MongoDB;

use Cml\Cml;
use Cml\Config;
use Cml\Db\Base;
use Cml\Debug;
use Cml\Lang;
use MongoDB\BSON\Regex;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Driver\Exception\Exception as MongoDBDriverException;
use MongoDB\Driver\ReadPreference;

/**
 * Orm MongoDB數據庫MongoDB實現類
 *
 * @see http://php.net/manual/zh/set.mongodb.php
 *
 * @package Cml\Db\MySql
 */
class MongoDB extends Base
{
	/**
	 * @var array sql組裝
	 */
	protected $sql = [
		'where' => [],
		'columns' => [],
		'limit' => [0, 5000],
		'orderBy' => [],
		'groupBy' => '',
		'having' => '',
	];
	/**
	 * 最新插入的數據的id
	 *
	 * @var null
	 */
	private $lastInsertId = null;
	/**
	 * 標識下個where操作為and 還是 or 默認是and操作
	 *
	 * @var bool
	 */
	private $opIsAnd = true;

	/**
	 * or操作中一組條件是否有多個條件
	 *
	 * @var bool
	 */
	private $bracketsIsOpen = false;

	/**
	 * 數據庫連接串
	 *
	 * @param $conf
	 */
	public function __construct($conf)
	{
		$this->conf = $conf;
		$this->tablePrefix = isset($this->conf['master']['tableprefix']) ? $this->conf['master']['tableprefix'] : '';
	}

	/**
	 * 獲取當前數據庫中所有表的信息
	 *
	 * @return array
	 */
	public function getAllTableStatus()
	{
		$return = [];
		$collections = $this->getTables();
		foreach ($collections as $collection) {
			$res = $this->runMongoCommand(['collStats' => $collection]);
			$return[substr($res[0]['ns'], strrpos($res[0]['ns'], '.') + 1)] = $res[0];
		}
		return $return;
	}

	/**
	 * 獲取當前db所有表名
	 *
	 * @return array
	 */
	public function getTables()
	{
		$tables = [];
		if ($this->serverSupportFeature(3)) {
			$result = $this->runMongoCommand(['listCollections' => 1]);
			foreach ($result as $val) {
				$tables[] = $val['name'];
			}
		} else {
			$result = $this->runMongoQuery('system.namespaces');
			foreach ($result as $val) {
				if (strpos($val['name'], '$') === false) {
					$tables[] = substr($val['name'], strpos($val['name'], '.') + 1);
				}
			}
		}

		return $tables;
	}

	/**
	 * 判斷當前mongod服務是否支持某個版本的特性
	 *
	 * @param int $version 要判斷的版本
	 *
	 * @return bool
	 */
	public function serverSupportFeature($version = 3)
	{
		$info = $this->getSlave()->selectServer(new ReadPreference(ReadPreference::RP_PRIMARY))->getInfo();
		$maxWireVersion = isset($info['maxWireVersion']) ? (integer)$info['maxWireVersion'] : 0;
		$minWireVersion = isset($info['minWireVersion']) ? (integer)$info['minWireVersion'] : 0;

		return ($minWireVersion <= $version && $maxWireVersion >= $version);
	}

	/**
	 * 返回從庫連接
	 *
	 * @return Manager
	 */
	private function getSlave()
	{
		return $this->rlink;
	}

	/**
	 * 執行命令
	 *
	 * @param array $cmd 要執行的Command
	 * @param bool $runOnMaster 使用主庫還是從庫執行 默認使用主庫執行
	 * @param bool $returnCursor 返回數據還是cursor 默認返回結果數據
	 *
	 * @return array|Cursor
	 */
	public function runMongoCommand($cmd = [], $runOnMaster = true, $returnCursor = false)
	{
		Cml::$debug && $this->debugLogSql('Command', '', $cmd);

		$this->reset();
		$db = $runOnMaster ?
			$this->getMaster()->selectServer(new ReadPreference(ReadPreference::RP_PRIMARY_PREFERRED))
			: $this->getSlave()->selectServer(new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED));
		$cursor = $db->executeCommand($this->getDbName(), new Command($cmd));

		if ($returnCursor) {
			return $cursor;
		} else {
			$cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
			$result = [];
			foreach ($cursor as $collection) {
				$result[] = $collection;
			}
			return $result;
		}
	}

	/**
	 * Debug模式記錄查詢語句顯示到控制台
	 *
	 * @param string $type 查詢的類型
	 * @param string $tableName 查詢的Collection
	 * @param array $condition 條件
	 * @param array $options 額外參數
	 */
	private function debugLogSql($type = 'Query', $tableName, $condition = [], $options = [])
	{
		if (Cml::$debug) {
			Debug::addSqlInfo(sprintf(
				"〔MongoDB {$type}〕 Collection: %s, Condition: %s, Other: %s",
				$this->getDbName() . ".{$tableName}",
				json_encode($condition, JSON_UNESCAPED_UNICODE),
				json_encode($options, JSON_UNESCAPED_UNICODE)
			));
		}
	}

	/**
	 * 獲取數據庫名
	 *
	 * @return string
	 */
	private function getDbName()
	{
		return $this->conf['master']['dbname'];
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
			$this->alwaysClearColumns && $this->sql['columns'] = [];
			if ($this->alwaysClearTable) {
				$this->table = []; //操作的表
				$this->join = []; //是否內聯
				$this->leftJoin = []; //是否左聯結
				$this->rightJoin = []; //是否右聯
			}
			return;
		}

		$this->sql = [
			'where' => [],
			'columns' => [],
			'limit' => [],
			'orderBy' => [],
			'groupBy' => '',
			'having' => '',
		];

		$this->table = []; //操作的表
		$this->join = []; //是否內聯
		$this->leftJoin = []; //是否左聯結
		$this->rightJoin = []; //是否右聯
		$this->whereNeedAddAndOrOr = 0;
		$this->opIsAnd = true;
	}

	/**
	 * 返回主庫連接
	 *
	 * @return Manager
	 */
	private function getMaster()
	{
		return $this->wlink;
	}

	/**
	 * 執行mongoQuery命令
	 *
	 * @param string $tableName 執行的mongoCollection名稱
	 * @param array $condition 查詢條件
	 * @param array $queryOptions 查詢的參數
	 * @param bool|string $useMaster 是否使用主庫
	 *
	 * @return array
	 */
	public function runMongoQuery($tableName, $condition = [], $queryOptions = [], $useMaster = false)
	{
		Cml::$debug && $this->debugLogSql('Query', $tableName, $condition, $queryOptions);

		$this->reset();
		$db = $useMaster ?
			$this->getMaster()->selectServer(new ReadPreference(ReadPreference::RP_PRIMARY_PREFERRED))
			: $this->getSlave()->selectServer(new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED));
		$cursor = $db->executeQuery($this->getDbName() . ".{$tableName}", new Query($condition, $queryOptions));
		$cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
		$result = [];
		foreach ($cursor as $collection) {
			$result[] = $collection;
		}
		return $result;
	}

	/**
	 * 獲取表字段-因為mongodb中collection對字段是沒有做強制一制的。這邊默認獲取第一條數據的所有字段返回
	 *
	 * @param string $table 表名
	 * @param mixed $tablePrefix 表前綴，不傳則獲取配置中配置的前綴
	 * @param int $filter 在MongoDB中此選項無效
	 *
	 * @return mixed
	 */
	public function getDbFields($table, $tablePrefix = null, $filter = 0)
	{
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$one = $this->runMongoQuery($tablePrefix . $table, [], ['limit' => 1]);
		return empty($one) ? [] : array_keys($one[0]);
	}

	/**
	 * 根據key取出數據
	 *
	 * @param string $key get('user-uid-123');
	 * @param bool $and 多個條件之間是否為and  true為and false為or
	 * @param bool|string $useMaster 是否使用主庫,此選項為字符串時為表前綴$tablePrefix
	 * @param null|string $tablePrefix 表前綴
	 *
	 * @return array
	 */
	public function get($key, $and = true, $useMaster = false, $tablePrefix = null)
	{
		if (is_string($useMaster) && is_null($tablePrefix)) {
			$tablePrefix = $useMaster;
			$useMaster = false;
		}

		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;

		list($tableName, $condition) = $this->parseKey($key, $and);

		$filter = [];
		isset($this->sql['limit'][0]) && $filter['skip'] = $this->sql['limit'][0];
		isset($this->sql['limit'][1]) && $filter['limit'] = $this->sql['limit'][1];

		return $this->runMongoQuery($tablePrefix . $tableName, $condition, $filter, $useMaster);
	}

	/**
	 * 查詢語句條件組裝
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
		$keys = explode('-', $key);
		$table = strtolower(array_shift($keys));
		$len = count($keys);
		$condition = [];
		for ($i = 0; $i < $len; $i += 2) {
			$val = is_numeric($keys[$i + 1]) ? intval($keys[$i + 1]) : $keys[$i + 1];
			$and ? $condition[$keys[$i]] = $val : $condition['$or'][][$keys[$i]] = $val;
		}

		if (empty($table) && !$noTable) {
			throw new \InvalidArgumentException(Lang::get('_DB_PARAM_ERROR_PARSE_KEY_', $key, 'table'));
		}
		if (empty($condition) && !$noCondition) {
			throw new \InvalidArgumentException(Lang::get('_DB_PARAM_ERROR_PARSE_KEY_', $key, 'condition'));
		}

		return [$table, $condition];
	}

	/**
	 * 新增多條數據
	 *
	 * @param string $table 表名
	 * @param array $field mongodb中本參數無效
	 * @param array $data eg: 多條數據的值 [['標題1', '內容1', 1, '2017'], ['標題2', '內容2', 1, '2017']]
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 * @param bool $openTransAction 是否開啟事務 mongodb中本參數無效
	 * @return bool|array
	 * @throws \InvalidArgumentException
	 *
	 */
	public function setMulti($table, $field, $data, $tablePrefix = null, $openTransAction = true)
	{
		$idArray = [];
		foreach ($data as $row) {
			$idArray[] = $this->set($table, $row, $tablePrefix);
		}
		return $idArray;
	}

	/**
	 * 根據key 新增 一條數據
	 *
	 * @param string $table 表名
	 * @param array $data eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return bool|int
	 */
	public function set($table, $data, $tablePrefix = null)
	{
		if (is_array($data)) {
			is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;

			$bulk = new BulkWrite();
			$insertId = $bulk->insert($data);
			$result = $this->runMongoBulkWrite($tablePrefix . $table, $bulk);

			Cml::$debug && $this->debugLogSql('BulkWrite INSERT', $tablePrefix . $table, [], $data);

			if ($result->getInsertedCount() > 0) {
				$this->lastInsertId = sprintf('%s', $insertId);
			}
			return $this->insertId();
		} else {
			return false;
		}
	}

	/**
	 * 執行mongoBulkWrite命令
	 *
	 * @param string $tableName 執行的mongoCollection名稱
	 * @param BulkWrite $bulk The MongoDB\Driver\BulkWrite to execute.
	 *
	 * @return \MongoDB\Driver\WriteResult
	 */
	public function runMongoBulkWrite($tableName, BulkWrite $bulk)
	{
		$this->reset();
		$return = false;

		try {
			$return = $this->getMaster()->selectServer(new ReadPreference(ReadPreference::RP_PRIMARY_PREFERRED))
				->executeBulkWrite($this->getDbName() . ".{$tableName}", $bulk);
		} catch (BulkWriteException $e) {
			$result = $e->getWriteResult();

			// Check if the write concern could not be fulfilled
			if ($writeConcernError = $result->getWriteConcernError()) {
				throw new \RuntimeException(sprintf("%s (%d): %s\n",
					$writeConcernError->getMessage(),
					$writeConcernError->getCode(),
					var_export($writeConcernError->getInfo(), true)
				), 0, $e);
			}

			$errors = [];
			// Check if any write operations did not complete at all
			foreach ($result->getWriteErrors() as $writeError) {
				$errors[] = sprintf("Operation#%d: %s (%d)\n",
					$writeError->getIndex(),
					$writeError->getMessage(),
					$writeError->getCode()
				);
			}
			throw new \RuntimeException(var_export($errors, true), 0, $e);
		} catch (MongoDBDriverException $e) {
			throw new \UnexpectedValueException(sprintf("Other error: %s\n", $e->getMessage()), 0, $e);
		}

		return $return;
	}

	/**
	 * 獲取上一INSERT的主鍵值
	 *
	 * @param mixed $link MongoDdb中此選項無效
	 *
	 * @return int
	 */
	public function insertId($link = null)
	{
		return $this->lastInsertId;
	}

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
	public function replaceInto($table, array $data, $tablePrefix = null)
	{
		return $this->upSet($table, $data, $data, $tablePrefix);
	}

	/**
	 * 插入或更新一條記錄，當UNIQUE index or PRIMARY KEY存在的時候更新，不存在的時候插入
	 * 若AUTO_INCREMENT存在則返回 AUTO_INCREMENT 的值.
	 *
	 * @param string $table 表名
	 * @param array $data 插入的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
	 * @param array $up mongodb中此項無效
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return int
	 */
	public function upSet($table, array $data = [], array $up = [], $tablePrefix = null)
	{
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$tableName = $tablePrefix . $table;
		if (empty($tableName)) {
			throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', 'upSet'));
		}
		$condition = $this->sql['where'];
		if (empty($condition)) {
			throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_CONDITION_', 'upSet'));
		}

		$bulk = new BulkWrite();
		$bulk->update($condition, ['$set' => array_merge($data, $up)], ['multi' => true, 'upsert' => true]);
		$result = $this->runMongoBulkWrite($tableName, $bulk);

		Cml::$debug && $this->debugLogSql('BulkWrite upSet', $tableName, $condition, $data);

		return $result->getModifiedCount();
	}

	/**
	 * 根據key更新一條數據
	 *
	 * @param string|array $key eg 'user-uid-$uid' 如果條件是通用whereXX()、表名是通過table()設定。這邊可以直接傳$data的數組
	 * @param array | null $data eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
	 * @param bool $and 多個條件之間是否為and  true為and false為or
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return boolean
	 */
	public function update($key, $data = null, $and = true, $tablePrefix = null)
	{
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$tableName = $condition = '';

		if (is_array($data)) {
			list($tableName, $condition) = $this->parseKey($key, $and, true, true);
		} else {
			$data = $key;
		}

		$tableName = empty($tableName) ? $this->getRealTableName(key($this->table)) : $tablePrefix . $tableName;
		if (empty($tableName)) {
			throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', 'update'));
		}
		$condition += $this->sql['where'];
		if (empty($condition)) {
			throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_CONDITION_', 'update'));
		}

		$bulk = new BulkWrite();
		$bulk->update($condition, ['$set' => $data], ['multi' => true]);
		$result = $this->runMongoBulkWrite($tableName, $bulk);

		Cml::$debug && $this->debugLogSql('BulkWrite UPDATE', $tableName, $condition, $data);

		return $result->getModifiedCount();
	}

	/**
	 * 獲取處理後的表名
	 *
	 * @param string $table 表名
	 *
	 * @return string
	 */
	private function getRealTableName($table)
	{
		return substr($table, strpos($table, '_') + 1);
	}

	/**
	 * 根據key值刪除數據
	 *
	 * @param string $key eg: 'user-uid-$uid'
	 * @param bool $and 多個條件之間是否為and  true為and false為or
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return boolean
	 */
	public function delete($key = '', $and = true, $tablePrefix = null)
	{
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$tableName = $condition = '';

		empty($key) || list($tableName, $condition) = $this->parseKey($key, $and, true, true);

		$tableName = empty($tableName) ? $this->getRealTableName(key($this->table)) : $tablePrefix . $tableName;
		if (empty($tableName)) {
			throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', 'delete'));
		}
		$condition += $this->sql['where'];
		if (empty($condition)) {
			throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_CONDITION_', 'delete'));
		}

		$bulk = new BulkWrite();
		$bulk->delete($condition);
		$result = $this->runMongoBulkWrite($tableName, $bulk);

		Cml::$debug && $this->debugLogSql('BulkWrite DELETE', $tableName, $condition);

		return $result->getDeletedCount();
	}

	/**
	 * 清空集合 這個操作太危險所以直接屏蔽了
	 *
	 * @param string $tableName 要清空的表名
	 *
	 * @return bool | $this
	 */
	public function truncate($tableName)
	{
		$this->logNotSupportMethod(__METHOD__);
		return $this;
	}

	/**
	 * 記錄不支持的方法
	 *
	 * @param int $method
	 */
	private function logNotSupportMethod($method)
	{
		Cml::$debug && Debug::addTipInfo('MongoDb NotSupport [' . $method . '] Method', Debug::TIP_INFO_TYPE_INFO, 'red');
	}

	/**
	 * 獲取表主鍵 mongo直接返回 '_id'
	 *
	 * @param string $table 要獲取主鍵的表名
	 * @param string $tablePrefix 表前綴
	 *
	 * @return string || false
	 */
	public function getPk($table, $tablePrefix = null)
	{
		return '_id';
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
			($leftBlur ? '' : '^') . preg_quote($this->filterLike($value)) . ($rightBlur ? '' : '$'),
			'LIKE'
		);
		return $this;
	}

	/**
	 * where 語句組裝工廠
	 *
	 * @param string $column 如 id  user.id (這邊的user為表別名如表pre_user as user 這邊用user而非帶前綴的原表名)
	 * @param array|int|string $value 值
	 * @param string $operator 操作符
	 * @return $this
	 * @throws \Exception
	 *
	 */
	public function conditionFactory($column, $value, $operator = '=')
	{
		$currentOrIndex = isset($this->sql['where']['$or']) ? count($this->sql['where']['$or']) - 1 : 0;

		if ($this->opIsAnd) {
			if (isset($this->sql['where'][$column][$operator])) {
				throw new \InvalidArgumentException('Mongodb Where Op key Is Exists[' . $column . $operator . ']');
			}
		} else if ($this->bracketsIsOpen) {
			if (isset($this->sql['where']['$or'][$currentOrIndex][$column][$operator])) {
				throw new \InvalidArgumentException('Mongodb Where Op key Is Exists[' . $column . $operator . ']');
			}
		}

		switch ($operator) {
			case 'IN':
				// no break
			case 'NOT IN':
				empty($value) && $value = [0];
				//這邊可直接跳過不組裝sql，但是為了給用戶提示無條件 便於調試還是加上where field in(0)
				if ($this->opIsAnd) {
					$this->sql['where'][$column][$operator == 'IN' ? '$in' : '$nin'] = $value;
				} else if ($this->bracketsIsOpen) {
					$this->sql['where']['$or'][$currentOrIndex][$column][$operator == 'IN' ? '$in' : '$nin'] = $value;
				} else {
					$this->sql['where']['$or'][][$column] = $operator == 'IN' ? ['$in' => $value] : ['$nin' => $value];
				}
				break;
			case 'BETWEEN':
				if ($this->opIsAnd) {
					$this->sql['where'][$column]['$gt'] = $value[0];
					$this->sql['where'][$column]['$lt'] = $value[1];
				} else if ($this->bracketsIsOpen) {
					$this->sql['where']['$or'][$currentOrIndex][$column]['$gt'] = $value[0];
					$this->sql['where']['$or'][$currentOrIndex][$column]['$lt'] = $value[1];
				} else {
					$this->sql['where']['$or'][][$column] = ['$gt' => $value[0], '$lt' => $value[1]];
				}
				break;
			case 'NOT BETWEEN':
				if ($this->opIsAnd) {
					$this->sql['where'][$column]['$lt'] = $value[0];
					$this->sql['where'][$column]['$gt'] = $value[1];
				} else if ($this->bracketsIsOpen) {
					$this->sql['where']['$or'][$currentOrIndex][$column]['$lt'] = $value[0];
					$this->sql['where']['$or'][$currentOrIndex][$column]['$gt'] = $value[1];
				} else {
					$this->sql['where']['$or'][][$column] = ['$lt' => $value[0], '$gt' => $value[1]];
				}
				break;
			case 'IS NULL':
				if ($this->opIsAnd) {
					$this->sql['where'][$column]['$in'] = [null];
					$this->sql['where'][$column]['$exists'] = true;
				} else if ($this->bracketsIsOpen) {
					$this->sql['where']['$or'][$currentOrIndex][$column]['$in'] = [null];
					$this->sql['where']['$or'][$currentOrIndex][$column]['$exists'] = true;
				} else {
					$this->sql['where']['$or'][][$column] = ['$in' => [null], '$exists' => true];
				}
				break;
			case 'IS NOT NULL':
				if ($this->opIsAnd) {
					$this->sql['where'][$column]['$ne'] = null;
					$this->sql['where'][$column]['$exists'] = true;
				} else if ($this->bracketsIsOpen) {
					$this->sql['where']['$or'][$currentOrIndex][$column]['$ne'] = null;
					$this->sql['where']['$or'][$currentOrIndex][$column]['$exists'] = true;
				} else {
					$this->sql['where']['$or'][][$column] = ['$ne' => null, '$exists' => true];
				}
				break;
			case '>':
				//no break;
			case '<':
				if ($this->opIsAnd) {
					$this->sql['where'][$column][$operator == '>' ? '$gt' : '$lt'] = $value;
				} else if ($this->bracketsIsOpen) {
					$this->sql['where']['$or'][$currentOrIndex][$column][$operator == '>' ? '$gt' : '$lt'] = $value;
				} else {
					$this->sql['where']['$or'][][$column] = $operator == '>' ? ['$gt' => $value] : ['$lt' => $value];
				}
				break;
			case '>=':
				//no break;
			case '<=':
				if ($this->opIsAnd) {
					$this->sql['where'][$column][$operator == '>=' ? '$gte' : '$lte'] = $value;
				} else if ($this->bracketsIsOpen) {
					$this->sql['where']['$or'][$currentOrIndex][$column][$operator == '>=' ? '$gte' : '$lte'] = $value;
				} else {
					$this->sql['where']['$or'][][$column] = $operator == '>=' ? ['$gte' => $value] : ['$lte' => $value];
				}
				break;
			case 'NOT LIKE':
				if ($this->opIsAnd) {
					$this->sql['where'][$column]['$not'] = new Regex($value, 'i');
				} else if ($this->bracketsIsOpen) {
					$this->sql['where']['$or'][$currentOrIndex][$column]['$not'] = new Regex($value, 'i');
				} else {
					$this->sql['where']['$or'][][$column] = ['$not' => new Regex($value, 'i')];
				}
				break;
			case 'LIKE':
				//no break;
			case 'REGEXP':
				if ($this->opIsAnd) {
					$this->sql['where'][$column]['$regex'] = $value;
					$this->sql['where'][$column]['$options'] = '$i';
				} else if ($this->bracketsIsOpen) {
					$this->sql['where']['$or'][$currentOrIndex][$column]['$regex'] = $value;
					$this->sql['where']['$or'][$currentOrIndex][$column]['$options'] = '$i';
				} else {
					$this->sql['where']['$or'][][$column] = ['$regex' => $value, '$options' => '$i'];
				}
				break;
			case '!=':
				if ($this->opIsAnd) {
					$this->sql['where'][$column]['$ne'] = $value;
				} else if ($this->bracketsIsOpen) {
					$this->sql['where']['$or'][$currentOrIndex][$column]['$ne'] = $value;
				} else {
					$this->sql['where']['$or'][][$column] = ['$ne' => $value];
				}
				break;
			case '=':
				if ($this->opIsAnd) {
					$this->sql['where'][$column] = $value;
				} else if ($this->bracketsIsOpen) {
					$this->sql['where']['$or'][$currentOrIndex][$column] = $value;
				} else {
					$this->sql['where']['$or'][][$column] = $value;
				}
				break;
			case 'column':
				$this->sql['where']['$where'] = "this.{$column} = this.{$value}";
				break;
			case 'raw':
				$this->sql['where']['$where'] = $column;
				break;
		}
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
	public function whereNotLike($column, $leftBlur = false, $value, $rightBlur = false)
	{
		$this->conditionFactory(
			$column,
			($leftBlur ? '' : '^') . preg_quote($this->filterLike($value)) . ($rightBlur ? '' : '$'),
			'NOT LIKE'
		);
		return $this;
	}

	/**
	 * 選擇列
	 *
	 * @param string|array $columns 默認選取所有 ['id, 'name'] 選取id,name兩列
	 *
	 * @return $this
	 */
	public function columns($columns = '*')
	{
		if (false === is_array($columns) && $columns != '*') {
			$columns = func_get_args();
		}
		foreach ($columns as $column) {
			$this->sql['columns'][$column] = 1;
		}
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
		$this->sql['orderBy'][$column] = strtoupper($order) === 'ASC' ? 1 : -1;
		return $this;
	}

	/**
	 * 分組 MongoDB中的聚合方式跟 sql不一樣。這個操作屏蔽。如果要使用聚合直接使用MongoDB Command
	 *
	 * @param string $column 要設置分組的字段名
	 *
	 * @return $this
	 */
	public function groupBy($column)
	{
		$this->logNotSupportMethod(__METHOD__);
		return $this;
	}

	/**
	 * having語句 MongoDB不支持此命令
	 *
	 * @param string $column 字段名
	 * @param string $operator 操作符
	 * @param string $value 值
	 *
	 * @return $this
	 */
	public function having($column, $operator = '=', $value)
	{
		$this->logNotSupportMethod(__METHOD__);
		return $this;
	}

	/**
	 * join內聯結 MongoDB不支持此命令
	 *
	 * @param string|array $table 表名 要取別名時使用 [不帶前綴表名 => 別名]
	 * @param string $on 聯結的條件 如：'c.cid = a.cid'
	 * @param mixed $tablePrefix 表前綴
	 *
	 * @return $this
	 */
	public function join($table, $on, $tablePrefix = null)
	{
		$this->logNotSupportMethod(__METHOD__);
		return $this;
	}

	/**
	 * leftJoin左聯結 MongoDB不支持此命令
	 *
	 * @param string|array $table 表名 要取別名時使用 [不帶前綴表名 => 別名]
	 * @param string $on 聯結的條件 如：'c.cid = a.cid'
	 * @param mixed $tablePrefix 表前綴
	 *
	 * @return $this
	 */
	public function leftJoin($table, $on, $tablePrefix = null)
	{
		$this->logNotSupportMethod(__METHOD__);
		return $this;
	}

	/**
	 * rightJoin右聯結 MongoDB不支持此命令
	 *
	 * @param string|array $table 表名 要取別名時使用 [不帶前綴表名 => 別名]
	 * @param string $on 聯結的條件 如：'c.cid = a.cid'
	 * @param mixed $tablePrefix 表前綴
	 *
	 * @return $this
	 */
	public function rightJoin($table, $on, $tablePrefix = null)
	{
		$this->logNotSupportMethod(__METHOD__);
		return $this;
	}

	/**
	 * union聯結 MongoDB不支持此命令
	 *
	 * @param string|array $sql 要union的sql
	 * @param bool $all 是否為union all
	 *
	 * @return $this
	 */
	public function union($sql, $all = false)
	{
		$this->logNotSupportMethod(__METHOD__);
		return $this;
	}

	/**
	 * 設置後面的where以and連接
	 *
	 * @param callable $callable 如果傳入函數則函數內執行的條件會被()包圍
	 *
	 * @return $this
	 */
	public function _and(callable $callable = null)
	{
		$history = $this->opIsAnd;
		$this->opIsAnd = true;

		if (is_callable($callable)) {
			$this->lBrackets();
			$callable();
			$this->rBrackets();
			$this->opIsAnd = $history;
		}

		return $this;
	}

	/**
	 * 在$or操作中讓一組條件支持多個條件
	 *
	 * @return $this
	 */
	public function lBrackets()
	{
		$this->bracketsIsOpen = true;
		return $this;
	}

	/**
	 * $or操作中關閉一組條件支持多個條件，啟動另外一組條件
	 *
	 * @return $this
	 */
	public function rBrackets()
	{
		$this->bracketsIsOpen = false;
		return $this;
	}

	/**
	 * 設置後面的where以or連接
	 *
	 * @param callable $callable mongodb中元首
	 *
	 * @return $this
	 */
	public function _or(callable $callable = null)
	{
		$history = $this->opIsAnd;
		$this->opIsAnd = false;

		if (is_callable($callable)) {
			$this->lBrackets();
			$callable();
			$this->rBrackets();
			$this->opIsAnd = $history;
		}

		return $this;
	}

	/**
	 * 獲取count(字段名或*)的結果
	 *
	 * @param string $field Mongo中此選項無效
	 * @param bool $isMulti Mongo中此選項無效
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function count($field = '*', $isMulti = false, $useMaster = false)
	{
		$cmd = [
			'count' => $this->getRealTableName(key($this->table)),
			'query' => $this->sql['where']
		];

		$count = $this->runMongoCommand($cmd, $useMaster);
		return intval($count[0]['n']);
	}

	/**
	 * 獲取 $max 的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時此參數為要$group的字段
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function max($field = 'id', $isMulti = false, $useMaster = false)
	{
		return $this->aggregation($field, $isMulti, '$max', $useMaster);
	}

	/**
	 * 獲取聚合的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時此參數為要$group的字段
	 * @param string $operation 聚合操作
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	private function aggregation($field, $isMulti = false, $operation = '$max', $useMaster = false)
	{
		$pipe = [];
		empty($this->sql['where']) || $pipe[] = [
			'$match' => $this->sql['where']
		];
		$pipe[] = [
			'$group' => [
				'_id' => $isMulti ? '$' . $isMulti : '0',
				'count' => [$operation => '$' . $field]
			]
		];
		$res = $this->mongoDbAggregate($pipe, [], $useMaster);
		if ($isMulti === false) {
			return $res[0]['count'];
		} else {
			return $res;
		}
	}

	/**
	 * MongoDb的aggregate封裝
	 *
	 * @param array $pipeline List of pipeline operations
	 * @param array $options Command options
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function mongoDbAggregate($pipeline = [], $options = [], $useMaster = false)
	{
		$cmd = $options + [
				'aggregate' => $this->getRealTableName(key($this->table)),
				'pipeline' => $pipeline
			];

		$data = $this->runMongoCommand($cmd, $useMaster);
		return $data[0]['result'];
	}

	/**
	 * 獲取 $min 的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時此參數為要$group的字段
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function min($field = 'id', $isMulti = false, $useMaster = false)
	{
		return $this->aggregation($field, $isMulti, '$min', $useMaster);
	}

	/**
	 * 獲取 $sum的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時此參數為要$group的字段
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function sum($field = 'id', $isMulti = false, $useMaster = false)
	{
		return $this->aggregation($field, $isMulti, '$sum', $useMaster);
	}

	/**
	 * 獲取 $avg 的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時此參數為要$group的字段
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function avg($field = 'id', $isMulti = false, $useMaster = false)
	{
		return $this->aggregation($field, $isMulti, '$avg', $useMaster);
	}

	/**
	 * MongoDb的distinct封裝
	 *
	 * @param string $field 指定不重複的字段值
	 *
	 * @return mixed
	 */
	public function mongoDbDistinct($field = '')
	{
		$cmd = [
			'distinct' => $this->getRealTableName(key($this->table)),
			'key' => $field,
			'query' => $this->sql['where']
		];

		$data = $this->runMongoCommand($cmd, false);
		return $data[0]['values'];
	}

	/**
	 * 獲取自增id-需要先初始化數據 如:
	 * db.mongoinckeycol.insert({id:0, 'table' : 'post'}) 即初始化帖子表(post)自增初始值為0
	 *
	 * @param string $collection 存儲自增的collection名
	 *
	 * @param string $table 表的名稱
	 *
	 * @return int
	 */
	public function getMongoDbAutoIncKey($collection = 'mongoinckeycol', $table = 'post')
	{
		$res = $this->runMongoCommand([
			'findandmodify' => $collection,
			'update' => [
				'$inc' => ['id' => 1]
			],
			'query' => [
				'table' => $table
			],
			'new' => true
		]);
		return intval($res[0]['value']['id']);
	}

	/**
	 * 構建sql
	 *
	 * @param null $offset 偏移量
	 * @param null $limit 返回的條數
	 * @param bool $isSelect 是否為select調用， 是則不重置查詢參數並返回cacheKey/否則直接返回sql並重置查詢參數
	 *
	 * @return string|array
	 */
	public function buildSql($offset = null, $limit = null, $isSelect = false)
	{
		$this->logNotSupportMethod(__METHOD__);
		return $this;
	}

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
	public function select($offset = null, $limit = null, $useMaster = false, $fieldAsKey = false)
	{
		is_null($offset) || $this->limit($offset, $limit);

		$filter = [];
		count($this->sql['orderBy']) > 0 && $filter['sort'] = $this->sql['orderBy'];
		count($this->sql['columns']) > 0 && $filter['projection'] = $this->sql['columns'];
		isset($this->sql['limit'][0]) && $filter['skip'] = $this->sql['limit'][0];
		isset($this->sql['limit'][1]) && $filter['limit'] = $this->sql['limit'][1];

		$return = $this->runMongoQuery(
			$this->getRealTableName(key($this->table)),
			$this->sql['where'],
			$filter,
			$useMaster
		);

		if ($fieldAsKey) {
			$result = [];
			foreach ($return as $row) {
				$result[$row[$fieldAsKey]] = $row;
			}
			$return = $result;
		}
		return $return;
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
		$limit < 1 && $limit = 100;
		$this->sql['limit'] = [$offset, $limit];
		return $this;
	}

	/**
	 * 返回INSERT，UPDATE 或 DELETE 查詢所影響的記錄行數
	 *
	 * @param \MongoDB\Driver\WriteResult $handle
	 * @param int $type 執行的類型1:insert、2:update、3:delete
	 *
	 * @return int
	 */
	public function affectedRows($handle, $type)
	{
		switch ($type) {
			case 1:
				return $handle->getInsertedCount();
				break;
			case 2:
				return $handle->getModifiedCount();
				break;
			case 3:
				return $handle->getDeletedCount();
				break;
			default:
				return false;
		}
	}

	/**
	 * 指定字段的值+1
	 *
	 * @param string $key 操作的key user-id-1
	 * @param int $val
	 * @param string $field 要改變的字段
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return bool
	 */
	public function increment($key, $val = 1, $field = null, $tablePrefix = null)
	{
		list($tableName, $condition) = $this->parseKey($key, true);
		if (is_null($field) || empty($tableName) || empty($condition)) {
			return false;
		}
		$val = abs(intval($val));
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$tableName = $tablePrefix . $tableName;

		$bulk = new BulkWrite();
		$bulk->update($condition, ['$inc' => [$field => $val]], ['multi' => true]);
		$result = $this->runMongoBulkWrite($tableName, $bulk);

		Cml::$debug && $this->debugLogSql('BulkWrite INC', $tableName, $condition, ['$inc' => [$field => $val]]);

		return $result->getModifiedCount();
	}

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
	public function decrement($key, $val = 1, $field = null, $tablePrefix = null)
	{
		list($tableName, $condition) = $this->parseKey($key, true);
		if (is_null($field) || empty($tableName) || empty($condition)) {
			return false;
		}
		$val = abs(intval($val));

		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$tableName = $tablePrefix . $tableName;

		$bulk = new BulkWrite();
		$bulk->update($condition, ['$inc' => [$field => -$val]], ['multi' => true]);
		$result = $this->runMongoBulkWrite($tableName, $bulk);

		Cml::$debug && $this->debugLogSql('BulkWrite DEC', $tableName, $condition, ['$inc' => [$field => -$val]]);

		return $result->getModifiedCount();
	}

	/**
	 * 關閉連接
	 *
	 */
	public function close()
	{
		if (!empty($this->wlink)) {
			Config::get('session_user') || $this->wlink = null; //開啟會話自定義保存時，不關閉防止會話保存失敗
		}
	}

	/**
	 *獲取mysql 版本
	 *
	 * @param \PDO $link
	 *
	 * @return string
	 */
	public function version($link = null)
	{
		$cursor = $this->getMaster()->executeCommand(
			$this->getDbName(),
			new Command(['buildInfo' => 1])
		);

		$cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
		$info = current($cursor->toArray());
		return $info['version'];
	}

	/**
	 * 開啟事務-MongoDb不支持
	 *
	 * @return bool | $this
	 */
	public function startTransAction()
	{
		$this->logNotSupportMethod(__METHOD__);
		return $this;
	}

	/**
	 * 提交事務-MongoDb不支持
	 *
	 * @return bool | $this
	 */
	public function commit()
	{
		$this->logNotSupportMethod(__METHOD__);
		return $this;
	}

	/**
	 * 設置一個事務保存點-MongoDb不支持
	 *
	 * @param string $pointName
	 *
	 * @return bool | $this
	 */
	public function savePoint($pointName)
	{
		$this->logNotSupportMethod(__METHOD__);
		return $this;
	}

	/**
	 * 回滾事務-MongoDb不支持
	 *
	 * @param bool $rollBackTo 是否為還原到某個保存點
	 *
	 * @return bool | $this
	 */
	public function rollBack($rollBackTo = false)
	{
		$this->logNotSupportMethod(__METHOD__);
		return $this;
	}

	/**
	 * 調用存儲過程-MongoDb不支持
	 *
	 * @param string $procedureName 要調用的存儲過程名稱
	 * @param array $bindParams 綁定的參數
	 * @param bool|true $isSelect 是否為返回數據集的語句
	 *
	 * @return array|int | $this
	 */
	public function callProcedure($procedureName = '', $bindParams = [], $isSelect = true)
	{
		$this->logNotSupportMethod(__METHOD__);
		return $this;
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
			if (!isset($this->conf['slaves']) || empty($this->conf['slaves'])) {
				$this->rlink = $this->wlink;
				return $this->rlink;
			}

			$n = mt_rand(0, count($this->conf['slaves']) - 1);
			$conf = $this->conf['slaves'][$n];
			$this->rlink = $this->connect(
				$conf['host'],
				$conf['username'],
				$conf['password'],
				$conf['dbname'],
				isset($conf['replicaSet']) ? $conf['replicaSet'] : ''
			);
			return $this->rlink;
		} elseif ($db == 'wlink') {
			$conf = $this->conf['master'];
			$this->wlink = $this->connect(
				$conf['host'],
				$conf['username'],
				$conf['password'],
				$conf['dbname'],
				isset($conf['replicaSet']) ? $conf['replicaSet'] : ''
			);
			return $this->wlink;
		}
		return false;
	}

	/**
	 * Db連接
	 *
	 * @param string $host 數據庫host
	 * @param string $username 數據庫用戶名
	 * @param string $password 數據庫密碼
	 * @param string $dbName 數據庫名
	 * @param string $replicaSet replicaSet名稱
	 * @param string $engine 無用
	 * @param bool $pConnect 無用
	 *
	 * @return mixed
	 */
	public function connect($host, $username, $password, $dbName, $replicaSet = '', $engine = '', $pConnect = false)
	{
		$authString = "";
		if ($username && $password) {
			$authString = "{$username}:{$password}@";
		}

		$replicaSet && $replicaSet = '?replicaSet=' . $replicaSet;
		$dsn = "mongodb://{$authString}{$host}/{$dbName}{$replicaSet}";

		return new Manager($dsn);
	}
}
