<?php
declare(strict_types=1);

namespace Kiri\Di\Interface;

interface InjectPropertyInterface
{


	public function dispatch(object $class, string $property): void;

}
