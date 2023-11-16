<?php
declare(strict_types=1);

namespace Kiri\Di;

use Closure;
use Exception;
use Kiri;


/**
 * 服务定位器
 */
class LocalService
{


	private array $_components = [];


	private array $_definition = [];


    /**
     * @param $name
     * @param $define
     * @throws Exception
     */
	public function set($name, $define): void
	{
		unset($this->_components[$name]);

		$this->_definition[$name] = $define;
		if (is_object($define) || $define instanceof Closure) {
			$this->_components[$name] = $this->get($name, $define);
		}
	}


	/**
	 * @throws Exception
	 */
	public function get(string $name, $throwException = true)
	{
		if (isset($this->_components[$name])) {
			return $this->_components[$name];
		}
		if (isset($this->_definition[$name])) {
			$definition = $this->_definition[$name];
			if (is_object($definition) && !$definition instanceof Closure) {
				return $this->_components[$name] = $definition;
			}
			return $this->_components[$name] = Kiri::createObject($definition);
		} else if ($throwException) {
			throw new Exception("Unknown component ID: $name");
		}
		return null;
	}


    /**
     * @param array $components
     * @throws Exception
     */
	public function setComponents(array $components): void
	{
		foreach ($components as $name => $component) {
			$this->set($name, $component);
		}
	}


	/**
	 * @param $id
	 * @return bool
	 */
	public function has($id): bool
	{
		return isset($this->_components[$id]) || isset($this->_definition[$id]);
	}


	/**
	 * @param $id
	 */
	public function remove($id): void
	{
		unset($this->_components[$id], $this->_definition[$id]);
	}


}
