<?php

namespace Kiri\Di\Interface;

interface InjectProxyInterface
{


    /**
     * @param string $fileName
     * @param string $class
     * @param string $method
     * @return void
     */
	public function dispatch(string $fileName, string $class, string $method): void;

}
