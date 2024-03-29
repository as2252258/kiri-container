<?php

declare(strict_types=1);


namespace Kiri\Di;

use Kiri\Di\Inject\Container;
use Kiri\Abstracts\Component;
use Kiri\Di\Inject\Skip;
use Psr\Container\ContainerInterface;
use ReflectionClass;
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
    public array $files = [];


    /**
     * @param string $path
     * @return void
     * @throws
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
                if (in_array($value->getRealPath(), $this->files)) {
                    continue;
                }
                $this->files[] = $value->getRealPath();
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
     * @throws
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
     * @throws
     */
    protected function parseFile($file): void
    {
        $class = $this->rename($file);
        if (class_exists($class)) {
            $reflect = $this->container->getReflectionClass($class);
            if ($reflect->isInstantiable()) {
                if ($reflect->isTrait() || $reflect->isEnum() || $reflect->isInterface()) {
                    return;
                }
                $attributes = $this->skipNames($reflect);
                if (in_array(Skip::class, $attributes) || in_array(\Attribute::class, $attributes)) {
                    return;
                }
                foreach ($reflect->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->isStatic() || $method->getDeclaringClass()->getName() != $class) {
                        continue;
                    }
                    $attributes = $method->getAttributes();
                    foreach ($attributes as $attribute) {
                        if (!class_exists($attribute->getName())) {
                            continue;
                        }
                        $attribute->newInstance()->dispatch($class, $method->getName());
                    }
                }
            }
        }
    }


    /**
     * @param ReflectionClass $reflect
     * @return array
     */
    protected function skipNames(ReflectionClass $reflect): array
    {
        $attributes = $reflect->getAttributes();
        $names      = [];
        foreach ($attributes as $attribute) {
            $names[] = $attribute->getName();
        }
        return $names;
    }
}
