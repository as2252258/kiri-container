<?php

namespace Kiri\Di\Interface;

interface InjectPropertyInterface
{


	public function dispatch(object $class, string $property): void;

}
