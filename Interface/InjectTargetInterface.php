<?php

namespace Kiri\Di\Interface;

interface InjectTargetInterface
{


    /**
     * @param string $class
     * @return void
     */
    public function dispatch(string $class): void;

}