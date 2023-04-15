<?php
declare(strict_types=1);

namespace Kiri\Di\Inject;

use Kiri\Di\Interface\InjectPropertyInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Config implements InjectPropertyInterface
{


	/**
	 * @param string $key
	 */
	public function __construct(public string $key)
	{
	}


	/**
	 * @param object $class
	 * @param string $property
	 * @return void
	 */
	public function dispatch(object $class, string $property): void
	{
		// TODO: Implement dispatch() method.
		$class->{$property} = config($this->key);
	}

}
