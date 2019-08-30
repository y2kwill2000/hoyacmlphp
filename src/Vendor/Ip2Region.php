<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 ip地址查詢庫 -基於ip2region修改https://github.com/lionsoul2014/ip2region
 * *********************************************************** */

namespace Cml\Vendor;

/**
 * ip地址查詢庫
 *
 * @package Cml\Vendor
 */
class Ip2Region
{
	const INDEX_BLOCK_LENGTH = 12;
	const TOTAL_HEADER_LENGTH = 8192;

	/**
	 * db file handler
	 */
	private $dbFileHandler = NULL;

	/**
	 * header block info
	 */
	private $HeaderSip = NULL;
	private $HeaderPtr = NULL;
	private $headerLen = 0;

	/**
	 * super block index info
	 */
	private $firstIndexPtr = 0;
	private $lastIndexPtr = 0;
	private $totalBlocks = 0;

	/**
	 * for memory mode only
	 *  the original db binary string
	 */
	private $dbBinStr = NULL;
	private $dbFile = NULL;

	/**
	 * construct method
	 *
	 * @param string $ip2regionFile
	 */
	public function __construct($ip2regionFile = '')
	{
		$ip2regionFile || $ip2regionFile = CML_EXTEND_PATH . DIRECTORY_SEPARATOR . 'ip2region.db';
		$this->dbFile = $ip2regionFile;
	}

	/**
	 * 根據所給IP地址或域名返回所在地區信息memory
	 *
	 * @param string $ip ip地址
	 *
	 * @return array
	 */
	public function memorySearch($ip)
	{
		//check and load the binary string for the first time
		if ($this->dbBinStr == NULL) {
			$this->dbBinStr = file_get_contents($this->dbFile);
			if ($this->dbBinStr == false) {
				throw new \InvalidArgumentException("Fail to open the db file {$this->dbFile}");
			}

			$this->firstIndexPtr = self::getLong($this->dbBinStr, 0);
			$this->lastIndexPtr = self::getLong($this->dbBinStr, 4);
			$this->totalBlocks = ($this->lastIndexPtr - $this->firstIndexPtr) / self::INDEX_BLOCK_LENGTH + 1;
		}

		if (is_string($ip)) $ip = self::safeIp2long($ip);

		//binary search to define the data
		$l = 0;
		$h = $this->totalBlocks;
		$dataPtr = 0;
		while ($l <= $h) {
			$m = (($l + $h) >> 1);
			$p = $this->firstIndexPtr + $m * self::INDEX_BLOCK_LENGTH;
			$sip = self::getLong($this->dbBinStr, $p);
			if ($ip < $sip) {
				$h = $m - 1;
			} else {
				$eip = self::getLong($this->dbBinStr, $p + 4);
				if ($ip > $eip) {
					$l = $m + 1;
				} else {
					$dataPtr = self::getLong($this->dbBinStr, $p + 8);
					break;
				}
			}
		}

		//not matched just stop it here
		if ($dataPtr == 0) return NULL;

		//get the data
		$dataLen = (($dataPtr >> 24) & 0xFF);
		$dataPtr = ($dataPtr & 0x00FFFFFF);

		return array(
			'city_id' => self::getLong($this->dbBinStr, $dataPtr),
			'region' => substr($this->dbBinStr, $dataPtr + 4, $dataLen - 4)
		);
	}

	/**
	 * read a long from a byte buffer
	 *
	 * @param mixed $b
	 * @param int $offset
	 *
	 * @return int
	 */
	public static function getLong($b, $offset)
	{
		$val = (
			(ord($b[$offset++])) |
			(ord($b[$offset++]) << 8) |
			(ord($b[$offset++]) << 16) |
			(ord($b[$offset]) << 24)
		);

		// convert signed int to unsigned int if on 32 bit operating system
		if ($val < 0 && PHP_INT_SIZE == 4) {
			$val = sprintf("%u", $val);
		}

		return $val;
	}

	/**
	 * safe self::safeIp2long function
	 *
	 * @param string $ip
	 *
	 * @return int
	 * */
	public static function safeIp2long($ip)
	{
		$ip = ip2long($ip);

		// convert signed int to unsigned int if on 32 bit operating system
		if ($ip < 0 && PHP_INT_SIZE == 4) {
			$ip = sprintf("%u", $ip);
		}

		return $ip;
	}

	/**
	 * 根據所給IP地址或域名返回所在地區信息binary
	 *
	 * @param string $ip ip地址
	 *
	 * @return    mixed Array or NULL for any error
	 */
	public function binarySearch($ip)
	{
		//check and conver the ip address
		if (is_string($ip)) $ip = self::safeIp2long($ip);
		if ($this->totalBlocks == 0) {
			//check and open the original db file
			if ($this->dbFileHandler == NULL) {
				$this->dbFileHandler = fopen($this->dbFile, 'r');
				if ($this->dbFileHandler == false) {
					throw new \InvalidArgumentException("Fail to open the db file {$this->dbFile}");
				}
			}

			fseek($this->dbFileHandler, 0);
			$superBlock = fread($this->dbFileHandler, 8);

			$this->firstIndexPtr = self::getLong($superBlock, 0);
			$this->lastIndexPtr = self::getLong($superBlock, 4);
			$this->totalBlocks = ($this->lastIndexPtr - $this->firstIndexPtr) / self::INDEX_BLOCK_LENGTH + 1;
		}

		//binary search to define the data
		$l = 0;
		$h = $this->totalBlocks;
		$dataPtr = 0;
		while ($l <= $h) {
			$m = (($l + $h) >> 1);
			$p = $m * self::INDEX_BLOCK_LENGTH;

			fseek($this->dbFileHandler, $this->firstIndexPtr + $p);
			$buffer = fread($this->dbFileHandler, self::INDEX_BLOCK_LENGTH);
			$sip = self::getLong($buffer, 0);
			if ($ip < $sip) {
				$h = $m - 1;
			} else {
				$eip = self::getLong($buffer, 4);
				if ($ip > $eip) {
					$l = $m + 1;
				} else {
					$dataPtr = self::getLong($buffer, 8);
					break;
				}
			}
		}

		//not matched just stop it here
		if ($dataPtr == 0) return NULL;
		//get the data
		$dataLen = (($dataPtr >> 24) & 0xFF);
		$dataPtr = ($dataPtr & 0x00FFFFFF);

		fseek($this->dbFileHandler, $dataPtr);
		$data = fread($this->dbFileHandler, $dataLen);

		return array(
			'city_id' => self::getLong($data, 0),
			'region' => substr($data, 4)
		);
	}

