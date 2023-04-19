<?php

namespace Kiri\Di\Interface;

interface InjectProxyInterface
{


	/**
	 * @param string $fileName
	 * @param object $class
	 * @param string $method
	 * @return void
	 */
	public function dispatch(string $fileName, object $class, string $method): void;

}
