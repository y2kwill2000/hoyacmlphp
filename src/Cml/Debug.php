<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 系統DEBUG調試類
 * *********************************************************** */

namespace Cml;

use Cml\Http\Request;
use \Cml\Interfaces\Debug as DebugInterfaces;

/**
 * Debug調試處理類,debug=true時負責調試相關信息的收集及ui的展示
 *
 * @package Cml
 */
class Debug implements DebugInterfaces
{
	/**
	 * info類型的提示信息
	 *
	 * @var int
	 */
	const TIP_INFO_TYPE_INFO = 0;//消息的類型為包含文件
	/**
	 * 包含文件的提示信息
	 *
	 * @var int
	 */
	const TIP_INFO_TYPE_INCLUDE_LIB = 1;//消息的類型為包含文件
	/**
	 * SQL語句調試信息
	 *
	 * @var int
	 */
	const TIP_INFO_TYPE_SQL = 2;//消息的類型為普通消息
	/**
	 * 包含文件的提示信息
	 *
	 * @var int
	 */
	const TIP_INFO_TYPE_INCLUDE_FILE = 3;//消息的類型為sql
	/**
	 * 正常的sql執行語句
	 *
	 * @var int
	 */
	const SQL_TYPE_NORMAL = 1;//程序運行結束時間
	/**
	 * 該SQL執行結果直接從緩存返回
	 *
	 * @var int
	 */
	const SQL_TYPE_FROM_CACHE = 2;//程序開始運行所用內存
	/**
	 * 執行過慢的sql
	 *
	 * @var int
	 */
	const SQL_TYPE_SLOW = 3;//程序結束運行時所用內存
	private static $includeFile = [];
	private static $includeLib = [];
	private static $tipInfo = [];
	private static $sql = [];
	private static $stopTime;
	private static $startMemory;
	private static $stopMemory;
	private static $tipInfoType = [
		E_WARNING => '運行時警告',
		E_NOTICE => '運行時提醒',
		E_STRICT => '編碼標準化警告',
		E_USER_ERROR => '自定義錯誤',
		E_USER_WARNING => '自定義警告',
		E_USER_NOTICE => '自定義提醒',
		E_DEPRECATED => '過時函數提醒',
		E_RECOVERABLE_ERROR => '可捕獲的致命錯誤',
		'Unknow' => '未知錯誤'
	];

	/**
	 * 在腳本開始處調用獲取腳本開始時間的微秒值\及內存的使用量
	 *
	 */
	public static function start()
	{
		// 記錄內存初始使用
		function_exists('memory_get_usage') && self::$startMemory = memory_get_usage();
	}

	/**
	 * 程序執行完畢,打印CmlPHP運行信息
	 *
	 */
	public static function stop()
	{
		self::$stopTime = microtime(true);
		// 記錄內存結束使用
		function_exists('memory_get_usage') && self::$stopMemory = memory_get_usage();

		Cml::getContainer()->make('cml_debug')->stopAndShowDebugInfo();

		Plugin::hook('cml.before_ob_end_flush');
		CML_OB_START && ob_end_flush();
	}

	/**
	 * 錯誤handler
	 *
	 * @param int $errorType 錯誤類型 分運行時警告、運行時提醒、自定義錯誤、自定義提醒、未知等
	 * @param string $errorTip 錯誤提示
	 * @param string $errorFile 發生錯誤的文件
	 * @param int $errorLine 錯誤所在行數
	 *
	 * @return void
	 */
	public static function catcher($errorType, $errorTip, $errorFile, $errorLine)
	{
		if (!isset(self::$tipInfoType[$errorType])) {
			$errorType = 'Unknow';
		}
		if ($errorType == E_NOTICE || $errorType == E_USER_NOTICE) {
			$color = '#000088';
		} else {
			$color = 'red';
		}
		$mess = "<span style='color:{$color}'>";
		$mess .= '<b>' . self::$tipInfoType[$errorType] . "</b>[在文件 {$errorFile} 中,第 {$errorLine} 行]:";
		$mess .= $errorTip;
		$mess .= '</span>';
		self::addTipInfo($mess);
	}

