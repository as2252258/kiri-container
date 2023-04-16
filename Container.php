<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 17:27
 */
declare(strict_types=1);

namespace Kiri\Di;


use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

class Container implements ContainerInterface
{

	/**
	 * @var array
	 *
	 * instance class by className
	 */
	private array $_singletons = [];


	/**
	 * @var array
	 *
	 * implements \ReflectClass
	 */
	private array $_reflection = [];


	/**
	 * @var array
	 */
	private array $_parameters = [];


	/**
	 * @var array
	 */
	private array $_interfaces = [];


	private static self|null $container = null;


	private function __construct()
	{
	}


	/**
	 * @return static
	 */
	public static function instance(): static
	{
		if (static::$container === null) {
			static::$container = new Container();
		}
		return static::$container;
	}


	/**
	 * @param string $id
	 * @return mixed
	 * @throws ReflectionException
	 */
	public function get(string $id): mixed
	{
		if ($id === ContainerInterface::class) {
			return $this;
		}
		if (!isset($this->_singletons[$id])) {
			if (isset($this->_interfaces[$id])) {
				$id = $this->_interfaces[$id];
			}
			$this->_singletons[$id] = $this->make($id);
		}
		return $this->_singletons[$id];
	}


	/**
	 * @param string $id
	 * @return void
	 * @throws ReflectionException
	 */
	public function parse(string $id): void
	{
		if (isset($this->_singletons[$id])) {
			return;
		}
		$this->make($id);
	}


	/**
	 * @param string $interface
	 * @param string $class
	 * @return void
	 */
	public function set(string $interface, string $class): void
	{
		$this->_interfaces[$interface] = $class;
	}


	/**
	 * @param string $interface
	 * @param object $object
	 * @return void
	 */
	public function bind(string $interface, object $object): void
	{
		$this->_singletons[$interface] = $object;
	}


	/**
	 * @param string $className
	 * @return ReflectionClass
	 * @throws ReflectionException
	 */
	public function getReflectionClass(string $className): ReflectionClass
	{
		if (isset($this->_reflection[$className])) {
			return $this->_reflection[$className];
		}

		$class = new ReflectionClass($className);

		return $this->_reflection[$className] = $class;
	}


	/**
	 * @param string $className
	 * @param array $construct
	 * @param array $config
	 * @return object
	 * @throws ReflectionException
	 */
	public function make(string $className, array $construct = [], array $config = []): object
	{
		$reflect = $this->getReflectionClass($className);

		$constructorHandler = $reflect->getConstructor();
		if (count($construct) < 1 && $constructorHandler !== null) {
			$construct = $this->getMethodParams($constructorHandler);
		}

		$object = self::configure($reflect->newInstanceArgs($construct), $config);

		$this->resolveProperties($reflect, $object);
		if ($constructorHandler === null && method_exists($object, 'init')) {
			call_user_func([$object, 'init']);
		}
		return $object;
	}


	/**
	 * @param ReflectionClass $getReflectionClass
	 * @param object $class
	 * @return void
	 */
	public function resolveProperties(ReflectionClass $getReflectionClass, object $class): void
	{
		$properties = $getReflectionClass->getProperties();
		foreach ($properties as $property) {
			$propertyAttributes = $property->getAttributes();

			foreach ($propertyAttributes as $attribute) {
				$attribute->newInstance()->dispatch($class, $property->getName());
			}
		}
	}


	/**
	 * @param string $className
	 * @param string $method
	 * @return ReflectionMethod
	 * @throws ReflectionException
	 */
	public function getMethod(string $className, string $method): ReflectionMethod
	{
		$reflection = $this->getReflectionClass($className);

		return $reflection->getMethod($method);
	}


	/**
	 * @param string $className
	 * @return ReflectionMethod[]
	 * @throws ReflectionException
	 */
	public function getMethods(string $className): array
	{
		$reflection = $this->getReflectionClass($className);

		return $reflection->getMethods();
	}


	/**
	 * @param ReflectionMethod $parameters
	 * @return array
	 * @throws ReflectionException
	 */
	public function getMethodParams(ReflectionMethod $parameters): array
	{
		$className = $parameters->getDeclaringClass()->getName();
		$methodName = $parameters->getName();
		if (!isset($this->_parameters[$className])) {
			return $this->_parameters[$className][$methodName] = $this->resolveMethodParams($parameters);
		}
		if (!isset($this->_parameters[$className][$methodName])) {
			$this->_parameters[$className][$methodName] = $this->resolveMethodParams($parameters);
		}
		return $this->_parameters[$className][$methodName];
	}


	/**
	 * @param \Closure $parameters
	 * @return array
	 * @throws ReflectionException
	 */
	public function getFunctionParams(\Closure $parameters): array
	{
		return $this->resolveMethodParams(new ReflectionFunction($parameters));
	}


	/**
	 * @param ReflectionMethod|ReflectionFunction $parameters
	 * @return array
	 * @throws ReflectionException
	 */
	private function resolveMethodParams(ReflectionMethod|ReflectionFunction $parameters): array
	{
		$params = [];
		if ($parameters->getNumberOfParameters() < 1) {
			return $params;
		}
		$parametersArray = $parameters->getParameters();

		$className = $parameters->getDeclaringClass()->getName();
		foreach ($parametersArray as $parameter) {
			$parameterAttributes = $parameter->getAttributes();
			if (count($parameterAttributes) < 1) {
				if ($parameter->isDefaultValueAvailable()) {
					$value = $parameter->getDefaultValue();
				} else if ($parameter->getType() === null) {
					$value = $parameter->getType();
				} else {
					$value = $parameter->getType()->getName();
					if (class_exists($value) || interface_exists($value)) {
						$value = $this->get($value);
					} else {
						$value = $this->getTypeValue($parameter);
					}
				}
				$params[$parameter->getName()] = $value;
			} else {
				$attribute = $parameterAttributes[0]->newInstance();

				$params[$parameter->getName()] = $attribute->dispatch($className, $parameters->getName());
			}
		}
		return $params;
	}


	/**
	 * @param ReflectionParameter $parameter
	 * @return string|int|bool|null
	 */
	private function getTypeValue(ReflectionParameter $parameter): string|int|bool|null
	{
		return match ($parameter->getType()) {
			'string' => '',
			'int', 'float' => 0,
			'', null, 'object', 'mixed' => NULL,
			'bool' => false,
			'default' => null
		};
	}


	/**
	 * @param object $object
	 * @param array $config
	 * @return object
	 */
	public static function configure(object $object, array $config): object
	{
		foreach ($config as $key => $value) {
			if (!property_exists($object, $key)) {
				continue;
			}
			$object->{$key} = $value;
		}
		return $object;
	}


	/**
	 * @param string $id
	 * @return bool
	 */
	public function has(string $id): bool
	{
		// TODO: Implement has() method.
		return isset($this->_singletons[$id]) && isset($this->_reflection[$id]);
	}


}
