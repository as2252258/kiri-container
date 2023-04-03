<?php

namespace Kiri\Di\Context;

use Swoole\Coroutine;

class CoroutineContext implements ContextInterface
{


	/**
	 * @param string $key
	 * @param mixed $value
	 * @param int|null $coroutineId
	 * @return mixed
	 */
	public static function set(string $key, mixed $value, ?int $coroutineId = null): mixed
	{
		// TODO: Implement set() method.
		if (is_null($coroutineId)) {
			$coroutineId = Coroutine::getCid();
		}
		return Coroutine::getContext($coroutineId)[$key] = $value;
	}

	/**
	 * @param string $key
	 * @param mixed|null $defaultValue
	 * @param int|null $coroutineId
	 * @return mixed
	 */
	public static function get(string $key, mixed $defaultValue = null, ?int $coroutineId = null): mixed
	{
		// TODO: Implement get() method.
		return Coroutine::getContext($coroutineId)[$key] ?? $defaultValue;
	}

	/**
	 * @param string $key
	 * @param int|null $coroutineId
	 * @return mixed
	 */
	public static function exists(string $key, ?int $coroutineId = null): bool
	{
		// TODO: Implement exists() method.
		return isset(Coroutine::getContext($coroutineId)[$key]);
	}

	/**
	 * @param string $key
	 * @param int|null $coroutineId
	 * @return void
	 */
	public static function remove(string $key, ?int $coroutineId = null): void
	{
		// TODO: Implement remove() method.
		Coroutine::getContext($coroutineId)[$key] = null;
		unset(Coroutine::getContext($coroutineId)[$key]);
	}


	/**
	 * @param $id
	 * @param int $value
	 * @param int|null $coroutineId
	 * @return bool|int
	 */
	public static function increment($id, int $value = 1, ?int $coroutineId = null): bool|int
	{
		if (is_null($coroutineId)) {
			$coroutineId = Coroutine::getCid();
		}
		if (!isset(Coroutine::getContext($coroutineId)[$id])) {
			Coroutine::getContext($coroutineId)[$id] = 0;
		}
		return Coroutine::getContext($coroutineId)[$id] += $value;
	}

	/**
	 * @param $id
	 * @param int $value
	 * @param int|null $coroutineId
	 * @return bool|int
	 */
	public static function decrement($id, int $value = 1, ?int $coroutineId = null): bool|int
	{
		if (is_null($coroutineId)) {
			$coroutineId = Coroutine::getCid();
		}
		if (!isset(Coroutine::getContext($coroutineId)[$id])) {
			Coroutine::getContext($coroutineId)[$id] = 0;
		}
		return Coroutine::getContext($coroutineId)[$id] -= $value;
	}
}