	/**
	 * 添加調試信息
	 *
	 * @param string $msg 調試消息字符串
	 * @param int $type 消息的類型
	 * @param string $color 是否要添加字體顏色
	 *
	 * @return void
	 */
	public static function addTipInfo($msg, $type = self::TIP_INFO_TYPE_INFO, $color = '')
	{
		if (Cml::$debug) {
			$color && $msg = "<span style='color:{$color}'>" . $msg . '</span>';
			switch ($type) {
				case self::TIP_INFO_TYPE_INFO:
					self::$tipInfo[] = $msg;
					break;
				case self::TIP_INFO_TYPE_INCLUDE_LIB:
					self::$includeLib[] = $msg;
					break;
				case self::TIP_INFO_TYPE_SQL:
					self::$sql[] = $msg;
					break;
				case self::TIP_INFO_TYPE_INCLUDE_FILE:
					self::$includeFile[] = str_replace('\\', '/', str_replace([Cml::getApplicationDir('secure_src'), CML_PATH], ['{secure_src}', '{cmlphp_src}'], $msg));
					break;
			}
		}
	}

	/**
	 * 添加一條sql查詢的調試信息
	 *
	 * @param $sql
	 * @param int $type sql類型 參考常量聲明SQL_TYPE_NORMAL、SQL_TYPE_FROM_CACHE、SQL_TYPE_SLOW
	 * @param int $other type = SQL_TYPE_SLOW時帶上執行時間
	 */
	public static function addSqlInfo($sql, $type = self::SQL_TYPE_NORMAL, $other = 0)
	{
		switch ($type) {
			case self::SQL_TYPE_FROM_CACHE:
				$sql .= "<span style='color:red;'>[from cache]</span>";
				break;
			case self::SQL_TYPE_SLOW:
				$sql .= "<span style='color:red;'>[slow sql, {$other}]</span>";
				break;
		}
		self::addTipInfo($sql, self::TIP_INFO_TYPE_SQL);
	}

	/**
	 * 顯示代碼片段
	 *
	 * @param string $file 文件路徑
	 * @param int $focus 出錯的行
	 * @param int $range 基於出錯行上下顯示多少行
	 * @param array $style 樣式
	 *
	 * @return string
	 */
	public static function codeSnippet($file, $focus, $range = 7, $style = ['lineHeight' => 20, 'fontSize' => 13])
	{
		$html = highlight_file($file, true);
		if (!$html) {
			return false;
		}
		// 分割html保存到數組
		$html = explode('<br />', $html);
		$lineNums = count($html);
		// 代碼的html
		$codeHtml = '';

		// 獲取相應範圍起止索引
		$start = ($focus - $range) < 1 ? 0 : ($focus - $range - 1);
		$end = (($focus + $range) > $lineNums ? $lineNums - 1 : ($focus + $range - 1));

		// 修正開始標籤
		// 有可能取到的片段缺少開始的span標籤，而它包含代碼著色的CSS屬性
		// 如果缺少，片段開始的代碼則沒有顏色了，所以需要把它找出來
		if (substr($html[$start], 0, 5) !== '<span') {
			while (($start - 1) >= 0) {
				$match = [];
				preg_match('/<span style="color: #([\w]+)"(.(?!<\/span>))+$/', $html[--$start], $match);
				if (!empty($match)) {
					$html[$start] = "<span style=\"color: #{$match[1]}\">" . $html[$start];
					break;
				}
			}
		}

		for ($line = $start; $line <= $end; $line++) {
			// 在行號前填充0
			$index_pad = str_pad($line + 1, strlen($end), 0, STR_PAD_LEFT);
			($line + 1) == $focus && $codeHtml .= "<p style='height: " . $style['lineHeight'] . "px; width: 100%; _width: 95%; background-color: red; opacity: 0.4; filter:alpha(opacity=40); font-size:15px; font-weight: bold;'>";
			$codeHtml .= "<span style='margin-right: 10px;line-height: " . $style['lineHeight'] . "px; color: #807E7E;'>{$index_pad}</span>{$html[$line]}";
			$codeHtml .= (($line + 1) == $focus ? '</p>' : ($line != $end ? '<br />' : ''));
		}

		// 修正結束標籤
		if (substr($codeHtml, -7) !== '</span>') {
			$codeHtml .= '</span>';
		}

		return <<<EOT
        <div style="position: relative; font-size: {$style['fontSize']}px; background-color: #BAD89A;">
            <div style="_width: 95%; line-height: {$style['lineHeight']}px; position: relative; z-index: 2; overflow: hidden; white-space:nowrap; text-overflow:ellipsis;">{$codeHtml}</div>
        </div>
EOT;
	}

