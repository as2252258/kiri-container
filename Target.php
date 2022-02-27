<?php

namespace Kiri\Di;

use JetBrains\PhpStorm\Pure;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class Target
{


	private ReflectionClass $target;


	private array $methods = [];


	private array $property = [];


	private mixed $construct = [];


	/**
	 * @param mixed $target
	 */
	public function __construct(ReflectionClass $target)
	{
		$this->target = $target;
		$this->construct = $target->getConstructor();
	}


	/**
	 * @return mixed|ReflectionMethod|null
	 */
	public function getConstruct(): mixed
	{
		return $this->construct;
	}


	/**
	 * @return ReflectionAttribute[]
	 */
	#[Pure] public function getAttributes(): array
	{
		return $this->target->getAttributes();
	}


	/**
	 * @param string $property
	 * @return ReflectionProperty
	 * @throws ReflectionException
	 */
	#[Pure] public function getProperty(string $property): ReflectionProperty
	{
		return $this->target->getProperty($property);
	}


	/**
	 * @return array<string, ReflectionMethod>
	 */
	#[Pure] public function getMethods(): array
	{
		return $this->target->getMethods();
	}


	/**
	 * @param string $method
	 * @return ReflectionMethod
	 * @throws ReflectionException
	 */
	#[Pure] public function getMethod(string $method): ReflectionMethod
	{
		return $this->target->getMethod($method);
	}


	/**
	 * @param string $method
	 * @param string $annotation
	 * @return mixed
	 * @throws ReflectionException
	 */
	public function getSpecify_annotation(string $method, string $annotation): mixed
	{
		$data = $this->getMethodAttribute($method, $annotation);
		if (!empty($data)) {
			return $data->newInstance();
		}
		return null;
	}


	/**
	 * @return array
	 * @throws ReflectionException
	 */
	#[Pure] public function getMethodsAttribute(): array
	{
		$methods = $this->target->getMethods();

		$array = [];
		foreach ($methods as $method) {
			$array[$method->getName()] = $this->getMethodAttribute($method->getName());
		}
		return $array;
	}


	/**
	 * @return ReflectionProperty[]
	 */
	#[Pure] public function getPropertyAttribute(): array
	{
		return $this->target->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC);
	}


	/**
	 * @param string $property
	 * @param string|null $annotation
	 * @return array|ReflectionAttribute
	 * @throws ReflectionException
	 */
	#[Pure] public function getMethodAttribute(string $property, ?string $annotation = null): array|ReflectionAttribute
	{
		$attributes = $this->target->getMethod($property);
		if (!empty($annotation)) {
			return $attributes->getAttributes($annotation);
		}
		return $attributes->getAttributes();
	}
}
