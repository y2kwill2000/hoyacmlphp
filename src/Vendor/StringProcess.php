<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 字符串處理類
 * *********************************************************** */

namespace Cml\Vendor;

/**
 * 字符串處理類,包含字符串截取、獲取隨機字符串等
 *
 * @package Cml\Vendor
 */
class StringProcess
{

	/**
	 * 返回兩個字符串的相似度
	 *
	 * @param string $string1
	 * @param string $string2
	 * @return int
	 */
	public static function strSimilar($string1, $string2)
	{
		similar_text($string1, $string2, $percent);
		return round($percent / 100, 2);
	}

	/**
	 * 計算兩個字符串間的levenshteinDistance
	 * @param string $string1
	 * @param string $string2
	 * @param int $costReplace 定義替換次數
	 * @param string $encoding
	 * @return mixed
	 */
	public static function levenshteinDistance($string1, $string2, $costReplace = 1, $encoding = 'UTF-8')
	{
		$mbStringToArrayFunc = function ($string) use ($encoding) {
			$arrayResult = [];
			while ($iLen = mb_strlen($string, $encoding)) {
				array_push($arrayResult, mb_substr($string, 0, 1, $encoding));
				$string = mb_substr($string, 1, $iLen, $encoding);
			}
			return $arrayResult;
		};

		$countSameLetter = 0;
		$d = [];
		$mbLen1 = mb_strlen($string1, $encoding);
		$mbLen2 = mb_strlen($string2, $encoding);

		$mbStr1 = $mbStringToArrayFunc($string1, $encoding);
		$mbStr2 = $mbStringToArrayFunc($string2, $encoding);

		$maxCount = count($mbStr1) > count($mbStr2) ? count($mbStr1) : count($mbStr2);

		for ($i1 = 0; $i1 <= $mbLen1; $i1++) {
			$d[$i1] = [];
			$d[$i1][0] = $i1;
		}

		for ($i2 = 0; $i2 <= $mbLen2; $i2++) {
			$d[0][$i2] = $i2;
		}

		for ($i1 = 1; $i1 <= $mbLen1; $i1++) {
			for ($i2 = 1; $i2 <= $mbLen2; $i2++) {
				// $cost = ($str1[$i1 - 1] == $str2[$i2 - 1]) ? 0 : 1;
				if ($mbStr1[$i1 - 1] === $mbStr2[$i2 - 1]) {
					$cost = 0;
					$countSameLetter++;
				} else {
					$cost = $costReplace; //替換
				}
				$d[$i1][$i2] = min($d[$i1 - 1][$i2] + 1, //插入
					$d[$i1][$i2 - 1] + 1, //刪除
					$d[$i1 - 1][$i2 - 1] + $cost);
			}
		}

		$percent = round(($maxCount - $d[$mbLen1][$mbLen2]) / $maxCount, 2);

		//return $d[$mbLen1][$mbLen2];
		return ['distance' => $d[$mbLen1][$mbLen2], 'count_same_letter' => $countSameLetter, 'percent' => $percent];
	}

	/**
	 * 檢查字符串是否是UTF8編碼
	 *
	 * @param string $string 字符串
	 *
	 * @return Boolean
	 */
	public static function isUtf8($string)
	{
		$len = strlen($string);
		for ($i = 0; $i < $len; $i++) {
			$c = ord($string[$i]);
			if ($c > 128) {
				if (($c >= 254)) {
					return false;
				} elseif ($c >= 252) {
					$bits = 6;
				} elseif ($c >= 248) {
					$bits = 5;
				} elseif ($c >= 240) {
					$bits = 4;
				} elseif ($c >= 224) {
					$bits = 3;
				} elseif ($c >= 192) {
					$bits = 2;
				} else {
					return false;
				}
				if (($i + $bits) > $len) return false;
				while ($bits > 1) {
					$i++;
					$b = ord($string[$i]);
					if ($b < 128 || $b > 191) return false;
					$bits--;
				}
			}
		}
		return true;
	}

