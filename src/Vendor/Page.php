<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 分頁類
 * *********************************************************** */

namespace Cml\Vendor;

use Cml\Cml;
use Cml\Config;
use Cml\Http\Input;
use Cml\Http\Response;

/**
 * 分頁類,對外系統現在一般使用js分頁很少用到php分頁了
 *
 * @package Cml\Vendor
 */
class Page
{
	/**
	 * 分頁欄每頁顯示的頁數
	 *
	 * @var int
	 */
	public $barShowPage = 5;

	/**
	 * 頁數跳轉時要帶的參數
	 *
	 * @var array
	 */
	public $param;

	/**
	 * 分頁的基礎url地址默認獲取當前操作
	 *
	 * @var string
	 */
	public $url = '';

	/**
	 * 列表每頁顯示條數
	 *
	 * @var int
	 */
	public $numPerPage;

	/**
	 * 起始行數
	 *
	 * @var int
	 */
	public $firstRow;

	/**
	 * 分頁總頁數
	 *
	 * @var int
	 */
	protected $totalPages;

	/**
	 * 總行數
	 *
	 * @var int
	 */
	protected $totalRows;

	/**
	 * @var int 當前頁數
	 */
	protected $nowPage;

	/**
	 * @var int 分頁欄的總頁數
	 */
	protected $coolPages;

	/**
	 * @var mixed|string 分頁變量名
	 */
	protected $pageShowVarName;

	/**
	 * @var array 分頁定制顯示
	 */
	protected $config = [
		'header' => '條記錄',
		'prev' => '上一頁',
		'next' => '下一頁',
		'first' => '第一頁',
		'last' => '最後一頁',
		'theme' => '<li><a>%totalRow% %header% %nowPage%/%totalPage%頁</a></li>%upPage% %downPage% %first%  %prePage% %linkPage%  %nextPage%  %end%'
	];

	/**
	 * 構造函數
	 *
	 * @param int $totalRows 總行數
	 * @param int $numPerPage 每頁顯示條數
	 * @param array $param 分頁跳轉時帶的參數 如：['name' => '張三']
	 */
	public function __construct($totalRows, $numPerPage = 20, $param = [])
	{
		$this->totalRows = $totalRows;
		$this->numPerPage = $numPerPage ? intval($numPerPage) : 10;
		$this->pageShowVarName = Config::get('var_page') ? Config::get('var_page') : 'p';
		$this->param = $param;
		$this->totalPages = ceil($this->totalRows / $this->numPerPage);
		$this->coolPages = ceil($this->totalPages / $this->barShowPage);
		$this->nowPage = Input::getInt($this->pageShowVarName, 1);
		if ($this->nowPage < 1) {
			$this->nowPage = 1;
		} elseif (!empty($this->totalRows) && $this->nowPage > $this->totalPages) {
			$this->nowPage = $this->totalPages;
		}
		$this->firstRow = $this->numPerPage * ($this->nowPage - 1);
	}

	/**
	 * 配置參數
	 *
	 * @param string $name 配置項
	 * @param string $value 配置的值
	 *
	 * @return void
	 */
	public function setConfig($name, $value)
	{
		isset($this->config[$name]) && ($this->config[$name] = $value);
	}

	/**
	 *輸出分頁
	 */
	public function show()
	{
		if ($this->totalRows == 0) return '';
		$nowCoolPage = ceil($this->nowPage / $this->barShowPage);
		$delimiter = Config::get('url_pathinfo_depr');
		$params = array_merge($this->param, [$this->pageShowVarName => '__PAGE__']);
		$paramsString = '';
		foreach ($params as $key => $val) {
			$paramsString == '' || $paramsString .= '/';
			$paramsString .= $key . '/' . $val;
		}

		if ($this->url) {
			$url = rtrim(Response::url($this->url . '/' . $paramsString, false), $delimiter);
		} else {
			$url = rtrim(Response::url(Cml::getContainer()->make('cml_route')->getFullPathNotContainSubDir() . '/' . $paramsString, false), $delimiter);
		}
		$upRow = $this->nowPage - 1;
		$downRow = $this->nowPage + 1;
		$upPage = $upRow > 0 ? '<li><a href = "' . str_replace('__PAGE__', $upRow, $url) . '">' . $this->config['prev'] . '</a></li>' : '';
		$downPage = $downRow <= $this->totalPages ? '<li><a href="' . str_replace('__PAGE__', $downRow, $url) . '">' . $this->config['next'] . '</a></li>' : '';

		// << < > >>
		if ($nowCoolPage == 1) {
			$theFirst = $prePage = '';
		} else {
			$preRow = $this->nowPage - $this->barShowPage;
			$prePage = '<li><a href="' . str_replace('__PAGE__', $preRow, $url) . '">上' . $this->barShowPage . '頁</a></li>';
			$theFirst = '<li><a href="' . str_replace('__PAGE__', 1, $url) . '">' . $this->config['first'] . '</a></li>';
		}

		if ($nowCoolPage == $this->coolPages) {
			$nextPage = $theEnd = '';
		} else {
			$nextRow = $this->nowPage + $this->barShowPage;
			$theEndRow = $this->totalPages;
			$nextPage = '<li><a href="' . str_replace('__PAGE__', $nextRow, $url) . '">下' . $this->barShowPage . '頁</a></li>';
			$theEnd = '<li><a href="' . str_replace('__PAGE__', $theEndRow, $url) . '">' . $this->config['last'] . '</a></li>';
		}

		//1 2 3 4 5
		$linkPage = '';
		for ($i = 1; $i <= $this->barShowPage; $i++) {
			$page = ($nowCoolPage - 1) * $this->barShowPage + $i;
			if ($page != $this->nowPage) {
				if ($page <= $this->totalPages) {
					$linkPage .= '&nbsp;<li><a href="' . str_replace('__PAGE__', $page, $url) . '">&nbsp;' . $page . '&nbsp;</a></li>';
				} else {
					break;
				}
			} else {
				if ($this->totalPages != 1) {
					$linkPage .= '&nbsp;<li class="active"><a>' . $page . '</a></li>';
				}
			}
		}
		$pageStr = str_replace(
			['%header%', '%nowPage%', '%totalRow%', '%totalPage%', '%upPage%', '%downPage%', '%first%', '%prePage%', '%linkPage%', '%nextPage%', '%end%'],
			[$this->config['header'], $this->nowPage, $this->totalRows, $this->totalPages, $upPage, $downPage, $theFirst, $prePage, $linkPage, $nextPage, $theEnd],
			$this->config['theme']
		);
		return '<ul>' . $pageStr . '</ul>';
	}
}
