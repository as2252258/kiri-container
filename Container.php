<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 17:27
 */
declare(strict_types=1);

namespace Kiri\Di;

use Closure;
use Exception;
use Kiri;
use Kiri\Di\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Class Container
 * @package Kiri\Di
 */
class Container implements ContainerInterface
{

	/**
	 * @var array
	 *
	 * instance class by className
	 */
	private array $_singletons = [];

	/**
	 * @var ReflectionMethod[]
	 *
	 * class new instance construct parameter
	 */
	private array $_constructs = [];

	/**
	 * @var array
	 *
	 * implements \ReflectClass
	 */
	private array $_reflection = [];


	/** @var array */
	private array $_parameters = [];


	/** @var array|string[] */
	private array $_interfaces = [];


	/**
	 * @param string $id
	 * @return mixed
	 * @throws
	 */
	public function get(string $id): mixed
	{
		if ($id == ContainerInterface::class) {
			return $this;
		}
		return $this->make($id, [], []);
	}


	/**
	 * @param $class
	 * @param array $constrict
	 * @param array $config
	 * @return mixed
	 * @throws
	 */
	public function make($class, array $constrict = [], array $config = []): mixed
	{
		if ($class == ContainerInterface::class) {
			return $this;
		}
		if ($this->isInterface($class)) {
			$class = $this->_interfaces[$class];
			if (is_null($class)) {
				throw new Exception('Unknown class mapping ' . $class . '::class');
			}
		}
		if (!isset($this->_singletons[$class])) {
			$this->_singletons[$class] = $this->resolve($class, $constrict, $config);
		}
		return $this->_singletons[$class];
	}


	/**
	 * @param string $interface
	 * @param string $class
	 */
	public function mapping(string $interface, string $class)
	{
		$this->_interfaces[$interface] = $class;
	}


	/**
	 * @param $class
	 * @return bool
	 */
	public function isInterface($class): bool
	{
		$reflect = $this->getReflect($class);
		if ($reflect->isInterface()) {
			return true;
		}
		return false;
	}


	/**
	 * @param string $interface
	 * @param $object
	 */
	public function setBindings(string $interface, $object)
	{
		if (is_string($object)) {
			$this->_interfaces[$interface] = $object;
		} else {
			$className = get_class($object);
			$this->_interfaces[$interface] = $className;
			$this->_singletons[$className] = $object;
		}
	}


	/**
	 * @param $class
	 * @param array $constrict
	 * @param array $config
	 * @return object
	 * @throws
	 */
	public function create($class, array $constrict = [], array $config = []): object
	{
		return $this->resolve($class, $constrict, $config);
	}


	/**
	 * @param $class
	 * @param $constrict
	 * @param $config
	 *
	 * @return object
	 * @throws Exception
	 */
	private function resolve($class, $constrict, $config): object
	{
		$reflect = $this->resolveDependencies($class);
		if (!$reflect->isInstantiable()) {
			throw new ReflectionException('Class ' . $class . ' cannot be instantiated');
		}

		$object = $this->newInstance($reflect, $constrict);

		$this->propertyInject($reflect, $object);

		return $this->onAfterInit($object, $config);
	}


	/**
	 * @param ReflectionClass $reflect
	 * @param $dependencies
	 * @return object
	 * @throws ReflectionException
	 */
	private function newInstance(ReflectionClass $reflect, $dependencies): object
	{
		if (!isset($this->_constructs[$reflect->getName()])) {
			return $reflect->newInstance();
		}
		$construct = $this->_constructs[$reflect->getName()];
		if ($construct->getNumberOfParameters() < 1) {
			return $reflect->newInstance();
		}
		$parameters = $this->mergeParam($this->resolveParameters($construct), $dependencies);
		return $reflect->newInstanceArgs($parameters);
	}


	/**
	 * @param ReflectionClass $reflect
	 * @param $object
	 * @return mixed
	 * @throws Exception
	 */
	public function propertyInject(ReflectionClass $reflect, $object): mixed
	{
		$properties = TargetManager::get($reflect->getName());
		if (is_null($properties)) {
			return $object;
		}
		$properties = $properties->getPropertyAttribute();
		foreach ($properties as $property) {
			$attributes = $property->getAttributes();
			foreach ($attributes as $attribute) {
				$attribute->newInstance()->execute($object, $property);
			}
		}
		return $object;
	}


	/**
	 * @param $className
	 * @param string|null $method
	 * @return array
	 * @throws ReflectionException
	 */
	public function getMethodAttribute($className, ?string $method = null): array
	{
		return TargetManager::get($className)->getMethodAttribute($method);
	}


