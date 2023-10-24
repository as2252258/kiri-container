<?php
declare(strict_types=1);


namespace Kiri\Di\Inject;


use Exception;
use Kiri\Di\Container;
use Kiri\Di\Interface\InjectParameterInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class ContainerParams implements InjectParameterInterface
{


	/**
	 * @param mixed $value
	 */
	public function __construct(readonly public mixed $value)
	{
	}


	/**
	 * @return mixed|null
	 * @throws Exception
	 */
	public function dispatch(string $class, string $method): mixed
	{
		return Container::instance()->get($this->value);
	}

}
