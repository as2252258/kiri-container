<?php

namespace Kiri\Di;

class TargetManager
{


	private static array $targets = [];


	/**
	 * @param string $class
	 * @return Target|null
	 */
	public static function get(string $class): ?Target
	{
		return static::$targets[$class] ?? null;
	}


	/**
	 * @param string $class
	 * @return bool
	 */
	public static function has(string $class): bool
	{
		return isset(static::$targets[$class]) && static::$targets[$class] !== null;
	}

	/**
	 * @param string $class
	 * @param \ReflectionClass $reflection
	 * @return Target
	 */
	public static function set(string $class, \ReflectionClass $reflection): Target
	{
		$target = new Target($reflection);

		static::$targets[$class] = $target;

		return $target;
	}


}