	/**
	 * 輸出調試消息
	 *
	 * @return void
	 */
	public function stopAndShowDebugInfo()
	{
		if (Request::isAjax()) {
			$dump = [
				'sql' => self::$sql,
				'tipInfo' => self::$tipInfo
			];
			if (Config::get('dump_use_php_console')) {
				\Cml\dumpUsePHPConsole($dump, strip_tags($_SERVER['REQUEST_URI']));
			} else {
				Cml::requireFile(CML_CORE_PATH . DIRECTORY_SEPARATOR . 'ConsoleLog.php', ['deBugLogData' => $dump]);
			}
		} else {
			View::getEngine('html')
				->assign('includeLib', Debug::getIncludeLib())
				->assign('includeFile', Debug::getIncludeFiles())
				->assign('tipInfo', Debug::getTipInfo())
				->assign('sqls', Debug::getSqls())
				->assign('usetime', Debug::getUseTime())
				->assign('usememory', Debug::getUseMemory());
			Cml::showSystemTemplate(Config::get('debug_page'));
		}
	}

	/**
	 * 返回包含的類庫
	 *
	 * @return array
	 */
	public static function getIncludeLib()
	{
		return self::$includeLib;
	}

	/**
	 * 返回包含的文件
	 *
	 * @return array
	 */
	public static function getIncludeFiles()
	{
		return self::$includeFile;
	}

	/**
	 * 返回提示信息
	 *
	 * @return array
	 */
	public static function getTipInfo()
	{
		return self::$tipInfo;
	}

	/**
	 * 返回執行的sql語句
	 *
	 * @return array
	 */
	public static function getSqls()
	{
		return self::$sql;
	}

	/**
	 * 返回程序運行所消耗時間
	 *
	 * @return float
	 */
	public static function getUseTime()
	{
		return round((self::$stopTime - Cml::$nowMicroTime), 4);  //計算後以4捨5入保留4位返回
	}

	/**
	 * 返回程序運行所消耗的內存
	 *
	 * @return string
	 */
	public static function getUseMemory()
	{
		if (function_exists('memory_get_usage')) {
			return number_format((self::$stopMemory - self::$startMemory) / 1024, 2) . 'kb';
		} else {
			return '當前服務器環境不支持內存消耗統計';
		}
	}
}

/**
 * 修改自dbug官方網站 http://dbug.ospinto.com/
 *
 * 使用方法：
 * include_once("dBug.php");
 * new dBug($myVariable1);
 * new dBug($myVariable2); //建議每次都創建一個新實例
 * new dBug($arr);
 *
 * $test = new someClass('123');
 * new dBug($test);
 *
 * $result = mysql_query('select * from tblname');
 * new dBug($result);
 *
 * $xmlData = "./data.xml";
 * new dBug($xmlData, "xml");
 **/

/**
 * debug依賴的第三方庫
 *
 * @package Cml
 */
class dBug
{
	private $xmlCData;
	private $xmlSData;
	private $xmlDData;
	private $xmlCount = 0;
	private $xmlAttrib;
	private $xmlName;
	private $arrType = ["array", "object", "resource", "boolean", "NULL"];
	private $bInitialized = false;
	private $bCollapsed = false;
	private $arrHistory = [];

	/**
	 * 構造方法
	 *
	 * @param mixed $var 要打印的變量
	 * @param string $forceType
	 * @param bool $bCollapsed
	 */
	public function __construct($var, $forceType = "", $bCollapsed = false)
	{
		//include js and css scripts
		$this->initJSandCSS();
		$arrAccept = ["array", "object", "xml"]; //array of variable types that can be "forced"
		$this->bCollapsed = $bCollapsed;
		if (in_array($forceType, $arrAccept)) {
			$this->{"varIs" . ucfirst($forceType)}($var);
		} else {
			$this->checkType($var);
		}
	}

	//get variable name

