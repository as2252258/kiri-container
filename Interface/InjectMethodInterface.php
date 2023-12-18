<?php

namespace Kiri\Di\Interface;

interface InjectMethodInterface
{


    /**
     * @param string $class
     * @param string $method
     * @return void
     */
    public function dispatch(string $class, string $method): void;

}