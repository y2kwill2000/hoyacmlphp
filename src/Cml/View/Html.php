<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 視圖 html渲染引擎
 * *********************************************************** */

namespace Cml\View;

use Cml\Cml;
use Cml\Config;
use Cml\Exception\FileCanNotReadableException;
use Cml\Exception\MkdirErrorException;
use Cml\Lang;
use Cml\Secure;

/**
 * 視圖 html渲染引擎
 *
 * @package Cml\View
 */
class Html extends Base
{
	/**
	 * 模板參數信息
	 *
	 * @var array
	 */
	private $options = [];

	/**
	 * 子模板block內容數組
	 *
	 * @var array
	 */
	private $layoutBlockData = [];

	/**
	 * 模板佈局文件
	 *
	 * @var null
	 */
	private $layout = null;

	/**
	 * 要替換的標籤
	 *
	 * @var array
	 */
	private $pattern = [];

	/**
	 * 替換後的內容
	 *
	 * @var array
	 */
	private $replacement = [];

	/**
	 * 構造方法
	 *
	 */
	public function __construct()
	{
		$this->options = [
			'templateDir' => 'templates' . DIRECTORY_SEPARATOR, //模板文件所在目錄
			'cacheDir' => 'templates' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR, //緩存文件存放目錄
			'autoUpdate' => true, //當模板文件改動時是否重新生成緩存
			'leftDelimiter' => preg_quote(Config::get('html_left_deper')),
			'rightDelimiter' => preg_quote(Config::get('html_right_deper'))
		];

		//要替換的標籤
		$this->pattern = [
			'#\<\?(=|php)(.+?)\?\>#s', //替換php標籤
			'#' . $this->options['leftDelimiter'] . "(\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?\\[\S+?\\]\\[\S+?\\]|\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?\\[\S+?\\]|\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?);?" . $this->options['rightDelimiter'] . '#', //替換變量 $a['name']這種一維數組以及$a['name']['name']這種二維數組
			'#' . $this->options['leftDelimiter'] . "(\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?)\\.([a-zA-Z0-9_\x7f-\xff]+);?" . $this->options['rightDelimiter'] . '#', //替換$a.key這種一維數組
			'#' . $this->options['leftDelimiter'] . "(\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?)\\.([a-zA-Z0-9_\x7f-\xff]+)\\.([a-zA-Z0-9_\x7f-\xff]+);?" . $this->options['rightDelimiter'] . '#', //替換$a.key.key這種二維數組

			//htmlspecialchars
			'#' . $this->options['leftDelimiter'] . "\\+(\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?\\[\S+?\\]\\[\S+?\\]|\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?\\[\S+?\\]|\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?);?" . $this->options['rightDelimiter'] . '#', //替換變量 $a['name']這種一維數組以及$a['name']['name']這種二維數組
			'#' . $this->options['leftDelimiter'] . "\\+(\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?)\\.([a-zA-Z0-9_\x7f-\xff]+);?" . $this->options['rightDelimiter'] . '#', //替換$a.key這種一維數組
			'#' . $this->options['leftDelimiter'] . "\\+(\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?)\\.([a-zA-Z0-9_\x7f-\xff]+)\\.([a-zA-Z0-9_\x7f-\xff]+);?" . $this->options['rightDelimiter'] . '#', //替換$a.key.key這種二維數組
			'#' . $this->options['leftDelimiter'] . '\\+echo\s+(.+?)' . $this->options['rightDelimiter'] . '#s', //替換 echo

			'#' . $this->options['leftDelimiter'] . 'template\s+([a-z0-9A-Z_\.\/]+);?' . $this->options['rightDelimiter'] . '[\n\r\t]*#',//替換模板載入命令
			'#' . $this->options['leftDelimiter'] . 'eval\s+(.+?)' . $this->options['rightDelimiter'] . '#s',//替換eval
			'#' . $this->options['leftDelimiter'] . 'echo\s+(.+?)' . $this->options['rightDelimiter'] . '#s', //替換 echo
			'#' . $this->options['leftDelimiter'] . 'if\s+(.+?)' . $this->options['rightDelimiter'] . '#s',//替換if
			'#' . $this->options['leftDelimiter'] . '(elseif|elseif)\s+(.+?)' . $this->options['rightDelimiter'] . '#s', //替換 elseif
			'#' . $this->options['leftDelimiter'] . 'else' . $this->options['rightDelimiter'] . '#', //替換 else
			'#' . $this->options['leftDelimiter'] . '\/if' . $this->options['rightDelimiter'] . '#',//替換 /if
			'#' . $this->options['leftDelimiter'] . '(loop|foreach)\s+(\S+)\s+(\S+)\s*?' . $this->options['rightDelimiter'] . '#s',//替換loop|foreach
			'#' . $this->options['leftDelimiter'] . '(loop|foreach)\s+(\S+)\s+(\S+)\s+(\S+)\s*?' . $this->options['rightDelimiter'] . '#s',//替換loop|foreach
			'#' . $this->options['leftDelimiter'] . '\/(loop|foreach)' . $this->options['rightDelimiter'] . '#',//替換 /foreach|/loop
			'#' . $this->options['leftDelimiter'] . 'hook\s+(\w+?)\s*' . $this->options['rightDelimiter'] . '#i',//替換 hook
			'#' . $this->options['leftDelimiter'] . '(get|post|request)\s+(\w+?)\s*' . $this->options['rightDelimiter'] . '#i',//替換 get/post/request
			'#' . $this->options['leftDelimiter'] . 'lang\s+([A-Za-z0-9_\.\s*]+)(.*?)' . $this->options['rightDelimiter'] . '#i',//替換 lang
			'#' . $this->options['leftDelimiter'] . 'config\s+([A-Za-z0-9_\.]+)\s*' . $this->options['rightDelimiter'] . '#i',//替換 config
			'#' . $this->options['leftDelimiter'] . 'url\s+(.*?)\s*' . $this->options['rightDelimiter'] . '#i',//替換 url
			'#' . $this->options['leftDelimiter'] . 'public' . $this->options['rightDelimiter'] . '#i',//替換 {{public}}
			'#' . $this->options['leftDelimiter'] . 'self' . $this->options['rightDelimiter'] . '#i',//替換 {{self}}
			'#' . $this->options['leftDelimiter'] . 'token' . $this->options['rightDelimiter'] . '#i',//替換 {{token}}
			'#' . $this->options['leftDelimiter'] . 'controller' . $this->options['rightDelimiter'] . '#i',//替換 {{controller}}
			'#' . $this->options['leftDelimiter'] . 'action' . $this->options['rightDelimiter'] . '#i',//替換 {{action}}
			'#' . $this->options['leftDelimiter'] . 'urldeper' . $this->options['rightDelimiter'] . '#i',//替換 {{urldeper}}
			'#' . $this->options['leftDelimiter'] . ' \\?\\>[\n\r]*\\<\\?' . $this->options['rightDelimiter'] . '#', //刪除 PHP 代碼斷間多餘的空格及換行
			'#(href\s*?=\s*?"\s*?"|href\s*?=\s*?\'\s*?\')#',
			'#(src\s*?=\s*?"\s*?"|src\s*?=\s*?\'\s*?\')#',
			'#' . $this->options['leftDelimiter'] . 'assert\s+(.+?)\s*' . $this->options['rightDelimiter'] . '#i',//替換 assert
			'#' . $this->options['leftDelimiter'] . 'comment\s+(.+?)\s*' . $this->options['rightDelimiter'] . '#i',//替換 comment 模板註釋
			'#' . $this->options['leftDelimiter'] . 'acl\s+(.+?)\s*' . $this->options['rightDelimiter'] . '#i',//替換 acl權限判斷標識
			'#' . $this->options['leftDelimiter'] . '\/acl' . $this->options['rightDelimiter'] . '#i',//替換 /acl
			'#' . $this->options['leftDelimiter'] . 'datetime\s+(\S+?)\s*?\|(.*?)' . $this->options['rightDelimiter'] . '#i',//替換 /datetime
		];

		//替換後的內容
		$this->replacement = [
			'&lt;?${1}${2}?&gt',
			'<?php echo ${1};?>',
			'<?php echo ${1}[\'${2}\'];?>',
			'<?php echo ${1}[\'${2}\'][\'${3}\'];?>',

			//htmlspecialchars
			'<?php echo htmlspecialchars(${1});?>',
			'<?php echo htmlspecialchars(${1}[\'${2}\']);?>',
			'<?php echo htmlspecialchars(${1}[\'${2}\'][\'${3}\']);?>',
			'<?php echo htmlspecialchars(${1});?>',

			'<?php require(\Cml\View::getEngine()->getFile(\'${1}\', 1)); ?>',
			'<?php ${1};?>',
			'<?php echo ${1};?>',
			'<?php if (${1}) { ?>',
			'<?php } elseif (${2}) { ?>',
			'<?php } else { ?>',
			'<?php } ?>',
			'<?php if (is_array(${2})) { foreach (${2} as ${3}) { ?>',
			'<?php if (is_array(${2})) { foreach (${2} as ${3} => ${4}) { ?>',
			'<?php } } ?>',
			'<?php \Cml\Plugin::hook("${1}");?>',
			'<?php echo \Cml\Http\Input::${1}String("${2}");?>',
			'<?php echo \Cml\Lang::get("${1}"${2});?>',
			'<?php echo \Cml\Config::get("${1}");?>',
			'<?php \Cml\Http\Response::url(${1});?>',
			'<?php echo \Cml\Config::get("static__path", \Cml\Cml::getContainer()->make("cml_route")->getSubDirName());?>',//替換 {{public}}
			'<?php echo strip_tags($_SERVER["REQUEST_URI"]); ?>',//替換 {{self}}
			'<input type="hidden" name="CML_TOKEN" value="<?php echo \Cml\Secure::getToken();?>" />',//替換 {{token}}
			'<?php echo \Cml\Cml::getContainer()->make("cml_route")->getControllerName(); ?>',//替換 {{controller}}
			'<?php echo \Cml\Cml::getContainer()->make("cml_route")->getActionName(); ?>',//替換 {{action}}
			'<?php echo \Cml\Config::get("url_model") == 3 ? "&" : "?"; ?>',//替換 {{urldeper}}
			'',
			'href="javascript:void(0);"',
			'src="javascript:void(0);"',
			'<?php echo \Cml\Tools\StaticResource::parseResourceUrl("${1}");?>',//靜態資源
			'',
			'<?php if (\Cml\Vendor\Acl::checkAcl("${1}")) { ?>',//替換 acl權限判斷標識
			'<?php } ?>',// /acl
			'<?php echo date(trim("${2}"), ${1}); ?>',// /datetime
		];
	}

