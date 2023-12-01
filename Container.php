<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 17:27
 */
declare(strict_types=1);

namespace Kiri\Di;


use Closure;
use Exception;
use Kiri\Di\Interface\InjectProxyInterface;
use Kiri\Router\Interface\ValidatorInterface;
use Psr\Container\ContainerInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;


/**
 * Class Container
 */
class Container implements ContainerInterface
{

    /**
     * @var array
     *
     * instance class by className
     */
    private array $_singletons = [];


    /**
     * @var array
     *
     * implements \ReflectClass
     */
    private array $_reflection = [];


    /**
     * @var array
     */
    private array $_parameters = [];


    /**
     * @var array
     */
    private array $_interfaces = [];


    private static self|null $container = null;


    /**
     *
     */
    private function __construct()
    {
    }


    /**
     * @return static
     */
    public static function instance(): static
    {
        if (static::$container === null) {
            static::$container = new Container();
        }
        return static::$container;
    }


    /**
     * @param string $id
     * @return mixed
     * @throws Exception
     */
    public function get(string $id): object
    {
        if ($id === ContainerInterface::class) return $this;
        if (isset($this->_singletons[$id])) return $this->_singletons[$id];
        if (isset($this->_interfaces[$id])) {
            $id = $this->_interfaces[$id];
        }
        return $this->_singletons[$id] = $this->make($id);
    }


    /**
     * @param string $id
     * @return object
     * @throws ReflectionException
     */
    public function parse(string $id): object
    {
        if (!isset($this->_singletons[$id])) {
            return $this->make($id);
        }
        return $this->_singletons[$id];
    }


    /**
     * @param string $interface
     * @param string $class
     * @return void
     */
    public function set(string $interface, string $class): void
    {
        $this->_interfaces[$interface] = $class;
    }


    /**
     * @param string $interface
     * @param object $object
     * @return object
     */
    public function bind(string $interface, object $object): object
    {
        $this->_singletons[$interface] = $object;
        return $object;
    }


    /**
     * @param string $className
     * @return ReflectionClass
     * @throws ReflectionException
     */
    public function getReflectionClass(string $className): ReflectionClass
    {
        if (!isset($this->_reflection[$className])) {
            $this->_reflection[$className] = new ReflectionClass($className);
        }
        return $this->_reflection[$className];
    }


    /**
     * @param string $className
     * @param array $construct
     * @param array $config
     * @return object|null
     * @throws ReflectionException
     */
    public function make(string $className, array $construct = [], array $config = []): ?object
    {
        $reflect = $this->getReflectionClass($className);
        if (!$reflect->isInstantiable()) {
            throw new ReflectionException('Class ' . $className . ' cannot be instantiated');
        }

        if (($handler = $reflect->getConstructor()) !== null) {
            $construct = $this->getMethodParams($handler);
        }
        $newInstance = $reflect->newInstanceArgs($construct);

        return $this->runInit($reflect, static::configure($newInstance, $config));
    }


    /**
     * @param ReflectionClass $reflect
     * @param object $object
     * @return void
     */
    protected function injectClassTarget(ReflectionClass $reflect, object $object): void
    {
        $this->resolveProperties($reflect, $object);
        $attributes = $reflect->getAttributes();
        foreach ($attributes as $attribute) {
            if (class_exists($attribute->getName())) {
                $instance = $attribute->newInstance();
                if ($object instanceof InjectProxyInterface) {
                    $instance->dispatch($reflect->getFileName(), $object);
                } else {
                    $instance->dispatch($object);
                }
            }
        }
    }


    /**
     * @param ReflectionClass $reflect
     * @param object $object
     * @return object
     */
    protected function runInit(ReflectionClass $reflect, object $object): object
    {
        $this->injectClassTarget($reflect, $object);
        if ($reflect->getName() === 'Symfony\Component\Console\Application') {
            return $object;
        }
        if (method_exists($object, 'init')) {
            call_user_func([$object, 'init']);
        }
        return $object;
    }


