<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Socket客戶端擴展
 * *********************************************************** */

namespace Cml\Vendor;

/**
 * Socket客戶端擴展
 *
 * @package Cml\Vendor
 */
class Socket
{
	/**
	 * 保存連接資源符
	 *
	 * @var null
	 */
	public $connection = null;
	/**
	 * 是否建立了連接
	 *
	 * @var bool
	 */
	public $connected = false;
	protected $config = [
		'persistent' => false, //持久化
		'host' => 'localhost',
		'protocol' => 'tcp', //協議
		'port' => 80,
		'timeout' => 30
	];

	/**
	 * 構造函數
	 *
	 * @param array $config 配置信息
	 */
	public function __construct($config = [])
	{
		$this->config = array_merge($this->config, $config);
		if (!is_numeric($this->config['protocol'])) {
			$this->config['protocol'] = getprotobyname($this->config['protocol']);
		}
	}

	/**
	 * 向服務端寫數據
	 *
	 * @param mixed $data 發送給服務端的數據
	 *
	 * @return bool|int
	 */
	public function write($data)
	{
		if (!$this->connected) {
			if (!$this->connect()) {
				return false;
			}
		}
		return fwrite($this->connection, $data, strlen($data));
	}

	/**
	 * 創建連接
	 *
	 * @return resource
	 */
	public function connect()
	{
		if ($this->connection != null) {
			$this->disconnect();
		}

		if ($this->config['persistent'] == true) {
			$tmp = null;
			$this->connection = @pfsockopen($this->config['host'], $this->config['port'], $errNum, $errStr, $this->config['timeout']);
		} else {
			$this->connection = fsockopen($this->config['host'], $this->config['port'], $errNum, $errStr, $this->config['timeout']);
		}

		if (!empty($errNum) || !empty($errStr)) {
			$this->error($errStr, $errNum);
		}

		$this->connected = is_resource($this->connection);

		return $this->connected;
	}

	/**
	 * 關閉連接
	 *
	 * @return bool
	 */
	public function disconnect()
	{
		if (!is_resource($this->connection)) {
			$this->connected = false;
			return true;
		}
		$this->connected = !fclose($this->connection);

		if (!$this->connected) {
			$this->connection = null;
		}
		return !$this->connected;
	}

	/**
	 * 錯誤處理
	 *
	 * @param string $errStr 錯誤文本
	 * @param int $errNum 錯誤數字
	 *
	 * @throws \Exception
	 */
	public function error($errStr, $errNum)
	{
		$error = 'fsockopen error errorStr:' . $errStr . ';errorNum:' . $errNum;
		throw new \UnexpectedValueException($error);
	}

	/**
	 * 從服務端讀取數據
	 *
	 * @param int $length 要讀取的字節數
	 *
	 * @return bool|string
	 */
	public function read($length = 1024)
	{
		if (!$this->connected) {
			if (!$this->connect()) {
				return false;
			}
		}

		if (!feof($this->connection)) {
			return fread($this->connection, $length);
		} else {
			return false;
		}
	}

	/**
	 * 析構函數
	 */
	public function __destruct()
	{
		$this->disconnect();
	}

}