	/**
	 * 添加一個模板替換規則
	 *
	 * @param string $pattern 正則
	 * @param string $replacement 替換成xx內容
	 * @param bool $haveDelimiter $pattern的內容是否要帶上左右定界符
	 *
	 * @return $this
	 */
	public function addRule($pattern, $replacement, $haveDelimiter = true)
	{
		if ($pattern && $replacement) {
			$this->pattern = $haveDelimiter ? '#' . $this->options['leftDelimiter'] . $pattern . $this->options['rightDelimiter'] . '#s' : "#{$pattern}#s";
			$this->replacement = $replacement;
		}
		return $this;
	}

	/**
	 * 使用佈局模板並渲染
	 *
	 * @param string $templateFile 模板文件
	 * @param string $layout 佈局文件
	 * @param bool|false $layoutInOtherApp 佈局文件是否在其它應用
	 * @param bool|false $tplInOtherApp 模板是否在其它應用
	 */
	public function displayWithLayout($templateFile = '', $layout = 'master', $layoutInOtherApp = false, $tplInOtherApp = false)
	{
		$this->layout = Cml::getApplicationDir('apps_path') . DIRECTORY_SEPARATOR
			. ($layoutInOtherApp ? $layoutInOtherApp : Cml::getContainer()->make('cml_route')->getAppName())
			. DIRECTORY_SEPARATOR . Cml::getApplicationDir('app_view_path_name') . DIRECTORY_SEPARATOR
			. (Config::get('html_theme') != '' ? Config::get('html_theme') . DIRECTORY_SEPARATOR : '')
			. 'layout' . DIRECTORY_SEPARATOR . $layout . Config::get('html_template_suffix');
		$this->display($templateFile, $tplInOtherApp);
	}

