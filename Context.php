<?php


namespace Kiri\Di;

use Kiri\Di\Context\AsyncContext;
use Kiri\Di\Context\ContextInterface;
use Kiri\Di\Context\CoroutineContext;
use Swoole\Coroutine;

/**
 * Class Context
 * @package Yoc\http
 * @mixin ContextInterface
 */
class Context
{


	/**
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public static function __callStatic(string $name, array $arguments): mixed
	{
		// TODO: Implement __callStatic() method.
		if (static::inCoroutine()) {
			return call_user_func([CoroutineContext::class, $name], ...$arguments);
		} else {
			return call_user_func([AsyncContext::class, $name], ...$arguments);
		}
	}


	/**
	 * @return bool
	 */
	public static function inCoroutine(): bool
	{
		return Coroutine::getCid() > -1;
	}

}



