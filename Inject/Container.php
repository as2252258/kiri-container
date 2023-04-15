<?php
declare(strict_types=1);

namespace Kiri\Di\Inject;


use Exception;
use Kiri\Di\Interface\InjectPropertyInterface;
use Kiri\Di\Container as DContainer;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Container implements InjectPropertyInterface
{


	/**
	 * @param string $service
	 * @param mixed|null $default
	 * @throws Exception
	 */
	public function __construct(readonly public string $service, public mixed $default = null)
	{
	}


	/**
	 * @param object $class
	 * @param string $property
	 * @return void
	 * @throws Exception
	 */
	public function dispatch(object $class, string $property): void
	{
		$class->{$property} = DContainer::instance()->get($this->service);
	}


}
