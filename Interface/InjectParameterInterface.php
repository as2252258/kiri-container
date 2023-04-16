<?php
declare(strict_types=1);

namespace Kiri\Di\Interface;

interface InjectParameterInterface
{


	/**
	 * @param string $class
	 * @param string $method
	 * @return mixed
	 */
	public function dispatch(string $class, string $method): mixed;

}
