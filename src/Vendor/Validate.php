<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 數據驗證類
 * *********************************************************** */

namespace Cml\Vendor;

use Cml\Cml;
use Cml\Config;
use Cml\Exception\FileCanNotReadableException;
use Cml\Lang;

/**
 * 數據驗證類,封裝了常用的數據驗證接口
 *
 * @package Cml\Vendor
 */
class Validate
{

	/**
	 * 自定義的規則
	 *
	 * @var array
	 */
	private static $rules = [];
	/**
	 * 錯誤提示語
	 *
	 * @var array
	 */
	private static $errorTip = [];
	/**
	 * 要驗證的數組
	 * @var array
	 */
	private $data = [];
	/**
	 * 數據綁定的驗證規則
	 *
	 * @var array
	 */
	private $dateBindRule = [];
	/**
	 * 驗證後的錯誤信息
	 *
	 * @var array
	 */
	private $errorMsg = [];

	/**
	 * 字段別名
	 *
	 * @var array
	 */
	private $label = [];

	/**
	 * 初始化要檢驗的參數
	 *
	 * @param array $data 包含要驗證數據的數組
	 * @param string|null $langDir 語言包所在的路徑
	 *
	 */
	public function __construct(array $data = [], $langDir = null)
	{
		if (is_null($langDir)) {
			$langDir = __DIR__ . '/Validate/Lang/' . Config::get('lang') . '.php';
		}

		if (!is_file($langDir)) {
			throw new FileCanNotReadableException(Lang::get('_NOT_FOUND_', 'lang dir [' . $langDir . ']'));
		}

		$errorTip = Cml::requireFile($langDir);
		foreach ($errorTip as $key => $val) {
			$key = strtolower($key);
			isset(self::$errorTip[$key]) || self::$errorTip[$key] = $val;
		}

		$this->data = $data;
	}

	/**
	 * 動態覆蓋語言包
	 *
	 * @param array $errorTip
	 */
	public static function setLang($errorTip = [])
	{
		self::$errorTip = array_merge(self::$errorTip, $errorTip);
	}

	/**
	 * 添加一個自定義的驗證規則
	 *
	 * @param string $name
	 * @param mixed $callback
	 * @param string $message
	 * @throws \InvalidArgumentException
	 */
	public static function addRule($name, $callback, $message = 'error param')
	{
		if (!is_callable($callback)) {
			throw new \InvalidArgumentException('param $callback must can callable');
		}
		self::$errorTip[strtolower($name)] = $message;
		static::$rules[$name] = $callback;
	}

	/**
	 * 數據基礎驗證-是否必須填寫的參數
	 *
	 * @param string $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isRequire($value)
	{
		if (is_null($value)) {
			return false;
		} elseif (is_string($value) && trim($value) === '') {
			return false;
		}

		return true;
	}

	/**
	 * 數據基礎驗證-是否為字符串參數
	 *
	 * @param string $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isString($value)
	{
		return is_string($value);
	}

	/**
	 * 數據基礎驗證-是否大於
	 *
	 * @param int $value 要比較的值
	 * @param int $max 要大於的長度
	 *
	 * @return bool
	 */
	public static function isGt($value, $max)
	{
		is_array($max) && $max = $max[0];
		if (!is_numeric($value)) {
			return false;
		} elseif (function_exists('bccomp')) {
			return bccomp($value, $max, 14) == 1;
		} else {
			return $value > $max;
		}
	}

	/**
	 * 數據基礎驗證-是否小於
	 *
	 * @param int $value 要比較的值
	 * @param int $min 要小於的長度
	 *
	 * @return bool
	 */
	public static function isLt($value, $min)
	{
		is_array($min) && $min = $min[0];
		if (!is_numeric($value)) {
			return false;
		} elseif (function_exists('bccomp')) {
			return bccomp($min, $value, 14) == 1;
		} else {
			return $value < $min;
		}
	}

	/**
	 * 數據基礎驗證-是否大於等於
	 *
	 * @param int $value 要比較的值
	 * @param int $max 要大於的長度
	 *
	 * @return bool
	 */
	public static function isGte($value, $max)
	{
		is_array($max) && $max = $max[0];
		if (!is_numeric($value)) {
			return false;
		} else {
			return $value >= $max;
		}
	}

