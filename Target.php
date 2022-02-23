<?php

namespace Kiri\Di;

use JetBrains\PhpStorm\Pure;
use ReflectionAttribute;
use ReflectionClass;
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
		$this->resolveProperty();
		$this->resolveMethods();
	}


	/**
	 * @return mixed|ReflectionMethod|null
	 */
	public function getConstruct(): mixed
	{
		return $this->construct;
	}


	/**
	 * @return void
	 */
	protected function resolveMethods()
	{
		$methods = $this->target->getMethods();
		foreach ($methods as $method) {
			$this->methods[$method->getName()] = $method;
		}
	}


	/**
	 * @return void
	 */
	protected function resolveProperty()
	{
		$methods = $this->target->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PUBLIC |
			ReflectionProperty::IS_PROTECTED);
		foreach ($methods as $method) {
			$this->property[$method->getName()] = $method;
		}
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
	 * @return ReflectionProperty|null
	 */
	#[Pure] public function getProperty(string $property): ?ReflectionProperty
	{
		return $this->property[$property] ?? null;
	}


	/**
	 * @return array<string, ReflectionMethod>
	 */
	public function getMethods(): array
	{
		return $this->methods;
	}


	/**
	 * @param string $method
	 * @return ReflectionMethod|null
	 */
	public function getMethod(string $method): ReflectionMethod|null
	{
		return $this->methods[$method] ?? null;
	}


	/**
	 * @param string $method
	 * @param string $annotation
	 * @return mixed
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
	 * @return array<string, ReflectionAttribute[]>
	 */
	#[Pure] public function getMethodsAttribute(): array
	{
		$methods = $this->methods;

		$array = [];
		foreach ($methods as $key => $method) {
			$array[$key] = $this->getMethodAttribute($method);
		}
		return $array;
	}


	/**
	 * @return ReflectionProperty[]
	 */
	#[Pure] public function getPropertyAttribute(): array
	{
		return $this->property;
	}


	/**
	 * @param string $property
	 * @param string|null $annotation
	 * @return ReflectionAttribute[]|ReflectionAttribute
	 */
	#[Pure] public function getMethodAttribute(string $property, ?string $annotation = null): array|ReflectionAttribute
	{
		/** @var ReflectionMethod $attributes */
		$attributes = $this->methods[$property] ?? [];
		if (!empty($attributes)) {
			if (empty($annotation)) {
				return $attributes->getAttributes();
			}
			$anno = $attributes->getAttributes($annotation);
			if (count($anno) > 0) {
				return $anno[0];
			}
		}
		return [];
	}
}
