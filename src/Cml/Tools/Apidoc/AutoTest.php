<?php
/* * *********************************************************
* 從代碼註釋提取接口信息自動運行測試
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2017/5/3 15:14
* *********************************************************** */

namespace Cml\Tools\Apidoc;

use Cml\Http\Request;
use Cml\Plugin;

/**
 * 從代碼註釋提取接口信息自動運行測試類
 *
 * @package Cml\Tools\Apidoc
 */
class AutoTest
{
	/**
	 * 運行測試
	 *
	 * @param string $app 接口項目的名稱
	 * @param string $trueCode 成功返回的code，多個用+分隔
	 *
	 * @return int
	 */
	public static function run($app = 'api', $trueCode = '0')
	{
		Plugin::hook('cml.before_run_api_test');

		$apiData = AnnotationToDoc::parse('layui', $app, false);

		$apiUrl = $apiData['config']['api_url'];
		$needTestList = $apiData['result'];

		$num = 0;
		array_walk($needTestList, function ($apiList, $version) use ($apiUrl, $trueCode, &$num) {
			array_walk($apiList, function ($api, $method) use ($version, $apiUrl, $trueCode, &$num) {
				$num++;
				$api['req'] = preg_replace([
					"#//(.*?)\n#",
					"#,(\s*?)}#"
				], [
					"\n",
					'}'
				], $api['req']);

				if (false == json_decode($api['req'])) {
					throw new \InvalidArgumentException("req is not Invalid JSON![method:($method)], [params:({$api['req']})]");
				}
				$pluginRes = Plugin::hook('cml.before_run_api_test_one_api', $api);
				is_null($pluginRes) || $api = $pluginRes;

				$res = Request::curl($apiUrl, $api['req'], [], 'raw');
				$resArray = json_decode($res, true);
				if (!$resArray || !in_array($resArray['code'], explode('+', $trueCode))) {
					throw new \InvalidArgumentException("api auto test failure!, [method:($method)], [params:({$api['req']})], [result:({$res})]");
				}
			});
		});
		if (!Request::isCli()) {
			echo "api auto test success! api num ({$num})";
		}
		return $num;
	}
}