	/**
	 * 模板顯示 調用內置的模板引擎顯示方法，
	 *
	 * @param string $templateFile 指定要調用的模板文件 默認為空 由系統自動定位模板文件
	 * @param bool $inOtherApp 是否為載入其它應用的模板
	 *
	 * @return void
	 */
	public function display($templateFile = '', $inOtherApp = false)
	{
		// 網頁字符編碼
		header('Content-Type:text/html; charset=' . Config::get('default_charset'));
		echo $this->fetch($templateFile, $inOtherApp);
		Cml::cmlStop();
	}

	/**
	 * 渲染模板獲取內容 調用內置的模板引擎顯示方法，
	 *
	 * @param string $templateFile 指定要調用的模板文件 默認為空 由系統自動定位模板文件
	 * @param bool $inOtherApp 是否為載入其它應用的模板
	 * @param bool $doNotSetDir 不自動根據當前請求設置目錄模板目錄。用於特殊模板顯示
	 * @param bool $donNotWriteCacheFileImmediateReturn 不要使用模板緩存，實時渲染(系統模板使用)
	 *
	 * @return string
	 */
	public function fetch($templateFile = '', $inOtherApp = false, $doNotSetDir = false, $donNotWriteCacheFileImmediateReturn = false)
	{
		if (Config::get('form_token')) {
			Secure::setToken();
		}

		ob_start();
		if ($donNotWriteCacheFileImmediateReturn) {
			$tplFile = $this->getTplFile($doNotSetDir ? $templateFile : $this->initBaseDir($templateFile, $inOtherApp));
			if (!is_readable($tplFile)) {
				throw new FileCanNotReadableException(Lang::get('_TEMPLATE_FILE_NOT_FOUND_', $tplFile));
			}
			empty($this->args) || extract($this->args, EXTR_PREFIX_SAME, "xxx");
			$return = $this->compile($tplFile, false, 0);
			eval('?>' . $return . '<?php ');
		} else {
			Cml::requireFile($this->getFile(($doNotSetDir ? $templateFile : $this->initBaseDir($templateFile, $inOtherApp))), $this->args);
		}

		$this->args = [];
		$this->reset();
		return ob_get_clean();
	}

