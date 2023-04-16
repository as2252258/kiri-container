<?php
declare(strict_types=1);

namespace Kiri\Di\Interface;

interface InjectParameterInterface
{


	/**
	 * @param object $class
	 * @param string $method
	 * @return mixed
	 */
	public function dispatch(object $class, string $method): mixed;

}