    /**
     * @param ReflectionClass $reflectionClass
     * @param object $class
     * @return void
     */
    public function resolveProperties(ReflectionClass $reflectionClass, object $class): void
    {
        $properties = $reflectionClass->getProperties();
        $filename   = $reflectionClass->getFileName();
        foreach ($properties as $property) {
            $propertyAttributes = $property->getAttributes();
            foreach ($propertyAttributes as $attribute) {
                if (!class_exists($attribute->getName()) || $this->isValidatorInterface($attribute)) {
                    continue;
                }
                $instance = $attribute->newInstance();
                if ($class instanceof InjectProxyInterface) {
                    $instance->dispatch($filename, $class, $property->getName());
                } else {
                    $instance->dispatch($class, $property->getName());
                }
            }
        }
    }


    /**
     * @param ReflectionAttribute $attribute
     * @return bool
     */
    protected function isValidatorInterface(ReflectionAttribute $attribute): bool
    {
        return in_array(ValidatorInterface::class, class_implements($attribute->getName()));
    }

    /**
     * @param string $className
     * @param string $method
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    public function getMethod(string $className, string $method): ReflectionMethod
    {
        $reflection = $this->getReflectionClass($className);

        return $reflection->getMethod($method);
    }


    /**
     * @param string $className
     * @return ReflectionMethod[]
     * @throws ReflectionException
     */
    public function getMethods(string $className): array
    {
        $reflection = $this->getReflectionClass($className);

        return $reflection->getMethods();
    }


    /**
     * @param ReflectionMethod $parameters
     * @return array
     * @throws ReflectionException
     */
    public function getMethodParams(ReflectionMethod $parameters): array
    {
        $className  = $parameters->getDeclaringClass()->getName();
        $methodName = $parameters->getName();
        if (!isset($this->_parameters[$className])) {
            $this->_parameters[$className] = [$methodName => []];
        }
        if (!isset($this->_parameters[$className][$methodName])) {
            return $this->_parameters[$className][$methodName] = $this->resolveMethodParams($parameters);
        } else {
            return $this->_parameters[$className][$methodName];
        }
    }


    /**
     * @param Closure $parameters
     * @return array
     * @throws ReflectionException
     */
    public function getFunctionParams(Closure $parameters): array
    {
        return $this->resolveMethodParams(new ReflectionFunction($parameters));
    }


    /**
     * @param ReflectionMethod|ReflectionFunction $parameters
     * @return array
     * @throws ReflectionException|Exception
     */
    public function resolveMethodParams(ReflectionMethod|ReflectionFunction $parameters): array
    {
        $params = [];
        if ($parameters->getNumberOfParameters() < 1) {
            return $params;
        }
        $parametersArray = $parameters->getParameters();
        $class           = $parameters->getDeclaringClass()->getName();
        foreach ($parametersArray as $parameter) {
            $parameterAttributes = $parameter->getAttributes();
            $name                = $parameter->getName();
            if (count($parameterAttributes) > 0) {
                $attribute     = $parameterAttributes[0]->newInstance();
                $params[$name] = $attribute->dispatch($class, $parameters->getName());
            } else {
                $params[$name] = $this->contractParams($parameter);
            }
        }
        return $params;
    }


    /**
     * @param $parameter
     * @return bool|int|mixed|object|string|null
     * @throws Exception
     */
    protected function contractParams($parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        if ($parameter->getType() === null) {
            return $parameter->getType();
        }
        $value = $parameter->getType()->getName();
        if (class_exists($value) || interface_exists($value)) {
            return $this->get($value);
        } else {
            return $this->getTypeValue($parameter);
        }
    }


    /**
     * @param ReflectionParameter $parameter
     * @return string|int|bool|null
     */
    private function getTypeValue(ReflectionParameter $parameter): string|int|bool|null
    {
        return match ($parameter->getType()) {
            'string'                    => '',
            'int', 'float'              => 0,
            '', null, 'object', 'mixed' => NULL,
            'bool'                      => false,
            'default'                   => null
        };
    }


    /**
     * @param object $object
     * @param array $config
     * @return object
     */
    public static function configure(object $object, array $config): object
    {
        foreach ($config as $key => $value) {
            if (!property_exists($object, $key)) {
                continue;
            }
            $object->{$key} = $value;
        }
        return $object;
    }


    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        // TODO: Implement has() method.
        return isset($this->_singletons[$id]) && isset($this->_reflection[$id]);
    }


}
