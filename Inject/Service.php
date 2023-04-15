<?php

namespace Kiri\Di\Inject;


use Exception;
use Kiri\Di\Container;
use Kiri\Di\Interface\InjectPropertyInterface;
use Kiri\Di\LocalService;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Service implements InjectPropertyInterface
{


	/**
	 * @param string $service
	 */
	public function __construct(readonly public string $service)
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
		$service = Container::instance()->get(LocalService::class);

		$class->{$property} = $service->get($this->service);
	}

}
