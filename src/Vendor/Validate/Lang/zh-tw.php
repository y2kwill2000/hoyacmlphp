<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-7-30 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * 數據驗證類語言包
 * *********************************************************** */
return [
	'require' => "{field} 的值不能為空",
	'string' => "{field} 的值必須為字符串",
	'gt' => "{field} 的值必須大於%s",
	'lt' => "{field} 的值必須小於%s",
	'gte' => "{field} 的值必須大於等於%s",
	'lte' => "{field} 的值必須小於等於%s",
	'between' => '{field} 的值必須在%d, %d之間',
	'lengthGt' => "{field} 的長度必須大於%d個字符",
	'lengthLt' => "{field} 的長度必須小於%d個字符",
	'lengthGte' => "{field} 的長度必須大於等於%d個字符",
	'lengthLte' => "{field} 的長度必須小於等於%d個字符",
	'lengthBetween' => "{field} 的長度必須為%d-%d個字符",
	'in' => "{field} 為無效的值",
	'notIn' => "{field} 為無效的值",
	'length' => "{field} 的長度為%d至%d個字",
	'empty' => "{field} 的值必須為空",
	'equals' => "{field} 的值必須和'%s'一致",
	'different' => "{field} 的值必須和'%s'不一致",
	'arr' => "{field} 的值必須是數組",
	'email' => "{field} 的值為無效郵箱地址",
	'ip' => "{field} 的值為無效IP地址",
	'number' => "{field} 的值只能是數字",
	'int' => "{field} 的值只能是整數",
	'bool' => "{field} 的值只能是布爾值",
	'card' => "{field} 的值必須是身份證",
	'mobile' => "{field} 的值必須為手機號",
	'phone' => "{field} 的值必須為固話格式",
	'url' => "{field} 的值為無效的URL",
	'zip' => "{field} 的值必須為郵政編碼",
	'qq' => "{field} 的值必須為qq號格式",
	'english' => "{field} 的值只能包括英文字母(A-Za-z)",
	'chinese' => "{field} 的值只能為中文"
];