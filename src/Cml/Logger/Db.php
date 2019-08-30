<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 18-11-30 下午1:11
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Log Db驅動實現
 * *********************************************************** */

/**
 * 數據表--表名/前綴可自行更改
 * CREATE TABLE `pr_cmlphp_log` (
 * `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主鍵',
 * `level` enum('debug','info','notice','warning','error','critical','alert','emergency') NOT NULL DEFAULT 'debug' COMMENT '日誌等級',
 * `message` text,
 * `context` longtext COMMENT '上下文',
 * `ctime` int(11) unsigned DEFAULT '0' COMMENT '寫入日期',
 * `ip` char(15) NOT NULL DEFAULT '',
 * PRIMARY KEY (`id`),
 * KEY `skey` (`level`,`ctime`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='cmlphp db日誌驅動數據表';
 *
 * 相關配置:
 * db_log_use_db 配置數據表所在的db標識 默認default_db
 * db_log_use_table 配置數據表除前綴的表名 默認 cmlphp_log
 * db_log_use_tableprefix 配置數據表前綴 默認取db中配置的前綴
 */

namespace Cml\Logger;

use Cml\Cml;
use Cml\Config;
use Cml\Http\Request;
use Cml\Model;

/**
 *  Log Db驅動實現
 *
 * @package Cml\Logger
 */
class Db extends Base
{
	/**
	 * 任意等級的日誌記錄
	 *
	 * @param mixed $level 日誌等級
	 * @param string $message 要記錄到log的信息
	 * @param array $context 上下文信息
	 *
	 * @return null
	 */
	public function log($level, $message, array $context = [])
	{
		$db = Config::get('db_log_use_db', 'default_db');
		$table = Config::get('db_log_use_table', 'cmlphp_log');
		$tablePrefix = Config::get('db_log_use_tableprefix', null);

		$context['cmlphp_log_src'] = Request::isCli() ? 'cli' : 'web';

		if ($level === self::EMERGENCY) {//致命錯誤記文件一份，防止db掛掉什麼信息都沒有
			$file = new File();
			$file->log($level, $message, $context);
		}
		return Model::getInstance($table, $tablePrefix, $db)->setCacheExpire(false)->set([
			'level' => $level,
			'message' => $message,
			'context' => json_encode($context, JSON_UNESCAPED_UNICODE),
			'ip' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '',
			'ctime' => Cml::$nowTime
		]);
	}
}
