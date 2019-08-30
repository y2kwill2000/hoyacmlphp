<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架  本類用來兼容舊版本，建議使用Ip2Region類。
 * *********************************************************** */

namespace Cml\Vendor;

use Cml\Http\Request;

/**
 * IP 地理位置查詢類 本類用來兼容舊版本，建議使用Ip2Region類。
 *
 * @package Cml\Vendor
 */
class IpLocation
{
	/**
	 * 獲取客戶端IP地址
	 *
	 * @param integer $type 返回類型 0 返回IP地址 1 返回IPV4地址數字
	 *
	 * @return mixed
	 */
	public static function get_client_ip($type = 0)
	{
		$ip = Request::ip();

		// IP地址合法驗證
		$long = sprintf("%u", ip2long($ip));
		$ip = $long ? [$ip, $long] : ['0.0.0.0', 0];
		return $ip[$type];
	}

	/**
	 * 根據所給 IP 地址或域名返回所在地區信息
	 *
	 * @param string $ip
	 *
	 * @return array
	 */
	public function getlocation($ip = '')
	{
		$obj = new Ip2Region();
		$res = $obj->btreeSearch($ip);
		$res = explode('|', $res['region']);
		return [
			'ip' => $ip,
			'country' => $res[2] . $res[3],
			'area' => $res[4]
		];
	}
}