	/**
	 * 數據基礎驗證-是否小於等於
	 *
	 * @param int $value 要比較的值
	 * @param int $min 要小於的長度
	 *
	 * @return bool
	 */
	public static function isLte($value, $min)
	{
		is_array($min) && $min = $min[0];
		if (!is_numeric($value)) {
			return false;
		} else {
			return $value <= $min;
		}
	}

	/**
	 * 數據基礎驗證-數字的值是否在區間內
	 *
	 * @param string $value 字符串
	 * @param int $start 起始數字
	 * @param int $end 結束數字
	 *
	 * @return bool
	 */
	public static function isBetween($value, $start, $end)
	{
		if (is_array($start)) {
			$end = $start[1];
			$start = $start[0];
		}

		if ($value > $end || $value < $start) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 數據基礎驗證-字符串長度是否大於
	 *
	 * @param string $value 字符串
	 * @param int $max 要大於的長度
	 *
	 * @return bool
	 */
	public static function isLengthGt($value, $max)
	{
		$value = trim($value);
		if (!is_string($value)) {
			return false;
		}
		$length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
		is_array($max) && $max = $max[0];

		if ($max != 0 && $length <= $max) return false;
		return true;
	}

	/**
	 * 數據基礎驗證-字符串長度是否小於
	 *
	 * @param string $value 字符串
	 * @param int $min 要小於的長度
	 *
	 * @return bool
	 */
	public static function isLengthLt($value, $min)
	{
		$value = trim($value);
		if (!is_string($value)) {
			return false;
		}
		$length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
		is_array($min) && $min = $min[0];

		if ($min != 0 && $length >= $min) return false;
		return true;
	}

	/**
	 * 長度是否在某區間內(包含邊界)
	 *
	 * @param string $value 字符串
	 * @param int $min 要小於等於的長度
	 * @param int $max 要大於等於的長度
	 *
	 * @return bool
	 */
	public static function isLengthBetween($value, $min, $max)
	{
		if (is_array($min)) {
			$max = $min[1];
			$min = $min[0];
		}

		if (self::isLengthGte($value, $min) && self::isLengthLte($value, $max)) {
			return true;
		}

		return false;
	}

	/**
	 * 數據基礎驗證-字符串長度是否大於等於
	 *
	 * @param string $value 字符串
	 * @param int $max 要大於的長度
	 *
	 * @return bool
	 */
	public static function isLengthGte($value, $max)
	{
		is_array($max) && $max = $max[0];
		return self::isLength($value, $max);
	}

	/**
	 * 數據基礎驗證-檢測字符串長度
	 *
	 * @param string $value 需要驗證的值
	 * @param int $min 字符串最小長度
	 * @param int $max 字符串最大長度
	 *
	 * @return bool
	 */
	public static function isLength($value, $min = 0, $max = 0)
	{
		$value = trim($value);
		if (!is_string($value)) {
			return false;
		}
		$length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);

		if (is_array($min)) {
			$max = $min[1];
			$min = $min[0];
		}
		if ($min != 0 && $length < $min) return false;
		if ($max != 0 && $length > $max) return false;
		return true;
	}

	/**
	 * 數據基礎驗證-字符串長度是否小於等於
	 *
	 * @param string $value 字符串
	 * @param int $min 要小於的長度
	 *
	 * @return bool
	 */
	public static function isLengthLte($value, $min)
	{
		is_array($min) && $min = $min[0];
		return self::isLength($value, 0, $min);
	}

	/**
	 * 數據基礎驗證-判斷數據是否在數組中
	 *
	 * @param string $value 字符串
	 * @param array $array 比較的數組
	 *
	 * @return bool
	 */
	public static function isIn($value, $array)
	{
		is_array($array[0]) && $array = $array[0];
		return in_array($value, $array);
	}

	/**
	 * 數據基礎驗證-判斷數據是否在數組中
	 *
	 * @param string $value 字符串
	 * @param array $array 比較的數組
	 *
	 * @return bool
	 */
	public static function isNotIn($value, $array)
	{
		is_array($array[0]) && $array = $array[0];
		return !in_array($value, $array);
	}

