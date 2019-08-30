<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 文件上傳擴展類
 * *********************************************************** */

namespace Cml\Vendor;

use Cml\Cml;

/**
 * 文件上傳擴展類
 *
 * @package Cml\Vendor
 */
class UploadFile
{
	private $config = [
		'maxSize' => -1, //上傳文件的最大值
		'allowExts' => [], //允許上傳的文件後綴，留空則不做限制，不帶點
		'allowTypes' => [], //允許上傳的文件類型，留空不作檢查
		'thumb' => false, //對上傳的圖片進行縮略圖處理
		'thumbMaxWidth' => '100',//縮略圖的最大寬度
		'thumbMaxHeight' => '100', //縮略圖的最大高度
		'thumbPrefix' => 'mini_',//縮略圖前綴
		'thumbPath' => '',// 縮略圖保存路徑
		'thumbFile' => '',// 縮略圖文件名 帶後綴
		'subDir' => false,//啟用子目錄保存文件
		'subDirType' => 'hash', //子目錄創建方式，hash\date兩種
		'dateFormat' => 'Y/m/d', //按日期保存的格式
		'hashLevel' => 1, //hash的目錄層次
		'savePath' => '', //上傳文件的保存路徑
		'replace' => false, //替換同名文件
		'rename' => true,//是否生成唯一文件名
	];

	//上傳失敗的信息
	private $errorInfo = '';

	// 上傳成功的文件信息
	private $successInfo;

	/**
	 * 構造方法
	 *
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		is_array($config) && $this->config = array_merge($this->config, $config);
	}

	/**
	 * 魔術方法快速獲取配置
	 *
	 * @param $name
	 *
	 * @return null
	 */
	public function __get($name)
	{
		if (isset($this->config[$name])) {
			return $this->config[$name];
		}
		return null;
	}

	/**
	 * 魔術方法，快速配置參數
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return void
	 */
	public function __set($name, $value)
	{
		if (isset($this->config[$name])) {
			$this->config[$name] = $value;
		}
	}

	/**魔術方法查詢是否存在配置項
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		return isset($this->config[$name]);
	}

	/**
	 * 上傳所有文件
	 *
	 * @param null|string $savePath 上傳文件的保存路徑
	 *
	 * @return bool
	 */
	public function upload($savePath = null)
	{
		is_null($savePath) && $savePath = $this->config['savePath'];
		$savePath = $savePath . '/';
		$fileInfo = [];
		$isUpload = false;

		//獲取上傳的文件信息
		$files = $this->workingFiles($_FILES);
		foreach ($files as $key => $file) {
			//過濾無效的選框
			if (!empty($file['name'])) {
				//登記上傳文件的擴展信息
				!isset($file['key']) && $file['key'] = $key;
				$pathinfo = pathinfo($file['name']);
				$file['extension'] = $pathinfo['extension'];
				$file['savepath'] = $savePath;
				$saveName = $this->getSaveName($savePath, $file);
				$file['savename'] = $saveName;//取得文件名

				//創建目錄
				if (is_dir($file['savepath'])) {
					if (!is_writable($file['savepath'])) {
						$this->errorInfo = "上傳目錄{$savePath}不可寫";
						return false;
					}
				} else {
					if (!mkdir($file['savepath'], 0700, true)) {
						$this->errorInfo = "上傳目錄{$savePath}不可寫";
						return false;
					}
				}
				//自動查檢附件
				if (!$this->secureCheck($file)) return false;
				//保存上傳文件
				if (!$this->save($file)) return false;
				unset($file['tmp_name'], $file['error']);
				$fileInfo[] = $file;
				$isUpload = true;
			}
		}
		if ($isUpload) {
			$this->successInfo = $fileInfo;
			return true;
		} else {
			$this->errorInfo = '沒有選擇上傳文件';
			return false;
		}
	}

	/**
	 * 把同一個選框名有多個文件的上傳信息轉換成跟 單個文件一樣的數組
	 *
	 * @param $files ($_FILES)
	 *
	 * @return array
	 */
	private function workingFiles($files)
	{
		$fileArray = [];
		$n = 0;
		foreach ($files as $key => $file) {
			if (is_array($file['name'])) { //一個表單name有多個文件
				$keys = array_keys($file);
				$count = count($file['name']);
				for ($i = 0; $i < $count; $i++) {
					$fileArray[$n]['key'] = $key; //這邊的key為表單中的file選框的name 比如有兩個上傳框一個叫attach 一個叫img 這兩個都可為數組(多個)
					foreach ($keys as $_key) {
						$fileArray[$n][$_key] = $file[$_key][$i];
					}
					$n++;
				}
			} else {
				$fileArray[$key] = $file;
			}
		}
		return $fileArray;
	}

