<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 MySql數據庫 Pdo驅動類
 * *********************************************************** */

namespace Cml\Db\MySql;

use Cml\Cml;
use Cml\Config;
use Cml\Db\Base;
use Cml\Debug;
use Cml\Exception\PdoConnectException;
use Cml\Lang;
use Cml\Log;
use Cml\Model;
use Cml\Plugin;

/**
 * Orm MySql數據庫Pdo實現類
 *
 * @package Cml\Db\MySql
 */
class Pdo extends Base
{
	/**
	 * 啟用數據緩存
	 *
	 * @var bool
	 */
	protected $openCache = true;

	/**
	 * 當前查詢使用的是否是主庫
	 *
	 * @var \PDO
	 */
	private $currentQueryIsMaster = true;

	/**
	 * 當前執行的sql 異常情況用來顯示在錯誤頁/日誌
	 *
	 * @var string
	 */
	private $currentSql = '';

	/**
	 * 用來存儲prepare方法的$resetParams參數 重連用
	 *
	 * @var bool
	 */
	private $currentPrepareIsResetParams = true;

	/**
	 * 強制某表使用某索引
	 *
	 * @var array
	 */
	private $forceIndex = [];

	/**
	 * 數據庫連接串
	 *
	 * @param $conf
	 */
	public function __construct($conf)
	{
		isset($conf['mark']) || $conf['mark'] = md5(json_encode($conf));
		$this->conf = $conf;
		isset($this->conf['log_slow_sql']) || $this->conf['log_slow_sql'] = false;
		$this->tablePrefix = $this->conf['master']['tableprefix'];
		$this->conf['cache_expire'] === false && $this->openCache = false;
	}

