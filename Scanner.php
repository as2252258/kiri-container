<?php

declare(strict_types=1);


namespace Kiri\Di;

use Exception;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use ReflectionException;

class Scanner extends Component
{


	private array $files = [];


	/**
	 * @param string $path
	 * @return void
	 */
	public function read(string $path): void
	{
		$this->load_dir($path);
	}


	/**
	 * @param string $namespace
	 * @return void
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function parse(string $namespace): void
	{
		$container = Container::instance();
		foreach ($this->files as $file) {
			$class = $this->rename($file);
			if (file_exists($class)) {
				error('Please follow the PSR-4 specification to write code.' . $class);
				continue;
			}
			$container->parse($class);
		}
	}


	/**
	 * @param string $file
	 * @return string
	 */
	private function rename(string $file): string
	{
		$filter = array_filter(explode('/', $file), function ($value) {
			if (empty($value)) {
				return false;
			}
			return ucfirst($value);
		});
		return ucfirst(implode('\\', $filter));
	}


	/**
	 * @param string $path
	 * @return void
	 */
	private function load_dir(string $path): void
	{
		$dir = new \DirectoryIterator($path);
		$skip = Config::get('scanner.skip', []);
		foreach ($dir as $value) {
			if ($value->isDot() || str_starts_with($value->getFilename(), '.')) {
				continue;
			}
			if ($value->isDir()) {
				if (in_array($value->getRealPath() . '/', $skip)) {
					continue;
				}
				$this->load_dir($value->getRealPath());
			} else if ($value->getExtension() == 'php') {
				$this->load_file($value->getRealPath());
			}
		}
	}


	/**
	 * @param string $path
	 * @return void
	 */
	private function load_file(string $path): void
	{
		try {
			require_once "$path";
			$path = str_replace($_SERVER['PWD'], '', $path);
			$path = str_replace('.php', '', $path);
			$this->files[] = $path;
		} catch (\Throwable $throwable) {
			error($throwable);
		}
	}


}