	/**
	 * @param string $class
	 * @param string|null $property
	 * @return ReflectionProperty|ReflectionProperty[]|null
	 * @throws ReflectionException
	 */
	public function getClassReflectionProperty(string $class, string $property = null): ReflectionProperty|null|array
	{
		return TargetManager::get($class)->getProperty($property);
	}


	/**
	 * @param $object
	 * @param $config
	 * @return mixed
	 */
	private function onAfterInit($object, $config): mixed
	{
		Kiri::configure($object, $config);
		if (method_exists($object, 'init') && is_callable([$object, 'init'])) {
			call_user_func([$object, 'init']);
		}
		return $object;
	}


	/**
	 * @param $class
	 * @return ReflectionClass
	 * @throws ReflectionException
	 */
	private function resolveDependencies($class): ReflectionClass
	{
		if (isset($this->_reflection[$class])) {
			return $this->_reflection[$class];
		}
		$reflect = new ReflectionClass($class);
		if ($reflect->isAbstract() || $reflect->isTrait() || $reflect->isInterface()) {
			return $this->_reflection[$class] = $reflect;
		}
		$construct = TargetManager::set($class, $reflect)->getConstruct();
		if (!empty($construct) && $construct->getNumberOfParameters() > 0) {
			$this->_constructs[$class] = $construct;
		}
		return $this->_reflection[$class] = $reflect;
	}


	/**
	 * @param string $class
	 * @return ReflectionMethod[]
	 */
	public function getReflectMethods(string $class): array
	{
		return TargetManager::get($class)->getMethods();
	}


	/**
	 * @param string $class
	 * @param string $method
	 * @return ReflectionMethod|null
	 * @throws ReflectionException
	 */
	public function getReflectMethod(string $class, string $method): ?ReflectionMethod
	{
		return TargetManager::get($class)->getMethod($method);
	}


	/**
	 * @param string|Closure $method
	 * @param string|null $className
	 * @return array|null
	 * @throws ReflectionException
	 */
	public function getArgs(string|Closure $method, ?string $className = null): ?array
	{
		if ($method instanceof Closure) {
			return $this->resolveParameters(new ReflectionFunction($method));
		}
		if (isset($this->_parameters[$className]) && isset($this->_parameters[$className][$method])) {
			return $this->_parameters[$className][$method];
		}
		$reflectMethod = $this->getReflectMethod($className, $method);
		if (!($reflectMethod instanceof ReflectionMethod)) {
			throw new ReflectionException("Class does not have a function $className::$method");
		}
		return $this->setParameters($className, $method, $this->resolveParameters($reflectMethod));
	}


	/**
	 * @param $class
	 * @param $method
	 * @param $parameters
	 * @return mixed
	 */
	private function setParameters($class, $method, $parameters): mixed
	{
		if (!isset($this->_parameters[$class])) {
			$this->_parameters[$class] = [];
		}
		return $this->_parameters[$class][$method] = $parameters;
	}


	/**
	 * @param ReflectionMethod|ReflectionFunction $reflectionMethod
	 * @return array
	 */
	private function resolveParameters(ReflectionMethod|ReflectionFunction $reflectionMethod): array
	{
		if ($reflectionMethod->getNumberOfParameters() < 1) {
			return [];
		}
		$params = [];
		foreach ($reflectionMethod->getParameters() as $key => $parameter) {
			if ($parameter->isDefaultValueAvailable()) {
				$params[$key] = $parameter->getDefaultValue();
			} else if ($parameter->getType() === null) {
				$params[$key] = $parameter->getType();
			} else {
				$type = $parameter->getType()->getName();
				if (class_exists($type) || interface_exists($type)) {
					$type = Kiri::getDi()->get($type);
				}
				$params[$key] = match ($parameter->getType()) {
					'string' => '',
					'int', 'float' => 0,
					'', null, 'object', 'mixed' => NULL,
					'bool' => false,
					default => $type
				};
			}
		}
		return $params;
	}


	/**
	 * @param $class
	 * @return ReflectionClass|null
	 */
	public function getReflect($class): ?ReflectionClass
	{
		if (!isset($this->_reflection[$class])) {
			return $this->resolveDependencies($class);
		}
		return $this->_reflection[$class];
	}


	/**
	 * @return $this
	 */
	public function flush(): static
	{
		$this->_reflection = [];
		$this->_singletons = [];
		$this->_constructs = [];
		return $this;
	}

	/**
	 * @param $old
	 * @param $newParam
	 *
	 * @return mixed
	 */
	private function mergeParam($old, $newParam): array
	{
		if (empty($old)) {
			return $newParam;
		} else if (empty($newParam)) {
			return $old;
		}
		foreach ($newParam as $key => $val) {
			$old[$key] = $val;
		}
		return $old;
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function has(string $id): bool
	{
		return isset($this->_singletons[$id]) || isset($this->_interfaces[$id]);
	}
}