	private function initJSandCSS()
	{
		echo <<<SCRIPTS
            <script language="JavaScript">
            /* code modified from ColdFusion's cfdump code */
                function dBug_toggleRow(source) {
                    var target = (document.all) ? source.parentElement.cells[1] : source.parentNode.lastChild;
                    dBug_toggleTarget(target,dBug_toggleSource(source));
                }

                function dBug_toggleSource(source) {
                    if (source.style.fontStyle=='italic') {
                        source.style.fontStyle='normal';
                        source.title='click to collapse';
                        return 'open';
                    } else {
                        source.style.fontStyle='italic';
                        source.title='click to expand';
                        return 'closed';
                    }
                }

                function dBug_toggleTarget(target,switchToState) {
                    target.style.display = (switchToState=='open') ? '' : 'none';
                }

                function dBug_toggleTable(source) {
                    var switchToState=dBug_toggleSource(source);
                    if (document.all) {
                        var table=source.parentElement.parentElement;
                        for (var i=1;i<table.rows.length;i++) {
                            target=table.rows[i];
                            dBug_toggleTarget(target,switchToState);
                        }
                    }
                    else {
                        var table=source.parentNode.parentNode;
                        for (var i=1;i<table.childNodes.length;i++) {
                            target=table.childNodes[i];
                            if (target.style) {
                                dBug_toggleTarget(target,switchToState);
                            }
                        }
                    }
                }
            </script>

            <style type="text/css">
                table.dBug_array,table.dBug_object,table.dBug_resource,table.dBug_resourceC,table.dBug_xml {
                    font-family:Verdana, Arial, Helvetica, sans-serif; color:#000000; font-size:12px;
                }

                .dBug_arrayHeader,
                .dBug_objectHeader,
                .dBug_resourceHeader,
                .dBug_resourceCHeader,
                .dBug_xmlHeader
                    { font-weight:bold; color:#FFFFFF; cursor:pointer; }

                .dBug_arrayKey,
                .dBug_objectKey,
                .dBug_xmlKey
                    { cursor:pointer; }

                /* array */
                table.dBug_array { background-color:#006600; }
                table.dBug_array td { background-color:#FFFFFF; }
                table.dBug_array td.dBug_arrayHeader { background-color:#009900; }
                table.dBug_array td.dBug_arrayKey { background-color:#CCFFCC; }

                /* object */
                table.dBug_object { background-color:#0000CC; }
                table.dBug_object td { background-color:#FFFFFF; }
                table.dBug_object td.dBug_objectHeader { background-color:#4444CC; }
                table.dBug_object td.dBug_objectKey { background-color:#CCDDFF; }

                /* resource */
                table.dBug_resourceC { background-color:#884488; }
                table.dBug_resourceC td { background-color:#FFFFFF; }
                table.dBug_resourceC td.dBug_resourceCHeader { background-color:#AA66AA; }
                table.dBug_resourceC td.dBug_resourceCKey { background-color:#FFDDFF; }

                /* resource */
                table.dBug_resource { background-color:#884488; }
                table.dBug_resource td { background-color:#FFFFFF; }
                table.dBug_resource td.dBug_resourceHeader { background-color:#AA66AA; }
                table.dBug_resource td.dBug_resourceKey { background-color:#FFDDFF; }

                /* xml */
                table.dBug_xml { background-color:#888888; }
                table.dBug_xml td { background-color:#FFFFFF; }
                table.dBug_xml td.dBug_xmlHeader { background-color:#AAAAAA; }
                table.dBug_xml td.dBug_xmlKey { background-color:#DDDDDD; }
            </style>
SCRIPTS;
	}

	//create the main table header

	private function checkType($var)
	{
		switch (gettype($var)) {
			case "resource":
				$this->varIsResource($var);
				break;
			case "object":
				$this->varIsObject($var);
				break;
			case "array":
				$this->varIsArray($var);
				break;
			case "NULL":
				$this->varIsNULL();
				break;
			case "boolean":
				$this->varIsBoolean($var);
				break;
			default:
				$var = ($var == "") ? "[empty string]" : $var;
				echo "<table cellspacing=0><tr>\n<td>" . $var . "</td>\n</tr>\n</table>\n";
				break;
		}
	}

	//create the table row header

	private function varIsResource($var)
	{
		$this->makeTableHeader("resourceC", "resource", 1);
		echo "<tr>\n<td>\n";
		switch (get_resource_type($var)) {
			case "fbsql result":
			case "mssql result":
			case "msql query":
			case "pgsql result":
			case "sybase-db result":
			case "sybase-ct result":
			case "mysql result":
				$db = current(explode(" ", get_resource_type($var)));
				$this->varIsDBResource($var, $db);
				break;
			case "gd":
				$this->varIsGDResource($var);
				break;
			case "xml":
				$this->varIsXmlResource($var);
				break;
			default:
				echo get_resource_type($var) . $this->closeTDRow();
				break;
		}
		echo $this->closeTDRow() . "</table>\n";
	}

	//close table row

	private function makeTableHeader($type, $header, $colspan = 2)
	{
		if (!$this->bInitialized) {
			$header = $this->getVariableName() . " (" . $header . ")";
			$this->bInitialized = true;
		}
		$str_i = ($this->bCollapsed) ? "style=\"font-style:italic\" " : "";

		echo "<table cellspacing=2 cellpadding=3 class=\"dBug_" . $type . "\">
                <tr>
                    <td " . $str_i . "class=\"dBug_" . $type . "Header\" colspan=" . $colspan . " onClick='dBug_toggleTable(this)'>" . $header . "</td>
                </tr>";
	}

	//error

	private function getVariableName()
	{
		$arrBacktrace = debug_backtrace();

		//possible 'included' functions
		$arrInclude = ["include", "include_once", "require", "require_once"];

		//check for any included/required files. if found, get array of the last included file (they contain the right line numbers)
		for ($i = count($arrBacktrace) - 1; $i >= 0; $i--) {
			$arrCurrent = $arrBacktrace[$i];
			if (
				array_key_exists("function", $arrCurrent)
				&& (
					in_array($arrCurrent["function"], $arrInclude) || (0 != strcasecmp($arrCurrent["function"], "dbug"))
				)
			) {
				continue;
			}
			$arrFile = $arrCurrent;
			break;
		}

		if (isset($arrFile)) {
			$arrLines = file($arrFile["file"]);
			$code = $arrLines[($arrFile["line"] - 1)];

			//find call to dBug class
			preg_match('/\bnew dBug\s*\(\s*(.+)\s*\);/i', $code, $arrMatches);

			return $arrMatches[1];
		}
		return "";
	}

	//check variable type

	private function varIsDBResource($var, $db = "mysql")
	{
		if ($db == "pgsql") {
			$db = "pg";
		}
		if ($db == "sybase-db" || $db == "sybase-ct") {
			$db = "sybase";
		}
		$arrFields = ["name", "type", "flags"];
		$numrows = call_user_func($db . "_num_rows", $var);
		$numfields = call_user_func($db . "_num_fields", $var);
		$this->makeTableHeader("resource", $db . " result", $numfields + 1);
		echo "<tr><td class=\"dBug_resourceKey\">&nbsp;</td>";
		$field = [];
		for ($i = 0; $i < $numfields; $i++) {
			$field_header = $field_name = "";
			for ($j = 0; $j < count($arrFields); $j++) {
				$db_func = $db . "_field_" . $arrFields[$j];
				if (function_exists($db_func)) {
					$fheader = call_user_func($db_func, $var, $i) . " ";
					if ($j == 0) {
						$field_name = $fheader;
					} else {
						$field_header .= $fheader;
					}
				}
			}
			$field[$i] = call_user_func($db . "_fetch_field", $var, $i);
			echo "<td class=\"dBug_resourceKey\" title=\"" . $field_header . "\">" . $field_name . "</td>";
		}
		echo "</tr>";
		for ($i = 0; $i < $numrows; $i++) {
			$row = call_user_func($db . "_fetch_array", $var, constant(strtoupper($db) . "_ASSOC"));
			echo "<tr>\n";
			echo "<td class=\"dBug_resourceKey\">" . ($i + 1) . "</td>";
			for ($k = 0; $k < $numfields; $k++) {
				$fieldrow = $row[($field[$k]->name)];
				$fieldrow = ($fieldrow == "") ? "[empty string]" : $fieldrow;
				echo "<td>" . $fieldrow . "</td>\n";
			}
			echo "</tr>\n";
		}
		echo "</table>";
		if ($numrows > 0) {
			call_user_func($db . "_data_seek", $var, 0);
		}
	}

	//if variable is a NULL type

	private function varIsGDResource($var)
	{
		$this->makeTableHeader("resource", "gd", 2);
		$this->makeTDHeader("resource", "Width");
		echo imagesx($var) . $this->closeTDRow();
		$this->makeTDHeader("resource", "Height");
		echo imagesy($var) . $this->closeTDRow();
		$this->makeTDHeader("resource", "Colors");
		echo imagecolorstotal($var) . $this->closeTDRow();
		echo "</table>";
	}

	//if variable is a boolean type

	private function makeTDHeader($type, $header)
	{
		$str_d = ($this->bCollapsed) ? " style=\"display:none\"" : "";
		echo "<tr" . $str_d . ">
                <td valign=\"top\" onClick='dBug_toggleRow(this)' class=\"dBug_" . $type . "Key\">" . $header . "</td>
                <td>";
	}

	//if variable is an array type

	private function closeTDRow()
	{
		return "</td></tr>\n";
	}

	//if variable is an object type

	private function varIsXmlResource($var)
	{
		$xml_parser = xml_parser_create();
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
		xml_set_element_handler($xml_parser, [&$this, "xmlStartElement"], [&$this, "xmlEndElement"]);
		xml_set_character_data_handler($xml_parser, [&$this, "xmlCharacterData"]);
		xml_set_default_handler($xml_parser, [&$this, "xmlDefaultHandler"]);

		$this->makeTableHeader("xml", "xml document", 2);
		$this->makeTDHeader("xml", "xmlRoot");

		//attempt to open xml file
		$bFile = (!($fp = @fopen($var, "r"))) ? false : true;

		//read xml file
		if ($bFile) {
			while ($data = str_replace("\n", "", fread($fp, 4096))) {
				$this->xmlParse($xml_parser, $data, feof($fp));
			}
		} //if xml is not a file, attempt to read it as a string
		else {
			if (!is_string($var)) {
				echo $this->error("xml") . $this->closeTDRow() . "</table>\n";
				return;
			}
			$data = $var;
			$this->xmlParse($xml_parser, $data, 1);
		}

		echo $this->closeTDRow() . "</table>\n";

	}

	//if variable is a resource type

	private function xmlParse($xml_parser, $data, $bFinal)
	{
		if (!xml_parse($xml_parser, $data, $bFinal)) {
			die(sprintf("XML error: %s at line %d\n",
				xml_error_string(xml_get_error_code($xml_parser)),
				xml_get_current_line_number($xml_parser)));
		}
	}

	//if variable is a database resource type

	private function error($type)
	{
		$error = "Error: Variable cannot be a";
		// this just checks if the type starts with a vowel or "x" and displays either "a" or "an"
		if (
		in_array(substr($type, 0, 1), ["a", "e", "i", "o", "u", "x"])
		) {
			$error .= "n";
		}
		return ($error . " " . $type . " type");
	}

	//if variable is an image/gd resource type

	private function varIsObject($var)
	{
		$var_ser = serialize($var);
		array_push($this->arrHistory, $var_ser);
		$this->makeTableHeader("object", "object");

		if (is_object($var)) {
			$arrObjVars = get_object_vars($var);
			foreach ($arrObjVars as $key => $value) {

				$value = (!is_object($value) && !is_array($value) && trim($value) == "") ? "[empty string]" : $value;
				$this->makeTDHeader("object", $key);

				//check for recursion
				if (is_object($value) || is_array($value)) {
					$var_ser = serialize($value);
					if (in_array($var_ser, $this->arrHistory, TRUE)) {
						$value = (is_object($value)) ? "*RECURSION* -> $" . get_class($value) : "*RECURSION*";

					}
				}
				if (in_array(gettype($value), $this->arrType)) {
					$this->checkType($value);
				} else {
					echo $value;
				}
				echo $this->closeTDRow();
			}
			$arrObjMethods = get_class_methods(get_class($var));
			foreach ($arrObjMethods as $key => $value) {
				$this->makeTDHeader("object", $value);
				echo "[function]" . $this->closeTDRow();
			}
		} else {
			echo "<tr><td>" . $this->error("object") . $this->closeTDRow();
		}
		array_pop($this->arrHistory);
		echo "</table>";
	}

	//if variable is an xml type

	private function varIsArray($var)
	{
		$var_ser = serialize($var);
		array_push($this->arrHistory, $var_ser);

		$this->makeTableHeader("array", "array");
		if (is_array($var)) {
			foreach ($var as $key => $value) {
				$this->makeTDHeader("array", $key);

				//check for recursion
				if (is_array($value)) {
					$var_ser = serialize($value);
					if (in_array($var_ser, $this->arrHistory, true))
						$value = "*RECURSION*";
				}

				if (in_array(gettype($value), $this->arrType)) {
					$this->checkType($value);
				} else {
					$value = (trim($value) == "") ? "[empty string]" : $value;
					echo $value;
				}
				echo $this->closeTDRow();
			}
		} else {
			echo "<tr><td>" . $this->error("array") . $this->closeTDRow();
		}
		array_pop($this->arrHistory);
		echo "</table>";
	}

	//if variable is an xml resource type

	private function varIsNULL()
	{
		echo "NULL";
	}

	//parse xml

	private function varIsBoolean($var)
	{
		$var = ($var == 1) ? "true" : "false";
		echo $var;
	}

	//xml: inititiated when a start tag is encountered

	private function varIsXml($var)
	{
		$this->varIsXmlResource($var);
	}

	//xml: initiated when an end tag is encountered

	private function xmlStartElement($parser, $name, $attribs)
	{
		$this->xmlAttrib[$this->xmlCount] = $attribs;
		$this->xmlName[$this->xmlCount] = $name;
		$this->xmlSData[$this->xmlCount] = '$this->makeTableHeader("xml","xml element",2);';
		$this->xmlSData[$this->xmlCount] .= '$this->makeTDHeader("xml","xmlName");';
		$this->xmlSData[$this->xmlCount] .= 'echo "<strong>' . $this->xmlName[$this->xmlCount] . '</strong>".$this->closeTDRow();';
		$this->xmlSData[$this->xmlCount] .= '$this->makeTDHeader("xml","xmlAttributes");';
		if (count($attribs) > 0) {
			$this->xmlSData[$this->xmlCount] .= '$this->varIsArray($this->xmlAttrib[' . $this->xmlCount . ']);';
		} else {
			$this->xmlSData[$this->xmlCount] .= 'echo "&nbsp;";';
		}
		$this->xmlSData[$this->xmlCount] .= 'echo $this->closeTDRow();';
		$this->xmlCount++;
	}

	//xml: initiated when text between tags is encountered

	private function xmlEndElement($parser, $name)
	{
		for ($i = 0; $i < $this->xmlCount; $i++) {
			eval($this->xmlSData[$i]);
			$this->makeTDHeader("xml", "xmlText");
			echo (!empty($this->xmlCData[$i])) ? $this->xmlCData[$i] : "&nbsp;";
			echo $this->closeTDRow();
			$this->makeTDHeader("xml", "xmlComment");
			echo (!empty($this->xmlDData[$i])) ? $this->xmlDData[$i] : "&nbsp;";
			echo $this->closeTDRow();
			$this->makeTDHeader("xml", "xmlChildren");
			unset($this->xmlCData[$i], $this->xmlDData[$i]);
		}
		echo $this->closeTDRow();
		echo "</table>";
		$this->xmlCount = 0;
	}

	//xml: initiated when a comment or other miscellaneous texts is encountered

	private function xmlCharacterData($parser, $data)
	{
		$count = $this->xmlCount - 1;
		if (!empty($this->xmlCData[$count])) {
			$this->xmlCData[$count] .= $data;
		} else {
			$this->xmlCData[$count] = $data;
		}
	}

	private function xmlDefaultHandler($parser, $data)
	{
		//strip '<!--' and '-->' off comments
		$data = str_replace(["&lt;!--", "--&gt;"], "", htmlspecialchars($data));
		$count = $this->xmlCount - 1;
		if (!empty($this->xmlDData[$count])) {
			$this->xmlDData[$count] .= $data;
		} else {
			$this->xmlDData[$count] = $data;
		}
	}
}