	/**
	 * 數據基礎驗證-是否是空字符串
	 *
	 * @param string $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isEmpty($value)
	{
		if (empty($value)) return true;
		return false;
	}

	/**
	 * 數據基礎驗證-檢測數組，數組為空時候也返回false
	 *
	 * @param string $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isArr($value)
	{
		if (!is_array($value) || empty($value)) {
			return false;
		}
		return true;
	}

	/**
	 * 數據基礎驗證-是否是Email 驗證：xxx@qq.com
	 *
	 * @param string $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isEmail($value)
	{
		return filter_var($value, \FILTER_VALIDATE_EMAIL) !== false;
	}

	/**
	 * 數據基礎驗證-是否是IP
	 *
	 * @param string $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isIp($value)
	{
		return filter_var($value, \FILTER_VALIDATE_IP) !== false;
	}

	/**
	 * 數據基礎驗證-是否是數字類型
	 *
	 * @param string $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isNumber($value)
	{
		return is_numeric($value);
	}

	/**
	 * 數據基礎驗證-是否是整型
	 *
	 * @param int $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isInt($value)
	{
		return filter_var($value, \FILTER_VALIDATE_INT) !== false;
	}

	/**
	 * 數據基礎驗證-是否是布爾類型
	 *
	 * @param int $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isBool($value)
	{
		return (is_bool($value)) ? true : false;
	}

	/**
	 * 數據基礎驗證-是否是身份證
	 *
	 * @param string $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isCard($value)
	{
		return preg_match("/^(\d{15}|\d{17}[\dx])$/i", $value);
	}

	/**
	 * 數據基礎驗證-是否是移動電話 驗證：1385810XXXX
	 *
	 * @param string $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isMobile($value)
	{
		return preg_match('/^[+86]?1[3546789][0-9]{9}$/', trim($value));
	}

	/**
	 * 數據基礎驗證-是否是電話 驗證：0571-xxxxxxxx
	 *
	 * @param string $value 需要驗證的值
	 * @return bool
	 */
	public static function isPhone($value)
	{
		return preg_match('/^[0-9]{3,4}[\-]?[0-9]{7,8}$/', trim($value));
	}

	/**
	 * 數據基礎驗證-是否是URL 驗證：http://www.baidu.com
	 *
	 * @param string $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isUrl($value)
	{
		return filter_var($value, \FILTER_VALIDATE_URL) !== false;
	}

	/**
	 * 數據基礎驗證-是否是郵政編碼 驗證：311100
	 *
	 * @param string $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isZip($value)
	{
		return preg_match('/^[1-9]\d{5}$/', trim($value));
	}

	/**
	 * 數據基礎驗證-是否是QQ
	 *
	 * @param string $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isQq($value)
	{
		return preg_match('/^[1-9]\d{4,12}$/', trim($value));
	}

	/**
	 * 數據基礎驗證-是否是英文字母
	 *
	 * @param string $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isEnglish($value)
	{
		return preg_match('/^[A-Za-z]+$/', trim($value));
	}

	/**
	 * 數據基礎驗證-是否是中文
	 *
	 * @param string $value 需要驗證的值
	 *
	 * @return bool
	 */
	public static function isChinese($value)
	{
		return preg_match("/^([\xE4-\xE9][\x80-\xBF][\x80-\xBF])+$/", trim($value));
	}

	/**
	 * 檢查是否是安全的賬號
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public static function isSafeAccount($value)
	{
		return preg_match("/^[a-zA-Z]{1}[a-zA-Z0-9_\.]{3,31}$/", $value);
	}

	/**
	 * 檢查是否是安全的暱稱
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public static function isSafeNickname($value)
	{
		return preg_match("/^[-\x{4e00}-\x{9fa5}a-zA-Z0-9_\.]{2,10}$/u", $value);
	}

	/**
	 * 檢查是否是安全的密碼
	 *
	 * @param string $str
	 *
	 * @return bool
	 */
	public static function isSafePassword($str)
	{
		if (preg_match('/[\x80-\xff]./', $str) || preg_match('/\'|"|\"/', $str) || strlen($str) < 6 || strlen($str) > 20) {
			return false;
		}
		return true;
	}