	/**
	 * 獲取模板文件緩存
	 *
	 * @param string $file 模板文件名稱
	 * @param int $type 緩存類型0當前操作的模板的緩存 1包含的模板的緩存
	 *
	 * @return string
	 */
	public function getFile($file, $type = 0)
	{
		$type == 1 && $file = $this->initBaseDir($file);//初始化路徑
		//$file = str_replace([('/', '\\'], DIRECTORY_SEPARATOR, $file);
		$cacheFile = $this->getCacheFile($file);
		if ($this->options['autoUpdate']) {
			$tplFile = $this->getTplFile($file);
			if (!is_readable($tplFile)) {
				throw new FileCanNotReadableException(Lang::get('_TEMPLATE_FILE_NOT_FOUND_', $tplFile));
			}
			if (!is_file($cacheFile)) {
				if ($type !== 1 && !is_null($this->layout)) {
					if (!is_readable($this->layout)) {
						throw new FileCanNotReadableException(Lang::get('_TEMPLATE_FILE_NOT_FOUND_', $this->layout));
					}
				}
				$this->compile($tplFile, $cacheFile, $type);
				return $cacheFile;
			}

			$compile = false;
			$tplMtime = filemtime($tplFile);
			$cacheMtime = filemtime($cacheFile);
			if ($cacheMtime && $tplMtime) {
				($cacheMtime < $tplMtime) && $compile = true;
			} else {//獲取mtime失敗
				$compile = true;
			}

			if ($compile && $type !== 1 && !is_null($this->layout)) {
				if (!is_readable($this->layout)) {
					throw new FileCanNotReadableException(Lang::get('_TEMPLATE_FILE_NOT_FOUND_', $this->layout));
				}
			}

			//當子模板未修改時判斷佈局模板是否修改
			if (!$compile && $type !== 1 && !is_null($this->layout)) {
				if (!is_readable($this->layout)) {
					throw new FileCanNotReadableException(Lang::get('_TEMPLATE_FILE_NOT_FOUND_', $this->layout));
				}
				$layoutMTime = filemtime($this->layout);
				if ($layoutMTime) {
					$cacheMtime < $layoutMTime && $compile = true;
				} else {
					$compile = true;
				}
			}

			$compile && $this->compile($tplFile, $cacheFile, $type);
		}
		return $cacheFile;
	}