	/**
	 * 根據所給IP地址或域名返回所在地區信息binary
	 *
	 * @param string $ip ip地址
	 *
	 * @return  Mixed Array for NULL for any error
	 */
	public function btreeSearch($ip)
	{
		if (is_string($ip)) $ip = self::safeIp2long($ip);

		//check and load the header
		if ($this->HeaderSip == NULL) {
			//check and open the original db file
			if ($this->dbFileHandler == NULL) {
				$this->dbFileHandler = fopen($this->dbFile, 'r');
				if ($this->dbFileHandler == false) {
					throw new \InvalidArgumentException("Fail to open the db file {$this->dbFile}");
				}
			}

			fseek($this->dbFileHandler, 8);
			$buffer = fread($this->dbFileHandler, self::TOTAL_HEADER_LENGTH);

			//fill the header
			$idx = 0;
			$this->HeaderSip = array();
			$this->HeaderPtr = array();
			for ($i = 0; $i < self::TOTAL_HEADER_LENGTH; $i += 8) {
				$startIp = self::getLong($buffer, $i);
				$dataPtr = self::getLong($buffer, $i + 4);
				if ($dataPtr == 0) break;

				$this->HeaderSip[] = $startIp;
				$this->HeaderPtr[] = $dataPtr;
				$idx++;
			}

			$this->headerLen = $idx;
		}

		//1. define the index block with the binary search
		$l = 0;
		$h = $this->headerLen;
		$sptr = 0;
		$eptr = 0;
		while ($l <= $h) {
			$m = (($l + $h) >> 1);

			//perfetc matched, just return it
			if ($ip == $this->HeaderSip[$m]) {
				if ($m > 0) {
					$sptr = $this->HeaderPtr[$m - 1];
					$eptr = $this->HeaderPtr[$m];
				} else {
					$sptr = $this->HeaderPtr[$m];
					$eptr = $this->HeaderPtr[$m + 1];
				}

				break;
			}

			//less then the middle value
			if ($ip < $this->HeaderSip[$m]) {
				if ($m == 0) {
					$sptr = $this->HeaderPtr[$m];
					$eptr = $this->HeaderPtr[$m + 1];
					break;
				} else if ($ip > $this->HeaderSip[$m - 1]) {
					$sptr = $this->HeaderPtr[$m - 1];
					$eptr = $this->HeaderPtr[$m];
					break;
				}
				$h = $m - 1;
			} else {
				if ($m == $this->headerLen - 1) {
					$sptr = $this->HeaderPtr[$m - 1];
					$eptr = $this->HeaderPtr[$m];
					break;
				} else if ($ip <= $this->HeaderSip[$m + 1]) {
					$sptr = $this->HeaderPtr[$m];
					$eptr = $this->HeaderPtr[$m + 1];
					break;
				}
				$l = $m + 1;
			}
		}

		//match nothing just stop it
		if ($sptr == 0) {
			return NULL;
		}

		//2. search the index blocks to define the data
		$blockLen = $eptr - $sptr;
		fseek($this->dbFileHandler, $sptr);
		$index = fread($this->dbFileHandler, $blockLen + self::INDEX_BLOCK_LENGTH);

		$dataptr = 0;
		$l = 0;
		$h = $blockLen / self::INDEX_BLOCK_LENGTH;
		while ($l <= $h) {
			$m = (($l + $h) >> 1);
			$p = (int)($m * self::INDEX_BLOCK_LENGTH);
			$sip = self::getLong($index, $p);
			if ($ip < $sip) {
				$h = $m - 1;
			} else {
				$eip = self::getLong($index, $p + 4);
				if ($ip > $eip) {
					$l = $m + 1;
				} else {
					$dataptr = self::getLong($index, $p + 8);
					break;
				}
			}
		}

		//not matched
		if ($dataptr == 0) return NULL;

		//3. get the data
		$dataLen = (($dataptr >> 24) & 0xFF);
		$dataPtr = ($dataptr & 0x00FFFFFF);

		fseek($this->dbFileHandler, $dataPtr);
		$data = fread($this->dbFileHandler, $dataLen);

		return array(
			'city_id' => self::getLong($data, 0),
			'region' => substr($data, 4)
		);
	}

	/**
	 * destruct method, resource destroy
	 */
	public function __destruct()
	{
		if ($this->dbFileHandler != NULL) {
			fclose($this->dbFileHandler);
		}

		$this->dbBinStr = NULL;
		$this->HeaderSip = NULL;
		$this->HeaderPtr = NULL;
	}
}