	/**
	 * 獲取當前db所有表名
	 *
	 * @return array
	 */
	public function getTables()
	{
		$this->currentQueryIsMaster = false;
		$stmt = $this->prepare('SHOW TABLES;', $this->rlink);
		$this->execute($stmt);

		$tables = [];
		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			$tables[] = $row['Tables_in_' . $this->conf['master']['dbname']];
		}
		return $tables;
	}

	/**
	 * 預處理語句
	 *
	 * @param string $sql 要預處理的sql語句
	 * @param \PDO $link
	 * @param bool $resetParams
	 *
	 * @return \PDOStatement
	 */

	public function prepare($sql, $link = null, $resetParams = true)
	{
		$resetParams && $this->reset();
		is_null($link) && $link = $this->currentQueryIsMaster ? $this->wlink : $this->rlink;

		$sqlParams = [];
		foreach ($this->bindParams as $key => $val) {
			$sqlParams[] = ':param' . $key;
		}

		$this->currentSql = $sql;
		$this->currentPrepareIsResetParams = $resetParams;
		$sql = vsprintf($sql, $sqlParams);

		$stmt = $link->prepare($sql);//pdo默認情況prepare出錯不拋出異常只返回Pdo::errorInfo
		if ($stmt === false) {
			$error = $link->errorInfo();
			if (in_array($error[1], [2006, 2013])) {
				$link = $this->connectDb($this->currentQueryIsMaster ? 'wlink' : 'rlink', true);
				$stmt = $link->prepare($sql);
				if ($stmt === false) {
					$error = $link->errorInfo();
				} else {
					return $stmt;
				}
			}
			throw new \InvalidArgumentException(
				'Pdo Prepare Sql error! ,【Sql: ' . $this->buildDebugSql() . '】,【Code: ' . $link->errorCode() . '】, 【ErrorInfo!: 
                ' . $error[2] . '】 '
			);
		}
		return $stmt;
	}

	/**
	 * 組裝sql用於DEBUG
	 *
	 * @return string
	 */
	private function buildDebugSql()
	{
		$bindParams = $this->bindParams;
		foreach ($bindParams as $key => $val) {
			$bindParams[$key] = str_replace('\\\\', '\\', addslashes($val));
		}
		return vsprintf(str_replace('%s', "'%s'", $this->currentSql), $bindParams);
	}

	/**
	 * 執行預處理語句
	 *
	 * @param object $stmt PDOStatement
	 * @param bool $clearBindParams
	 *
	 * @return bool
	 */
	public function execute($stmt, $clearBindParams = true)
	{
		foreach ($this->bindParams as $key => $val) {
			is_int($val) ? $stmt->bindValue(':param' . $key, $val, \PDO::PARAM_INT) : $stmt->bindValue(':param' . $key, $val, \PDO::PARAM_STR);
		}

		//empty($param) && $param = $this->bindParams;
		$this->conf['log_slow_sql'] && $startQueryTimeStamp = microtime(true);

		$error = false;
		if (!$stmt->execute()) {
			$error = $stmt->errorInfo();
			if (in_array($error[1], [2006, 2013])) {
				$link = $this->connectDb($this->currentQueryIsMaster ? 'wlink' : 'rlink', true);
				$stmt = $this->prepare($this->currentSql, $link, $this->currentPrepareIsResetParams);
				foreach ($this->bindParams as $key => $val) {
					is_int($val) ? $stmt->bindValue(':param' . $key, $val, \PDO::PARAM_INT) : $stmt->bindValue(':param' . $key, $val, \PDO::PARAM_STR);
				}
				if (!$stmt->execute()) {
					$error = $stmt->errorInfo();
				} else {
					$error = false;
				}
			}
		}

		if ($error) {
			throw new \InvalidArgumentException('Pdo execute Sql error!,【Sql : ' . $this->buildDebugSql() . '】,【Code: ' . $error[1] . '】,【Error:' . $error[2] . '】');
		}

		$slow = 0;
		if ($this->conf['log_slow_sql']) {
			$queryTime = microtime(true) - $startQueryTimeStamp;
			if ($queryTime > $this->conf['log_slow_sql']) {
				if (Plugin::hook('cml.mysql_query_slow', ['sql' => $this->buildDebugSql(), 'query_time' => $queryTime]) !== false) {
					Log::notice('slow_sql', ['sql' => $this->buildDebugSql(), 'query_time' => $queryTime]);
				}
				$slow = $queryTime;
			}
		}

		if (Cml::$debug) {
			$this->debugLogSql($slow > 0 ? Debug::SQL_TYPE_SLOW : Debug::SQL_TYPE_NORMAL, $slow);
		}

		$this->currentQueryIsMaster = true;
		$this->currentSql = '';
		$clearBindParams && $this->clearBindParams();
		return true;
	}

	/**
	 * Debug模式記錄查詢語句顯示到控制台
	 *
	 * @param int $type
	 * @param int $other $other type = SQL_TYPE_SLOW時帶上執行時間
	 */
	private function debugLogSql($type = Debug::SQL_TYPE_NORMAL, $other = 0)
	{
		Debug::addSqlInfo($this->buildDebugSql(), $type, $other);
	}

	/**
	 * 獲取當前數據庫中所有表的信息
	 *
	 * @return array
	 */
	public function getAllTableStatus()
	{
		$this->currentQueryIsMaster = false;
		$stmt = $this->prepare('SHOW TABLE STATUS FROM ' . $this->conf['master']['dbname'], $this->rlink);
		$this->execute($stmt);
		$res = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		$return = [];
		foreach ($res as $val) {
			$return[$val['Name']] = $val;
		}
		return $return;
	}

	/**
	 * 獲取表字段
	 *
	 * @param string $table 表名
	 * @param mixed $tablePrefix 表前綴，不傳則獲取配置中配置的前綴
	 * @param int $filter 0 獲取表字段詳細信息數組 1獲取字段以,號相隔組成的字符串
	 *
	 * @return mixed
	 */
	public function getDbFields($table, $tablePrefix = null, $filter = 0)
	{
		static $dbFieldCache = [];

		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		if ($filter == 1 && Cml::$debug) return '*'; //debug模式時直接返回*
		$table = strtolower($tablePrefix . $table);

		$info = false;

		if (isset($dbFieldCache[$table])) {
			$info = $dbFieldCache[$table];
		} else {
			Config::get('db_fields_cache') && $info = \Cml\simpleFileCache($this->conf['master']['dbname'] . '.' . $table);
			if (!$info || Cml::$debug) {
				$this->currentQueryIsMaster = false;
				$stmt = $this->prepare("SHOW COLUMNS FROM $table", $this->rlink, false);
				$this->execute($stmt, false);
				$info = [];
				while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
					$info[$row['Field']] = [
						'name' => $row['Field'],
						'type' => $row['Type'],
						'notnull' => (bool)($row['Null'] === ''), // not null is empty, null is yes
						'default' => $row['Default'],
						'primary' => (strtolower($row['Key']) == 'pri'),
						'autoinc' => (strtolower($row['Extra']) == 'auto_increment'),
					];
				}

				count($info) > 0 && \Cml\simpleFileCache($this->conf['master']['dbname'] . '.' . $table, $info);
			}
			$dbFieldCache[$table] = $info;
		}

		if ($filter) {
			if (count($info) > 0) {
				$info = implode('`,`', array_keys($info));
				$info = '`' . $info . '`';
			} else {
				return '*';
			}
		}
		return $info;
	}

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
	public function get($key, $and = true, $useMaster = false, $tablePrefix = null)
	{
		if (is_string($useMaster) && is_null($tablePrefix)) {
			$tablePrefix = $useMaster;
			$useMaster = false;
		}
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;

		list($tableName, $condition) = $this->parseKey($key, $and);
		$tableName = $tablePrefix . $tableName;
		$sql = "SELECT * FROM {$tableName} WHERE {$condition} LIMIT 0, 1000";

		if ($this->openCache && $this->currentQueryUseCache) {
			$cacheKey = md5($sql . json_encode($this->bindParams)) . $this->getCacheVer($tableName);
			$return = Model::getInstance()->cache()->get($cacheKey);
		} else {
			$return = false;
		}

		if ($return === false) { //cache中不存在這條記錄
			$this->currentQueryIsMaster = $useMaster;
			$stmt = $this->prepare($sql, $useMaster ? $this->wlink : $this->rlink);
			$this->execute($stmt);
			$return = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			$this->openCache && $this->currentQueryUseCache && Model::getInstance()->cache()->set($cacheKey, $return, $this->conf['cache_expire']);
			$this->currentQueryUseCache = true;
		} else {
			if (Cml::$debug) {
				$this->currentSql = $sql;
				$this->debugLogSql(Debug::SQL_TYPE_FROM_CACHE);
				$this->currentSql = '';
			}

			$this->clearBindParams();
		}

		return $return;
	}

	/**
	 * 新增 一條數據
	 *
	 * @param string $table 表名
	 * @param array $data eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return bool|int
	 */
	public function set($table, $data, $tablePrefix = null)
	{
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$tableName = $tablePrefix . $table;
		if (is_array($data)) {
			$s = $this->arrToCondition($data);
			$this->currentQueryIsMaster = true;
			$stmt = $this->prepare("INSERT INTO {$tableName} SET {$s}", $this->wlink);
			$this->execute($stmt);

			$this->setCacheVer($tableName);
			return $this->insertId();
		} else {
			return false;
		}
	}

	/**
	 * 獲取上一INSERT的主鍵值
	 *
	 * @param \PDO $link
	 *
	 * @return int
	 */
	public function insertId($link = null)
	{
		is_null($link) && $link = $this->wlink;
		return $link->lastInsertId();
	}

	/**
	 * 新增多條數據
	 *
	 * @param string $table 表名
	 * @param array $field 字段 eg: ['title', 'msg', 'status', 'ctime『]
	 * @param array $data eg: 多條數據的值 [['標題1', '內容1', 1, '2017'], ['標題2', '內容2', 1, '2017']]
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 * @param bool $openTransAction 是否開啟事務 默認開啟
	 * @return bool|array
	 * @throws \InvalidArgumentException
	 *
	 */
	public function setMulti($table, $field, $data, $tablePrefix = null, $openTransAction = true)
	{
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$tableName = $tablePrefix . $table;
		if (is_array($data) && is_array($field)) {
			$field = array_flip(array_values($field));
			foreach ($field as $key => $val) {
				$field[$key] = $data[0][$val];
			}
			$s = $this->arrToCondition($field);

			try {
				$openTransAction && $this->startTransAction();
				$this->currentQueryIsMaster = true;
				$stmt = $this->prepare("INSERT INTO {$tableName} SET {$s}", $this->wlink);
				$idArray = [];
				foreach ($data as $row) {
					$this->bindParams = array_values($row);
					$this->execute($stmt);
					$idArray[] = $this->insertId();
				}
				$openTransAction && $this->commit();
			} catch (\InvalidArgumentException $e) {
				$openTransAction && $this->rollBack();

				throw new \InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
			}

			$this->setCacheVer($tableName);
			return $idArray;
		} else {
			return false;
		}
	}

	/**
	 * 開啟事務
	 *
	 * @return bool
	 */
	public function startTransAction()
	{
		Cml::$debug && Debug::addSqlInfo('beginTransaction');
		return $this->wlink->beginTransaction();
	}

	/**
	 * 提交事務
	 *
	 * @return bool
	 */
	public function commit()
	{
		Cml::$debug && Debug::addSqlInfo('commit');
		return $this->wlink->commit();
	}

	/**
	 * 回滾事務
	 *
	 * @param bool $rollBackTo 是否為還原到某個保存點
	 *
	 * @return bool
	 */
	public function rollBack($rollBackTo = false)
	{
		if ($rollBackTo === false) {
			Cml::$debug && Debug::addSqlInfo('ROLLBACK');
			return $this->wlink->rollBack();
		} else {
			Cml::$debug && Debug::addSqlInfo("ROLLBACK TO {$rollBackTo}");
			return $this->wlink->exec("ROLLBACK TO {$rollBackTo}");
		}
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
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$tableName = $tablePrefix . $table;
		if (is_array($data)) {
			$s = $this->arrToCondition($data);
			$this->currentQueryIsMaster = true;
			$stmt = $this->prepare("REPLACE INTO {$tableName} SET {$s}", $this->wlink);
			$this->execute($stmt);

			$this->setCacheVer($tableName);
			return $this->insertId();
		} else {
			return false;
		}
	}

	/**
	 * 插入或更新一條記錄，當UNIQUE index or PRIMARY KEY存在的時候更新，不存在的時候插入
	 * 若AUTO_INCREMENT存在則返回 AUTO_INCREMENT 的值.
	 *
	 * @param string $table 表名
	 * @param array $data 插入的值 eg: ['username'=>'admin', 'email'=>'linhechengbush@live.com']
	 * @param array $up 更新的值-會自動merge $data中的數據
	 * @param mixed $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return int
	 */
	public function upSet($table, array $data, array $up = [], $tablePrefix = null)
	{
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$tableName = $tablePrefix . $table;
		if (is_array($data)) {
			$up = $this->arrToCondition(array_merge($data, $up));
			$s = $this->arrToCondition($data);
			$this->currentQueryIsMaster = true;
			$stmt = $this->prepare("INSERT INTO {$tableName} SET {$s} ON DUPLICATE KEY UPDATE {$up}", $this->wlink);
			$this->execute($stmt);

			$this->setCacheVer($tableName);
			return $this->insertId();
		} else {
			return false;
		}
	}

	/**
	 * 插入或替換多條記錄
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
	public function replaceMulti($table, $field, $data, $tablePrefix = null, $openTransAction = true)
	{
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$tableName = $tablePrefix . $table;
		if (is_array($data) && is_array($field)) {
			$field = array_flip(array_values($field));
			foreach ($field as $key => $val) {
				$field[$key] = $data[0][$val];
			}
			$s = $this->arrToCondition($field);

			try {
				$openTransAction && $this->startTransAction();
				$this->currentQueryIsMaster = true;
				$stmt = $this->prepare("REPLACE INTO {$tableName} SET {$s}", $this->wlink);
				$idArray = [];
				foreach ($data as $row) {
					$this->bindParams = array_values($row);
					$this->execute($stmt);
					$idArray[] = $this->insertId();
				}
				$openTransAction && $this->commit();
			} catch (\InvalidArgumentException $e) {
				$openTransAction && $this->rollBack();

				throw new \InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
			}

			$this->setCacheVer($tableName);
			return $idArray;
		} else {
			return false;
		}
	}

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
	public function update($key, $data = null, $and = true, $tablePrefix = null)
	{
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$tableName = $condition = '';

		if (is_array($data)) {
			list($tableName, $condition) = $this->parseKey($key, $and, true, true);
		} else {
			$data = $key;
		}

		if (empty($tableName)) {
			$tableAndCacheKey = $this->tableFactory(false);
			$tableName = $tableAndCacheKey[0];
			$upCacheTables = $tableAndCacheKey[1];
		} else {
			$tableName = $tablePrefix . $tableName;
			$upCacheTables = [$tableName];
			isset($this->forceIndex[$tableName]) && $tableName .= ' force index(' . $this->forceIndex[$tableName] . ') ';
		}

		if (empty($tableName)) {
			throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', 'update'));
		}
		$s = $this->arrToCondition($data);
		$whereCondition = $this->sql['where'];
		$whereCondition .= empty($condition) ? '' : (empty($whereCondition) ? 'WHERE ' : '') . $condition;
		if (empty($whereCondition)) {
			throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_CONDITION_', 'update'));
		}
		$this->currentQueryIsMaster = true;
		$limit = '';
		if ($this->sql['limit']) {
			$limit = explode(',', $this->sql['limit']);
			$limit = 'LIMIT ' . $limit[1];
		}
		$stmt = $this->prepare("UPDATE {$tableName} SET {$s} {$whereCondition} {$limit}", $this->wlink);
		$this->execute($stmt);

		foreach ($upCacheTables as $tb) {
			$this->setCacheVer($tb);
		}
		return $stmt->rowCount();
	}

	/**
	 * table組裝工廠
	 *
	 * @param bool $isRead 是否為讀操作
	 *
	 * @return array
	 */
	private function tableFactory($isRead = true)
	{
		$table = $operator = '';
		$cacheKey = [];
		foreach ($this->table as $key => $val) {
			$realTable = $this->getRealTableName($key);
			$cacheKey[] = $isRead ? $this->getCacheVer($realTable) : $realTable;

			$on = null;
			if (isset($this->join[$key])) {
				$operator = ' INNER JOIN';
				$on = $this->join[$key];
			} elseif (isset($this->leftJoin[$key])) {
				$operator = ' LEFT JOIN';
				$on = $this->leftJoin[$key];
			} elseif (isset($this->rightJoin[$key])) {
				$operator = ' RIGHT JOIN';
				$on = $this->rightJoin[$key];
			} else {
				empty($table) || $operator = ' ,';
			}
			if (is_null($val)) {
				$table .= "{$operator} {$realTable}";
			} else {
				$table .= "{$operator} {$realTable} AS `{$val}`";
			}
			isset($this->forceIndex[$realTable]) && $table .= ' force index(' . $this->forceIndex[$realTable] . ') ';
			is_null($on) || $table .= " ON {$on}";
		}

		if (empty($table)) {
			throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', $isRead ? 'select' : 'update/delete'));
		}
		return [$table, $cacheKey];
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
	 * @param string|int $key eg: 'user'(表名，即條件通過where()傳遞)、'user-uid-$uid'(表名+條件)、啥也不傳(即通過table傳表名)
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

		if (empty($tableName)) {
			$tableAndCacheKey = $this->tableFactory(false);
			$tableName = $tableAndCacheKey[0];
			$upCacheTables = $tableAndCacheKey[1];
		} else {
			$tableName = $tablePrefix . $tableName;
			$upCacheTables = [$tableName];
			isset($this->forceIndex[$tableName]) && $tableName .= ' force index(' . $this->forceIndex[$tableName] . ') ';
		}

		if (empty($tableName)) {
			throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', 'delete'));
		}
		$whereCondition = $this->sql['where'];
		$whereCondition .= empty($condition) ? '' : (empty($whereCondition) ? 'WHERE ' : '') . $condition;
		if (empty($whereCondition)) {
			throw new \InvalidArgumentException(Lang::get('_PARSE_SQL_ERROR_NO_CONDITION_', 'delete'));
		}
		$this->currentQueryIsMaster = true;
		$limit = '';
		if ($this->sql['limit']) {
			$limit = explode(',', $this->sql['limit']);
			$limit = 'LIMIT ' . $limit[1];
		}
		$stmt = $this->prepare("DELETE FROM {$tableName} {$whereCondition} {$limit}", $this->wlink);
		$this->execute($stmt);

		foreach ($upCacheTables as $tb) {
			$this->setCacheVer($tb);
		}
		return $stmt->rowCount();
	}

	/**
	 * 根據表名刪除數據 這個操作太危險慎用。不過一般情況程序也沒這個權限
	 *
	 * @param string $tableName 要清空的表名
	 *
	 * @return bool
	 */
	public function truncate($tableName)
	{
		$tableName = $this->tablePrefix . $tableName;
		$this->currentQueryIsMaster = true;
		$stmt = $this->prepare("TRUNCATE {$tableName}");

		$this->setCacheVer($tableName);
		return $stmt->execute();//不存在會報錯，但無關緊要
	}

	/**
	 * 獲取 COUNT(字段名或*) 的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時相當於執行了 groupBy($isMulti)
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function count($field = '*', $isMulti = false, $useMaster = false)
	{
		return $this->aggregation($field, $isMulti, $useMaster, 'COUNT');
	}

	/**
	 * 獲取max(字段名)的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時相當於執行了 groupBy($isMulti)
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 * @param string $operation 聚合操作
	 *
	 * @return mixed
	 */
	private function aggregation($field, $isMulti = false, $useMaster = false, $operation = 'COUNT')
	{
		is_string($isMulti) && $this->groupBy($isMulti)->columns($isMulti);
		$count = $this->columns(["{$operation}({$field})" => '__res__'])->select(null, null, $useMaster);
		if ($isMulti) {
			$return = [];
			foreach ($count as $val) {
				$return[$val[$isMulti]] = $operation === 'COUNT' ? intval($val['__res__']) : floatval($val['__res__']);
			}
			return $return;
		} else {
			return $operation === 'COUNT' ? intval($count[0]['__res__']) : floatval($count[0]['__res__']);
		}
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
		list($sql, $cacheKey) = $this->buildSql($offset, $limit, true);

		if ($this->openCache && $this->currentQueryUseCache) {
			$cacheKey = md5($sql . json_encode($this->bindParams)) . implode('', $cacheKey) . $fieldAsKey;
			$return = Model::getInstance()->cache()->get($cacheKey);
		} else {
			$return = false;
		}

		if ($return === false) {
			$this->currentQueryIsMaster = $useMaster;
			$stmt = $this->prepare($sql, $useMaster ? $this->wlink : $this->rlink);
			$this->execute($stmt);
			$return = $stmt->fetchAll(\PDO::FETCH_ASSOC);

			if ($fieldAsKey) {
				$result = [];
				foreach ($return as $row) {
					$result[$row[$fieldAsKey]] = $row;
				}
				$return = $result;
			}

			$this->openCache && $this->currentQueryUseCache && Model::getInstance()->cache()->set($cacheKey, $return, $this->conf['cache_expire']);
			$this->currentQueryUseCache = true;
		} else {
			if (Cml::$debug) {
				$this->currentSql = $sql;
				$this->debugLogSql(Debug::SQL_TYPE_FROM_CACHE);
				$this->currentSql = '';
			}

			$this->reset();
			$this->clearBindParams();
		}
		return $return;
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
		is_null($offset) || $this->limit($offset, $limit);

		$this->sql['columns'] == '' && ($this->sql['columns'] = '*');

		$columns = $this->sql['columns'];

		$tableAndCacheKey = $this->tableFactory();

		empty($this->sql['limit']) && ($this->sql['limit'] = "LIMIT 0, 100");

		$sql = "SELECT $columns FROM {$tableAndCacheKey[0]} " . $this->sql['where'] . $this->sql['groupBy'] . $this->sql['having']
			. $this->sql['orderBy'] . $this->union . $this->sql['limit'];
		if ($isSelect) {
			return [$sql, $tableAndCacheKey[1]];
		} else {
			$this->currentSql = $sql;
			$sql = $this->buildDebugSql();
			$this->reset();
			$this->clearBindParams();
			$this->currentSql = '';
			return " ({$sql}) ";
		}
	}

	/**
	 * 獲取 MAX(字段名) 的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時相當於執行了 groupBy($isMulti)
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function max($field = 'id', $isMulti = false, $useMaster = false)
	{
		return $this->aggregation($field, $isMulti, $useMaster, 'MAX');
	}

	/**
	 * 獲取 MIN(字段名) 的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時相當於執行了 groupBy($isMulti)
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function min($field = 'id', $isMulti = false, $useMaster = false)
	{
		return $this->aggregation($field, $isMulti, $useMaster, 'MIN');
	}

	/**
	 * 獲取 SUM(字段名) 的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時相當於執行了 groupBy($isMulti)
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function sum($field = 'id', $isMulti = false, $useMaster = false)
	{
		return $this->aggregation($field, $isMulti, $useMaster, 'SUM');
	}

	/**
	 * 獲取 AVG(字段名) 的結果
	 *
	 * @param string $field 要統計的字段名
	 * @param bool|string $isMulti 結果集是否為多條 默認只有一條。傳字符串時相當於執行了 groupBy($isMulti)
	 * @param bool|string $useMaster 是否使用主庫 默認讀取從庫
	 *
	 * @return mixed
	 */
	public function avg($field = 'id', $isMulti = false, $useMaster = false)
	{
		return $this->aggregation($field, $isMulti, $useMaster, 'AVG');
	}

	/**
	 * 強制使用索引
	 *
	 * @param string $table 要強制索引的表名(不帶前綴)
	 * @param string $index 要強制使用的索引
	 * @param string $tablePrefix 表前綴 不傳則獲取配置中配置的前綴
	 *
	 * @return $this
	 */
	public function forceIndex($table, $index, $tablePrefix = null)
	{
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$this->forceIndex[$tablePrefix . $table] = $index;
		return $this;
	}

	/**
	 * 返回INSERT，UPDATE 或 DELETE 查詢所影響的記錄行數。
	 *
	 * @param $handle \PDOStatement
	 * @param int $type 執行的類型1:insert、2:update、3:delete
	 *
	 * @return int
	 */
	public function affectedRows($handle, $type)
	{
		return $handle->rowCount();
	}

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
	public function connect($host, $username, $password, $dbName, $charset = 'utf8', $engine = '', $pConnect = false)
	{
		$link = '';

		$host = explode(':', $host);
		if (substr($host[0], 0, 11) === 'unix_socket') {
			$dsn = "mysql:dbname={$dbName};unix_socket=" . substr($host[0], 12);
		} else {
			$dsn = "mysql:host={$host[0]};" . (isset($host[1]) ? "port={$host[1]};" : '') . "dbname={$dbName}";
		}

		$doConnect = function () use ($dsn, $pConnect, $charset, $username, $password) {
			if ($pConnect) {
				return new \PDO($dsn, $username, $password, [
					\PDO::ATTR_PERSISTENT => true,
					\PDO::ATTR_EMULATE_PREPARES => false,
					\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset"
				]);
			} else {
				return new \PDO($dsn, $username, $password, [
					\PDO::ATTR_EMULATE_PREPARES => false,
					\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset"
				]);
			}
		};

		try {
			$link = $doConnect();
		} catch (\PDOException $e) {
			$connectError = true;

			if (in_array($e->getCode(), [2006, 2013])) {
				try {
					$link = $doConnect();
					$connectError = false;
				} catch (\PDOException $e) {
				}
			}

			if ($connectError) {
				throw new PdoConnectException(
					'Pdo Connect Error! ｛' .
					$host[0] . (isset($host[1]) ? ':' . $host[1] : '') . ', ' . $dbName .
					'} Code:' . $e->getCode() . ', ErrorInfo!:' . $e->getMessage(),
					0,
					$e
				);
			}
		}
		//$link->exec("SET names $charset");
		isset($this->conf['sql_mode']) && $link->exec('set sql_mode="' . $this->conf['sql_mode'] . '";'); //放數據庫配 特殊情況才開
		if (!empty($engine) && $engine == 'InnoDB') {
			$link->exec('SET innodb_flush_log_at_trx_commit=2');
		}
		return $link;
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
			$this->clearBindParams();
			return false;
		}
		$val = abs(intval($val));
		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$tableName = $tablePrefix . $tableName;

		$this->currentQueryIsMaster = true;
		$stmt = $this->prepare('UPDATE  `' . $tableName . "` SET  `{$field}` =  `{$field}` + {$val}  WHERE  $condition", $this->wlink);

		$this->execute($stmt);
		$this->setCacheVer($tableName);
		return $stmt->rowCount();
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
			$this->clearBindParams();
			return false;
		}
		$val = abs(intval($val));

		is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
		$tableName = $tablePrefix . $tableName;
		$this->currentQueryIsMaster = true;
		$stmt = $this->prepare('UPDATE  `' . $tableName . "` SET  `$field` =  `$field` - $val  WHERE  $condition", $this->wlink);

		$this->execute($stmt);
		$this->setCacheVer($tableName);
		return $stmt->rowCount();
	}

	/**
	 * 關閉連接
	 *
	 */
	public function close()
	{
		if (!Config::get('session_user')) {
			//開啟會話自定義保存時，不關閉防止會話保存失敗
			$this->wlink = null;
			unset($this->wlink);
		}

		$this->rlink = null;
		unset($this->rlink);
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
		is_null($link) && $link = $this->wlink;
		return $link->getAttribute(\PDO::ATTR_SERVER_VERSION);
	}

	/**
	 * 設置一個事務保存點
	 *
	 * @param string $pointName
	 *
	 * @return bool
	 */
	public function savePoint($pointName)
	{
		Cml::$debug && Debug::addSqlInfo("SAVEPOINT {$pointName}");
		return $this->wlink->exec("SAVEPOINT {$pointName}");
	}

	/**
	 * 調用存儲過程
	 *
	 * @param string $procedureName 要調用的存儲過程名稱
	 * @param array $bindParams 綁定的參數
	 * @param bool|true $isSelect 是否為返回數據集的語句
	 *
	 * @return array|int
	 */
	public function callProcedure($procedureName = '', $bindParams = [], $isSelect = true)
	{
		$this->bindParams = $bindParams;
		$this->currentQueryIsMaster = true;
		$stmt = $this->prepare("exec {$procedureName}", $this->wlink);
		$this->execute($stmt);
		if ($isSelect) {
			return $stmt->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			return $stmt->rowCount();
		}
	}
}