	/**
	 * 初始化目錄
	 *
	 * @param string $templateFile 模板文件名
	 * @param bool|false $inOtherApp 是否在其它app
	 *
	 * @return string
	 */
	private function initBaseDir($templateFile, $inOtherApp = false)
	{
		$baseDir = $inOtherApp ? $inOtherApp : Cml::getContainer()->make('cml_route')->getAppName();
		$baseDir && $baseDir .= '/';
		$baseDir .= Cml::getApplicationDir('app_view_path_name') . (Config::get('html_theme') != '' ? DIRECTORY_SEPARATOR . Config::get('html_theme') : '');

		if ($templateFile === '') {
			$baseDir .= '/' . Cml::getContainer()->make('cml_route')->getControllerName() . '/';
			$file = Cml::getContainer()->make('cml_route')->getActionName();
		} else {
			$templateFile = str_replace('.', '/', $templateFile);
			$baseDir .= DIRECTORY_SEPARATOR . dirname($templateFile) . DIRECTORY_SEPARATOR;
			$file = basename($templateFile);
		}

		$options = [
			'templateDir' => Cml::getApplicationDir('apps_path') . DIRECTORY_SEPARATOR . $baseDir, //指定模板文件存放目錄
			'cacheDir' => Cml::getApplicationDir('runtime_cache_path') . DIRECTORY_SEPARATOR . $baseDir, //指定緩存文件存放目錄
			'autoUpdate' => true, //當模板修改時自動更新緩存
		];

		$this->setHtmlEngineOptions($options);
		return $file;
	}

	/**
	 * 設定模板配置參數
	 *
	 * @param string | array $name 參數名稱
	 * @param mixed $value 參數值
	 *
	 * @return $this
	 */
	public function setHtmlEngineOptions($name, $value = '')
	{
		if (is_array($name)) {
			$this->options = array_merge($this->options, $name);
		} else {
			$this->options[$name] = $value;
		}
		return $this;
	}

	/**
	 * 獲取模板緩存文件名及路徑
	 *
	 * @param string $file 模板文件名稱
	 *
	 * @return string
	 */
	private function getCacheFile($file)
	{
		return $this->options['cacheDir'] . $file . '.cache.php';
	}

	/**
	 * 獲取模板文件名及路徑
	 *
	 * @param string $file 模板文件名稱
	 *
	 * @return string
	 */
	private function getTplFile($file)
	{
		return $this->options['templateDir'] . $file . Config::get('html_template_suffix');
	}

