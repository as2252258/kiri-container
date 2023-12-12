<?php

declare(strict_types=1);


namespace Kiri\Di\Context;

class AsyncContext implements ContextInterface
{


    /**
     * @var array
     */
    private static array $context = [];


    /**
     * @return bool
     */
    public static function inCoroutine(): bool
    {
        return false;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $coroutineId
     * @return mixed
     */
    public static function set(string $key, mixed $value, ?int $coroutineId = null): mixed
    {
        // TODO: Implement set() method.
        return static::$context[$key] = $value;
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
        return static::$context[$key] ?? $defaultValue;
    }

    /**
     * @param string $key
     * @param int|null $coroutineId
     * @return mixed
     */
    public static function exists(string $key, ?int $coroutineId = null): bool
    {
        // TODO: Implement exists() method.
        return isset(static::$context[$key]);
    }

    /**
     * @param string $key
     * @param int|null $coroutineId
     * @return void
     */
    public static function remove(string $key, ?int $coroutineId = null): void
    {
        // TODO: Implement remove() method.
        static::$context[$key] = null;
        unset(static::$context[$key]);
    }

    /**
     * @param string $id
     * @param int $value
     * @param int|null $coroutineId
     * @return int
     */
    public static function increment(string $id, int $value = 1, ?int $coroutineId = null): int
    {
        if (!isset(static::$context[$id])) {
            static::$context[$id] = 0;
        }
        return static::$context[$id] += $value;
    }

    /**
     * @param string $id
     * @param int $value
     * @param int|null $coroutineId
     * @return int
     */
    public static function decrement(string $id, int $value = 1, ?int $coroutineId = null): int
    {
        if (!isset(static::$context[$id])) {
            static::$context[$id] = 0;
        }
        return static::$context[$id] -= $value;
    }
}
