<?php

namespace Kiri\Di\Context;

interface ContextInterface
{


	/**
	 * @param string $key
	 * @param mixed $value
	 * @param int|null $coroutineId
	 * @return mixed
	 */
	public static function set(string $key, mixed $value, ?int $coroutineId = null): mixed;

	/**
	 * @param string $key
	 * @param mixed|null $defaultValue
	 * @param int|null $coroutineId
	 * @return mixed
	 */
	public static function get(string $key, mixed $defaultValue = null, ?int $coroutineId = null): mixed;

	/**
	 * @param string $key
	 * @param int|null $coroutineId
	 * @return mixed
	 */
	public static function exists(string $key, ?int $coroutineId = null): bool;


	/**
	 * @param string $key
	 * @param int|null $coroutineId
	 * @return void
	 */
	public static function remove(string $key, ?int $coroutineId = null): void;


	/**
	 * @param string $id
	 * @param int $value
	 * @param $coroutineId
	 * @return bool|int
	 */
	public static function increment(string $id, int $value = 1, $coroutineId = null): bool|int;


	/**
	 * @param string $id
	 * @param int $value
	 * @param $coroutineId
	 * @return bool|int
	 */
	public static function decrement(string $id, int $value = 1, $coroutineId = null): bool|int;


}
