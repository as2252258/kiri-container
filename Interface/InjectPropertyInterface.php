<?php
declare(strict_types=1);

namespace Kiri\Di\Interface;

interface InjectPropertyInterface
{


    /**
     * @param object $class
     * @param string $property
     * @return void
     */
	public function dispatch(object $class, string $property): void;

}