	/**
	 * 對模板文件進行緩存
	 *
	 * @param string $tplFile 模板文件名
	 * @param string $cacheFile 模板緩存文件名
	 * @param int $type 緩存類型0當前操作的模板的緩存 1包含的模板的緩存
	 *
	 * @return mixed
	 */
	private function compile($tplFile, $cacheFile, $type)
	{
		//取得模板內容
		//$template = file_get_contents($tplFile);
		$template = $this->getTplContent($tplFile, $type);

		//執行替換
		$template = preg_replace($this->pattern, $this->replacement, $template);

		if (!Cml::$debug) {
			/* 去除html空格與換行 */
			$find = ['~>\s+<~', '~>(\s+\n|\r)~'];
			$replace = ['><', '>'];
			$template = preg_replace($find, $replace, $template);
			$template = str_replace('?><?php', '', $template);
		}

		//添加 頭信息
		$template = '<?php if (!class_exists(\'\Cml\View\')) die(\'Access Denied\');?>' . $template;

		if (!$cacheFile) {
			return $template;
		}

		//寫入緩存文件
		$this->makePath($cacheFile);
		file_put_contents($cacheFile, $template, LOCK_EX);
		return true;
	}

	/**
	 * 獲取模板文件內容  使用佈局的時候返回處理完的模板
	 *
	 * @param $tplFile
	 * @param int $type 緩存類型0當前操作的模板的緩存 1包含的模板的緩存
	 *
	 * @return string
	 */
	private function getTplContent($tplFile, $type)
	{
		if ($type === 0 && !is_null($this->layout)) {//主模板且存在模板佈局
			$layoutCon = file_get_contents($this->layout);
			$tplCon = file_get_contents($tplFile);

			//獲取子模板內容
			$presult = preg_match_all(
				'#' . $this->options['leftDelimiter'] . 'to\s+([a_zA-Z]+?)' . $this->options['rightDelimiter'] . '(.*?)' . $this->options['leftDelimiter'] . '\/to' . $this->options['rightDelimiter'] . '#is',
				$tplCon,
				$tmpl
			);
			$tplCon = null;
			if ($presult > 0) {
				array_shift($tmpl);
			}
			//保存子模板提取完的區塊內容
			for ($i = 0; $i < $presult; $i++) {
				$this->layoutBlockData[$tmpl[0][$i]] = $tmpl[1][$i];
			}
			$presult = null;

			//將子模板內容替換到佈局文件返回
			$layoutBlockData = &$this->layoutBlockData;
			$layoutCon = preg_replace_callback(
				'#' . $this->options['leftDelimiter'] . 'block\s+([a_zA-Z]+?)' . $this->options['rightDelimiter'] . '(.*?)' . $this->options['leftDelimiter'] . '\/block' . $this->options['rightDelimiter'] . '#is',
				function ($matches) use ($layoutBlockData) {
					array_shift($matches);
					if (isset($layoutBlockData[$matches[0]])) {
						//替換{parent}標籤並返回
						return str_replace(
							$this->options['rightDelimiter'] . 'parent' . $this->options['rightDelimiter'],
							$matches[1],
							$layoutBlockData[$matches[0]]
						);
					} else {
						return '';
					}
				},
				$layoutCon
			);
			unset($layoutBlockData);
			$this->layoutBlockData = [];
			return $layoutCon;//返回替換完的佈局文件內容
		} else {
			return file_get_contents($tplFile);
		}
	}

	/**
	 * 根據指定的路徑創建不存在的文件夾
	 *
	 * @param string $path 路徑/文件夾名稱
	 *
	 * @return string
	 */
	private function makePath($path)
	{
		$path = dirname($path);
		if (!is_dir($path) && !mkdir($path, 0700, true)) {
			throw new MkdirErrorException(Lang::get('_CREATE_DIR_ERROR_') . "[{$path}]");
		}
		return true;
	}

	/**
	 * 重置所有參數
	 *
	 */
	public function reset()
	{
		$this->layout = null;
		$this->args = [];
		$this->layoutBlockData = [];
		return $this;
	}

	/**
	 * 正常情況佈局文件直接通過displayWithLayout方法指定，會自動從主題目錄/layout裡尋找。但是一些特殊情況要單獨設置佈局。
	 *
	 * @param string $layout 必須為絕對路徑
	 */
	public function setLayout($layout = '')
	{
		$layout && $this->layout = $layout;
	}
}
