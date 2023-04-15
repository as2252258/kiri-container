<?php


namespace Kiri\Di\Inject;


use Exception;
use Kiri\Di\Interface\InjectParameterInterface;
use Kiri\Di\LocalService;
use Kiri\Di\Container;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class ServiceParams implements InjectParameterInterface
{


	/**
	 * @param mixed $value
	 */
	public function __construct(readonly public mixed $value)
	{
	}


	/**s
	 * @return mixed|null
	 * @throws Exception
	 */
	public function dispatch(): mixed
	{
		$service = Container::instance()->get(LocalService::class);

		return $service->get($this->value);
	}

}
