<?php

declare(strict_types=1);


namespace Kiri\Di;

use Kiri\Di\Inject\Container;
use Kiri\Abstracts\Component;
use Kiri\Di\Inject\Skip;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionMethod;

class Scanner extends Component
{


    /**
     * @var ContainerInterface
     */
    #[Container(ContainerInterface::class)]
    public ContainerInterface $container;


    /**
     * @var array
     */
    private array $files = [];


    /**
     * @param string $path
     * @return void
     * @throws ReflectionException
     */
    public function load_directory(string $path): void
    {
        $dir  = new \DirectoryIterator($path);
        $skip = \config('scanner.skip', []);
        foreach ($dir as $value) {
            if ($value->isDot() || str_starts_with($value->getFilename(), '.')) {
                continue;
            }
            if ($value->isDir()) {
                if (in_array($value->getRealPath() . '/', $skip)) {
                    continue;
                }
                $this->load_directory($value->getRealPath());
            } else if ($value->getExtension() == 'php') {
                $this->load_file($value->getRealPath());
            }
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
     * @throws ReflectionException
     */
    private function load_file(string $path): void
    {
        try {
            require_once "$path";
            $path = str_replace($_SERVER['PWD'], '', $path);
            $path = str_replace('.php', '', $path);
            $this->parseFile($path);
        } catch (\Throwable $throwable) {
            error($throwable);
        }
    }


    /**
     * @param $file
     * @return void
     * @throws ReflectionException
     */
    protected function parseFile($file): void
    {
        $class = $this->rename($file);
        if (class_exists($class)) {
            $reflect = $this->container->getReflectionClass($class);
            if ($reflect->isInstantiable()) {
                $data = $reflect->getAttributes(Skip::class);
                if (count($data) > 0) {
                    return;
                }
                $object  = $this->container->parse($class);
                $methods = $this->container->getReflectionClass($class);
                foreach ($methods->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->isStatic() || $method->getDeclaringClass()->getName() != $class) {
                        continue;
                    }
                    $attributes = $method->getAttributes();
                    foreach ($attributes as $attribute) {
                        if (!class_exists($attribute->getName())) {
                            continue;
                        }
                        $attribute->newInstance()->dispatch($object, $method->getName());
                    }
                }
            }
        }
    }
}