	/**
	 * 檢查是否是正確的標識符
	 *
	 * @param string $value 以字母或下劃線開始，後面跟著任何字母，數字或下劃線。
	 *
	 * @return mixed
	 */
	public static function isIdentifier($value)
	{
		return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]+$/', trim($value));
	}

	/**
	 * 批量綁定校驗規則到字段
	 *
	 * @param array $rules
	 *
	 * @return $this
	 */
	public function rules($rules)
	{
		foreach ($rules as $rule => $field) {
			if (is_array($field) && is_array($field[0])) {
				foreach ($field as $params) {
					array_unshift($params, $rule);
					call_user_func_array([$this, 'rule'], $params);
				}
			} else {
				$this->rule($rule, $field);
			}
		}
		return $this;
	}

	/**
	 * 綁定校驗規則到字段
	 *
	 * @param string $rule
	 * @param array|string $field
	 *
	 * @return $this
	 */
	public function rule($rule, $field)
	{
		$ruleMethod = 'is' . ucfirst($rule);
		if (!isset(static::$rules[$rule]) && !method_exists($this, $ruleMethod)) {
			throw new \InvalidArgumentException(Lang::get('_NOT_FOUND_', 'validate rule [' . $rule . ']'));
		}

		$params = array_slice(func_get_args(), 2);

		$this->dateBindRule[] = [
			'rule' => $rule,
			'field' => (array)$field,
			'params' => (array)$params
		];
		return $this;
	}

	/**
	 * 自定義錯誤提示信息
	 *
	 * @param string $msg
	 * @return $this
	 */
	public function message($msg)
	{
		$this->dateBindRule[count($this->dateBindRule) - 1]['message'] = $msg;

		return $this;
	}

	/**
	 * 執行校驗並返回布爾值
	 *
	 * @return boolean
	 */
	public function validate()
	{
		foreach ($this->dateBindRule as $bind) {
			foreach ($bind['field'] as $field) {
				if (strpos($field, '.')) {
					$values = Cml::doteToArr($field, $this->data);
				} else {
					$values = isset($this->data[$field]) ? $this->data[$field] : null;
				}

				if (isset(static::$rules[$bind['rule']])) {
					$callback = static::$rules[$bind['rule']];
				} else {
					$callback = [$this, 'is' . ucfirst($bind['rule'])];
				}

				$result = true;
				if ($bind['rule'] == 'arr') {
					$result = call_user_func($callback, $values, $bind['params'], $field);
				} else {
					is_array($values) || $values = [$values];// GET|POST的值為數組的時候每個值都進行校驗
					foreach ($values as $value) {
						$result = $result && call_user_func($callback, $value, $bind['params'], $field);
						if (!$result) {
							break;
						}
					}
				}

				if (!$result) {
					$this->error($field, $bind);
				}
			}
		}

		return count($this->getErrors()) === 0;
	}

	/**
	 * 添加一條錯誤信息
	 *
	 * @param string $field
	 * @param array $bind
	 */
	private function error($field, &$bind)
	{
		$label = (isset($this->label[$field]) && !empty($this->label[$field])) ? $this->label[$field] : $field;
		$this->errorMsg[$field][] = vsprintf(str_replace('{field}', $label, (isset($bind['message']) ? $bind['message'] : self::$errorTip[strtolower($bind['rule'])])), $bind['params']);
	}

	/**
	 * 獲取所有錯誤信息
	 *
	 * @param int $format 返回的格式 0返回數組，1返回json,2返回字符串
	 * @param string $delimiter format為2時分隔符
	 * @return array|string
	 */
	public function getErrors($format = 0, $delimiter = ', ')
	{
		switch ($format) {
			case 1:
				return json_encode($this->errorMsg, JSON_UNESCAPED_UNICODE);
			case 2:
				$return = '';
				foreach ($this->errorMsg as $val) {
					$return .= ($return == '' ? '' : $delimiter) . implode($delimiter, $val);
				}
				return $return;
		}
		return $this->errorMsg;
	}

	/**
	 * 設置字段顯示別名
	 *
	 * @param string|array $label
	 *
	 * @return $this
	 */
	public function label($label)
	{
		if (is_array($label)) {
			$this->label = array_merge($this->label, $label);
		} else {
			$this->label[$this->dateBindRule[count($this->dateBindRule) - 1]['field'][0]] = $label;
		}

		return $this;
	}

	/**
	 * 驗證兩個字段相等
	 *
	 * @param string $compareField
	 * @param string $field
	 *
	 * @return bool
	 */
	protected function isEquals($value, $compareField, $field)
	{
		is_array($compareField) && $compareField = $compareField[0];
		return isset($this->data[$field]) && isset($this->data[$compareField]) && $this->data[$field] == $this->data[$compareField];
	}

	/**
	 * 驗證兩個字段不等
	 *
	 * @param string $compareField
	 * @param string $field
	 *
	 * @return bool
	 */
	protected function isDifferent($value, $compareField, $field)
	{
		is_array($compareField) && $compareField = $compareField[0];
		return isset($this->data[$field]) && isset($this->data[$compareField]) && $this->data[$field] != $this->data[$compareField];
	}
}