	/**
	 * 產生隨機字串 //中文 需要php_mbstring擴展支持
	 *
	 * 默認長度6位 字母和數字混合 支持中文
	 * @param int $len 長度
	 * @param int $type 字串類型 0 字母 1 數字 其它 混合
	 * @param string $addChars 自定義一部分字符
	 *
	 * @return string
	 */
	public static function randString($len = 6, $type = 0, $addChars = '')
	{
		$string = '';
		switch ($type) {
			case 0:
				$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
				break;
			case 1:
				$chars = str_repeat('0123456789', 3);
				break;
			case 2:
				$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
				break;
			case 3:
				$chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
				break;
			case 4:
				$chars = "們以我到他會作時要動國產的一是工就年階義發成部民可出能方進在了不和有大這主中人上為來分生對於學下級地個用同行面說種過命度革而多子後自社加小機也經力線本電高量長黨得實家定深法表著水理化爭現所二起政三好十戰無農使性前等反體合鬥路圖把結第裡正新開論之物從當兩些還天資事隊批點育重其思與間內去因件日利相由壓員氣業代全組數果期導平各基或月毛然如應形想制心樣干都向變關問比展那它最及外沒看治提五解系林者米群頭意只明四道馬認次文通但條較克又公孔領軍流入接席位情運器並飛原油放立題質指建區驗活眾很教決特此常石強極土少已根共直團統式轉別造切九你取西持總料連任志觀調七麼山程百報更見必真保熱委手改管處己將修支識病象幾先老光專什六型具示復安帶每東增則完風回南廣勞輪科北打積車計給節做務被整聯步類集號列溫裝即毫知軸研單色堅據速防史拉世設達爾場織歷花受求傳口斷況采精金界品判參層止邊清至萬確究書術狀廠須離再目海交權且兒青才證低越際八試規斯近注辦布門鐵需走議縣兵固除般引齒千勝細影濟白格效置推空配刀葉率述今選養德話查差半敵始片施響收華覺備名紅續均藥標記難存測士身緊液派准斤角降維板許破述技消底床田勢端感往神便賀村構照容非搞亞磨族火段算適講按值美態黃易彪服早班麥削信排台聲該擊素張密害侯草何樹肥繼右屬市嚴徑螺檢左頁抗蘇顯苦英快稱壞移約巴材省黑武培著河帝僅針怎植京助升王眼她抓含苗副雜普談圍食射源例致酸舊卻充足短劃劑宣環落首尺波承粉踐府魚隨考刻靠夠滿夫失包住促枝局菌桿周護巖師舉曲春元超負砂封換太模貧減陽揚江析畝木言球朝醫校古呢稻宋聽唯輸滑站另衛字鼓剛寫劉微略范供阿塊某功套友限項余倒捲創律雨讓骨遠幫初皮播優占死毒圈偉季訓控激找叫雲互跟裂糧粒母練塞鋼頂策雙留誤礎吸阻故寸盾晚絲女散焊功株親院冷徹彈錯散商視藝滅版烈零室輕血倍缺厘泵察絕富城沖噴壤簡否柱李望盤磁雄似困鞏益洲脫投送奴側潤蓋揮距觸星松送獲興獨官混紀依未突架寬冬章濕偏紋吃執閥礦寨責熟穩奪硬價努翻奇甲預職評讀背協損棉侵灰雖矛厚羅泥辟告卵箱掌氧恩愛停曾溶營終綱孟錢待盡俄縮沙退陳討奮械載胞幼哪剝迫旋征槽倒握擔仍呀鮮吧卡粗介鑽逐弱腳怕鹽末陰豐霧冠丙街萊貝輻腸付吉滲瑞驚頓擠秒懸姆爛森糖聖凹陶詞遲蠶億矩康遵牧遭幅園腔訂香肉弟屋敏恢忘編印蜂急拿擴傷飛露核緣游振操央伍域甚迅輝異序免紙夜鄉久隸缸夾念蘭映溝乙嗎儒殺汽磷艱晶插埃燃歡鐵補咱芽永瓦傾陣碳演威附牙芽永瓦斜灌歐獻順豬洋腐請透司危括脈宜笑若尾束壯暴企菜穗楚漢愈綠拖牛份染既秋遍鍛玉夏療尖殖井費州訪吹榮銅沿替滾客召旱悟刺腦措貫藏敢令隙爐殼硫煤迎鑄粘探臨薄旬善福縱擇禮願伏殘雷延煙句純漸耕跑澤慢栽魯赤繁境潮橫掉錐希池敗船假亮謂托伙哲懷割擺貢呈勁財儀沉煉麻罪祖息車穿貨銷齊鼠抽畫飼龍庫守築房歌寒喜哥洗蝕廢納腹乎錄鏡婦惡脂莊擦險贊鍾搖典柄辯竹谷賣亂虛橋奧伯趕垂途額壁網截野遺靜謀弄掛課鎮妄盛耐援扎慮鍵歸符慶聚繞摩忙舞遇索顧膠羊湖釘仁音跡碎伸燈避泛亡答勇頻皇柳哈揭甘諾概憲濃島襲誰洪謝炮澆斑訊懂靈蛋閉孩釋乳巨徒私銀伊景坦累勻霉杜樂勒隔彎績招紹胡呼痛峰零柴簧午跳居尚丁秦稍追樑折耗鹼殊崗挖氏刃劇堆赫荷胸衡勤膜篇登駐案刊秧緩凸役剪川雪鏈漁啦臉戶洛孢勃盟買楊宗焦賽旗濾硅炭股坐蒸凝竟陷槍黎救冒暗洞犯筒您宋弧爆謬塗味津臂障褐陸啊健尊豆拔莫抵桑坡縫警挑污冰柬嘴啥飯塑寄趙喊墊丹渡耳刨虎筆稀昆浪薩茶滴淺擁穴覆倫娘噸浸袖珠雌媽紫戲塔錘震歲貌潔剖牢鋒疑霸閃埔猛訴刷狠忽災鬧喬唐漏聞沈熔氯荒莖男凡搶像漿旁玻亦忠唱蒙予紛捕鎖尤乘烏智淡允叛畜俘摸銹掃畢璃寶芯爺鑒秘淨蔣鈣肩騰枯拋軌堂拌爸循誘祝勵肯酒繩窮塘燥泡袋朗喂鋁軟渠顆慣貿糞綜牆趨彼屆墨礙啟逆卸航衣孫齡嶺騙休借" . $addChars;
				break;
			default :
				// 默認去掉了容易混淆的字符oOLl和數字01，要添加請使用addChars參數
				$chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
				break;
		}
		if ($len > 10) {//位數過長重複字符串一定次數
			$chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
		}
		if ($type != 4) {
			$chars = str_shuffle($chars);
			$string = substr($chars, 0, $len);
		} else {
			// 中文 需要php_mbstring擴展支持
			for ($i = 0; $i < $len; $i++) {
				$string .= self::substrCn($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1, 'utf-8', false);
			}
		}
		return $string;
	}

	/**
	 * 字符串截取，支持中文和其他編碼
	 *
	 * @param string $string 需要轉換的字符串
	 * @param int $start 開始位置
	 * @param int $length 截取長度
	 * @param string $charset 編碼格式
	 * @param string $suffix 截斷字符串後綴
	 *
	 * @return string
	 */
	public static function substrCn($string, $start = 0, $length, $charset = "utf-8", $suffix = '')
	{
		if (function_exists("mb_substr")) {
			return mb_substr($string, $start, $length, $charset) . $suffix;
		} elseif (function_exists('iconv_substr')) {
			return iconv_substr($string, $start, $length, $charset) . $suffix;
		}
		$re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		$re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		$re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
		preg_match_all($re[$charset], $string, $match);
		$slice = join("", array_slice($match[0], $start, $length));
		return $slice . $suffix;
	}
}
