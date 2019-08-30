<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 13-12-26 上午11:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 php多線程工作類
 * *********************************************************** */

namespace Cml\Vendor;

/**
 * php多線程工作類
 *
 * @package Cml\Vendor
 */
class PhpThread
{
	/**
	 * 已完成的任務隊列(查看處理結果)
	 *
	 * @var array
	 */
	public $success = [];
	/**
	 * 線程隊列
	 *
	 * @var array
	 */
	protected $queue = [];
	/**
	 * 當前任務隊列
	 *
	 * @var array
	 */
	protected $tasks = [];
	/**
	 * 讀取的字節數
	 *
	 * @var int
	 */
	private $readDataLen = 1024;
	/**
	 * 最大線程數
	 *
	 * @var int
	 */
	private $max;
	/**
	 * 超時時間
	 *
	 * @var int
	 */
	private $timeout = 3;
	/**
	 * 保存處理結果
	 *
	 * @var bool
	 */
	private $saveSuccess = false;

	/**
	 * 構造函數
	 *
	 * @param int $max 最大線程數
	 * @param bool $saveSuccess 是否保存成功的信息
	 * @param int $readDataLen 讀取的字節數
	 * @param int $timeout 等待超時時間
	 */
	public function __construct($max = 10, $saveSuccess = false, $readDataLen = 1024, $timeout = 3)
	{
		$this->max = $max;
		$this->saveSuccess = $saveSuccess;
		$this->readDataLen = $readDataLen;
		$this->timeout = $timeout;
	}

	/**
	 * 往線程隊列添加任務
	 *
	 * @param string $host 服務器
	 * @param string $path 任務程序路徑
	 */
	public function add($host, $path = '/')
	{
		$this->queue[] = ['host' => $host, 'path' => $path];
	}

	/**
	 * 執行線程隊列裡的所有任務
	 *
	 * @return array
	 */
	public function run()
	{
		// 初始化
		reset($this->queue);
		for ($i = 0; $i < $this->max; $i++) {
			if ($this->makeTask() == -1) {
				break;
			}
		}
		// 處理任務隊列
		reset($this->tasks);
		while (count($this->tasks) > 0) {
			$task = current($this->tasks);
			$this->processTask($task);
			if ($task['status'] == -1 || $task['status'] == 2) {
				if ($this->saveSuccess) {
					$this->success[] = $task;
				}
				unset($this->tasks[key($this->tasks)]);
				$this->makeTask();
			} else {
				$this->tasks[key($this->tasks)] = $task;
			}
			if (!next($this->tasks)) {
				reset($this->tasks);
			}
		}
		return $this->getSuccessInfo();
	}

	/**
	 * 創建任務
	 *
	 * @return int 狀態: -1=線程隊列空, 0=失敗, 1=成功
	 */
	private function makeTask()
	{
		$item = each($this->queue);
		if (!$item) {
			return -1;
		}
		$item = $item['value'];
		$socket = @stream_socket_client($item['host'] . ':80', $errno, $errstr, $this->timeout, STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT);
		if ($socket) {
			$this->tasks[] = [
				'host' => $item['host'],
				'path' => $item['path'],
				'socket' => $socket,
				'response' => '',
				'status' => 0, // -1=error, 0=ready, 1=active, 2=done
			];
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * 處理任務
	 *
	 * @param array $task 任務信息
	 */
	private function processTask(&$task)
	{
		$read = $write = [$task['socket']];
		$n = stream_select($read, $write, $e = null, $this->timeout);
		if ($n > 0) {
			switch ($task['status']) {
				case 0: // ready
					fwrite($task['socket'], "GET {$task['path']} HTTP/1.1\r\nHost: {$task['host']}\r\n\r\n");
					$task['status'] = 1;
					break;
				case 1: // active
					$data = fread($task['socket'], $this->readDataLen);
					if (strlen($data) == 0) {
						fclose($task['socket']);
						echo "Failed to connect {$task['host']}.<br />\n";
						$task['status'] = -1;
					} else {
						$task['status'] = 2;
						$task['response'] .= $data;
					}
					break;
			}
		}
	}

	/**
	 * 已完成的任務隊列(查看處理結果)
	 *
	 * @return array
	 */
	public function getSuccessInfo()
	{
		return $this->success;
	}
}