	/**
	 * 根據上傳文件命名規則取得保存文件名
	 *
	 * @param string $savepath
	 * @param string $filename
	 *
	 * @return string
	 */
	private function getSaveName($savepath, $filename)
	{
		//重命名
		$saveName = $this->config['rename'] ? \Cml\createUnique() . '.' . $filename['extension'] : $filename['name'];
		if ($this->config['subDir']) {
			//使用子目錄保存文件
			switch ($this->config['subDirType']) {
				case 'date':
					$dir = date($this->config['dateFormat'], Cml::$nowTime) . '/';
					break;
				case 'hash':
				default:
					$name = md5($saveName);
					$dir = '';
					for ($i = 0; $i < $this->config['hashLevel']; $i++) {
						$dir .= $name{$i} . '/';
					}
					break;
			}
			if (!is_dir($savepath . $dir)) {
				mkdir($savepath . $dir, 0700, true);
			}
			$saveName = $dir . $saveName;
		}
		return $saveName;
	}

	/**
	 * 檢查上傳的文件有沒上傳成功是否合法
	 *
	 * @param array $file 上傳的單個文件
	 *
	 * @return bool
	 */
	private function secureCheck($file)
	{
		//文件上傳失敗，檢查錯誤碼
		if ($file['error'] != 0) {
			switch ($file['error']) {
				case 1:
					$this->errorInfo = '上傳的文件大小超過了 php.ini 中 upload_max_filesize 選項限制的值';
					break;
				case 2:
					$this->errorInfo = '上傳文件的大小超過了 HTML 表單中 MAX_FILE_SIZE 選項指定的值';
					break;
				case 3:
					$this->errorInfo = '文件只有部分被上傳';
					break;
				case 4:
					$this->errorInfo = '沒有文件被上傳';
					break;
				case 6:
					$this->errorInfo = '找不到臨時文件夾';
					break;
				case 7:
					$this->errorInfo = '文件寫入失敗';
					break;
				default:
					$this->errorInfo = '未知上傳錯誤！';
			}
			return false;
		}

		//文件上傳成功，進行自定義檢查
		if ((-1 != $this->config['maxSize']) && ($file['size'] > $this->config['maxSize'])) {
			$this->errorInfo = '上傳文件大小不符';
			return false;
		}

		//檢查文件Mime類型
		if (!$this->checkType($file['type'])) {
			$this->errorInfo = '上傳文件mime類型允許';
			return false;
		}

		//檢查文件類型
		if (!$this->checkExt($file['extension'])) {
			$this->errorInfo = '上傳文件類型不允許';
			return false;
		}

		//檢查是否合法上傳
		if (!is_uploaded_file($file['tmp_name'])) {
			$this->errorInfo = '非法的上傳文件！';
			return false;
		}
		return true;
	}

	/**
	 * 查檢文件的mime類型是否合法
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	private function checkType($type)
	{
		if (!empty($this->allowTypes)) {
			return in_array(strtolower($type), $this->allowTypes);
		}
		return true;
	}

	/**
	 * 檢查上傳的文件後綴是否合法
	 *
	 * @param string $ext
	 *
	 * @return bool
	 */
	private function checkExt($ext)
	{
		if (!empty($this->allowExts)) {
			return in_array(strtolower($ext), $this->allowExts, true);
		}
		return true;
	}

	/**
	 * 保存
	 *
	 * @param array $file
	 *
	 * @return bool
	 */
	private function save($file)
	{
		$filename = $file['savepath'] . $file['savename'];
		if (!$this->config['replace'] && is_file($filename)) { //不覆蓋同名文件
			$this->errorInfo = "文件已經存在{$filename}";
			return false;
		}

		//如果是圖片，檢查格式
		if (in_array(strtolower($file['extension']), ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'])
			&& false === getimagesize($file['tmp_name'])) {
			$this->errorInfo = '非法圖像文件';
			return false;
		}
		if (!move_uploaded_file($file['tmp_name'], $filename)) {
			$this->errorInfo = '文件上傳錯誤!';
			return false;
		}

		if ($this->config['thumb'] && in_array(strtolower($file['extension']), ['gif', 'jpg', 'jpeg', 'bmp', 'png'])) {
			if ($image = getimagesize($filename)) {
				//生成縮略圖
				$thumbPath = $this->config['thumbPath'] ? $this->config['thumbPath'] : dirname($filename);
				$thunbName = $this->config['thumbFile'] ? $this->config['thumbFile'] : $this->config['thumbPrefix'] . basename($filename);
				Image::makeThumb($filename, $thumbPath . '/' . $thunbName, null, $this->config['thumbMaxWidth'], $this->config['thumbMaxHeight']);
			}
		}
		return true;
	}

	/**
	 * 取得最後一次錯誤信息
	 *
	 * @return string
	 */
	public function getErrorInfo()
	{
		return $this->errorInfo;
	}

	/**
	 * 取得上傳文件的信息
	 *
	 * @return array
	 */
	public function getSuccessInfo()
	{
		return $this->successInfo;
	}

}
