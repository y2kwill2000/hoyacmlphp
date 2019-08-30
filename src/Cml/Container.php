<?php

namespace Cml;
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-09-10 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 容器
 * *********************************************************** */

class Container
{

	/**
	 * 綁定的規則
	 *
	 * @var array
	 */
	protected $binds = [];

	/**
	 * 可執行實例
	 *
	 * @var array
	 */
	protected $instances = [];

	/**
	 * 別名
	 *
	 * @var array
	 */
	protected $aliases = [];

	/**
	 * 判斷是否綁定過某服務
	 *
	 * @param string $abstract 服務的名稱
	 *
	 * @return bool
	 */
	public function isBind($abstract)
	{
		return isset($this->binds[$abstract]);
	}

	/**
	 * 判斷別名是否存在
	 *
	 * @param string $alias 別名
	 *
	 * @return bool
	 */
	public function isExistAlias($alias)
	{
		return isset($this->aliases[$alias]);
	}

	/**
	 * 綁定單例服務
	 *
	 * @param string|array $abstract 服務的名稱
	 * @param \Closure|string|null $concrete
	 * @return $this
	 */
	public function singleton($abstract, $concrete = null)
	{
		return $this->bind($abstract, $concrete, true);
	}

	/**
	 * 綁定服務
	 *
	 * @param mixed $abstract 要綁定的服務，傳數組的時候則設置別名
	 * @param mixed $concrete 實際執行的服務
	 * @param bool $singleton 是否為單例
	 *
	 * @return $this
	 */
	public function bind($abstract, $concrete = null, $singleton = false)
	{
		if (is_array($abstract)) {
			list($abstract, $alias) = [key($abstract), current($abstract)];
			$this->alias($abstract, $alias);
		}

		$abstract = $this->filter($abstract);
		$concrete = $this->filter($concrete);

		if (is_null($concrete)) {
			$concrete = $abstract;
		}

		$this->binds[$abstract] = compact('concrete', 'singleton');
		return $this;
	}

	/**
	 * 綁定一個別名
	 *
	 * @param string $abstract 服務的名稱
	 * @param string $alias 別名
	 *
	 * @return $this
	 */
	public function alias($abstract, $alias)
	{
		$this->aliases[$alias] = $this->filter($abstract);
		return $this;
	}

	/**
	 * 過濾
	 *
	 * @param mixed $abstract 服務的名稱
	 *
	 * @return string
	 */
	private function filter($abstract)
	{
		return is_string($abstract) ? ltrim($abstract, '\\') : $abstract;
	}

	/**
	 * 實例化服務
	 *
	 * @param mixed $abstract 服務的名稱
	 * @param mixed $parameters 參數
	 *
	 * @return mixed
	 */
	public function make($abstract, $parameters = [])
	{
		if ($alias = $this->getAlias($abstract)) {
			$abstract = $alias;
		}

		if (isset($this->instances[$abstract])) {
			return $this->instances[$abstract];
		}

		if (!isset($this->binds[$abstract])) {
			throw new \InvalidArgumentException(Lang::get('_CONTAINER_MAKE_PARAMS_ERROR_', $abstract));
		}

		if ($this->binds[$abstract]['concrete'] instanceof \Closure) {
			array_unshift($parameters, $this);
			$instance = call_user_func_array($this->binds[$abstract]['concrete'], (array)$parameters);
		} else {
			$concrete = $this->binds[$abstract]['concrete'];
			$instance = new $concrete($parameters);
		}
		$this->binds[$abstract]['singleton'] && $this->instances[$abstract] = $instance;

		return $instance;
	}

	/**
	 * 獲取綁定的別名
	 *
	 * @param string $alias 別名
	 * @return mixed
	 */
	public function getAlias($alias)
	{
		return isset($this->aliases[$alias]) ? $this->aliases[$alias] : false;
	}
}
