<?php
namespace DD;

use DD\DiMaria\Exception\ContainerException;
use DD\DiMaria\Exception\NotFoundException;
use Interop\Container\ContainerInterface;

/**
 * DiMaria Dependency injector
 */
class DiMaria implements ContainerInterface
{
    protected $aliases = [];
    protected $factory = [];
    protected $injections = [];
    protected $params = [];
    protected $preferences = [];
    protected $shared = [];
    protected $sharedInstance = [];

    public function __construct()
    {
        $this->sharedInstance[__CLASS__] = $this;
    }

    /**
     * Alias a class/interface/alias to a string
     * @param  string $alias   the name of the alias
     * @param  string $class   the name of the class
     * @param  array  $params  a key/value array of parameter names and values
     * @return self
     */
    public function setAlias(string $alias, string $class, array $params = []): self
    {
        $this->aliases[$alias] = [
            'class' => $class,
            'params' => $params
        ];
        return $this;
    }

    /**
     * Set rule to call a method after constructing a class
     * @param  string $class   the name of the class
     * @param  string $method  the name of the method
     * @param  array  $params  a key/value array of parameter names and values
     * @return self
     */
    public function setInjection(string $class, string $method, array $params = []): self
    {
        $this->injections[$class][$method][] = $params;
        return $this;
    }

    /**
     * Set parameters of a class
     * @param  string $class   the name of the class
     * @param  array  $params  a key/value array of parameter names and values
     * @return self
     */
    public function setParams(string $class, array $params): self
    {
        $this->params[$class] = $params + ($this->params[$class] ?? []);
        return $this;
    }

    /**
     * Set a preferred implementation of a class/interface
     * @param  string $alias  the name of the alias
     * @param  string $class  the name of the class/interface
     * @return self
     */
    public function setPreference(string $alias, string $class): self
    {
        $this->preferences[$alias] = $class;
        return $this;
    }

    /**
     * Set an instance to always return a shared or new instance, regardless of method used
     * @param  string $class     the name of class/alias
     * @param  bool   $isShared  true will always return a shared instance. false will always return a new instance
     * @return self
     */
    public function setShared(string $class, bool $isShared = true): self
    {
        $this->shared[$class] = $isShared;
        return $this;
    }

    /**
     * Set a value. Retrievable with the get method
     * @param  string $key    a name to retrieve the value by
     * @param  mixed  $value  the content to store against the key
     * @return self
     */
    public function set(string $key, $value): self
    {
        $this->sharedInstance[$key] = $value;
        return $this;
    }

    /**
     * Set a factory. Retrievable with the create method. The get method will fetch but also cache the response
     * @param  string   $key      a name to retrieve the content by
     * @param  callable $factory  a callable to be invoked when fetched
     * @return self
     */
    public function setFactory(string $key, callable $factory): self
    {
        $this->factory[$key] = $factory;
        return $this;
    }

    /**
     * Returns true if DiMaria can return an entry for the given string. Returns false otherwise.
     * @param  string $class  identifier of the entry to look for
     * @return boolean
     */
    public function has($class): bool
    {
        return class_exists($class) ?: isset($this->aliases[$class]) ?: isset($this->preferences[$class]) ?: isset($this->sharedInstance[$class]) ?: isset($this->factory[$class]);
    }

    /**
     * Get an instance of a class
     * @param  string $class       the name of class/alias to create
     * @param  array $params       a key/value array of parameter names and values
     * @throws NotFoundException   when no entry was found
     * @throws ContainerException  if class could not be constructed
     * @return mixed               an instance of the class requested
     */
    public function get($class, array $params = [])
    {
        if (isset($this->shared[$class]) && !$this->shared[$class]) {
            return $this->create($class, $params);
        }
        if (isset($this->sharedInstance[$class])) {
            return $this->sharedInstance[$class];
        }
        $class = $this->getClassName($class);

        $object = $this->getObject($class, $params);
        $this->sharedInstance[$class] = $object;
        return $object;
    }

    /**
     * Get a new instance of a class
     * @param  string $class       the name of class/alias to create
     * @param  array $params       a key/value array of parameter names and values
     * @throws NotFoundException   if no entry was found
     * @throws ContainerException  if class could not be constructed
     * @return mixed               an instance of the class requested
     */
    public function create(string $class, array $params = [])
    {
        if (isset($this->shared[$class]) && $this->shared[$class]) {
            return $this->get($class, $params);
        }
        return $this->getObject($this->getClassName($class), $params);
    }

    protected function getClassName(string $class): string
    {
        if (!$this->has($class)) {
            throw new NotFoundException('Class or alias ' . $class . ' does not exist');
        }
        while ($preference = $this->preferences[$class] ?? false) {
            $class = $preference;
        }
        return $class;
    }

    protected function getObject(string $class, array $params)
    {
        $originalClass = $class;
        while ($alias = $this->aliases[$class] ?? false) {
            $params = $params + $alias['params'];
            $class = $alias['class'];
        }
        try {
            $callback = $this->factory[$originalClass] ?? $this->factory[$originalClass] = $this->getCallback($class, $originalClass);
            return $callback($params);
        } catch (\Exception $e) {
            throw new ContainerException($e->getMessage(), $e->getCode(), $e);
        } catch (\Error $e) {
            throw new ContainerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function getCallback(string $class, string $originalClass): callable
    {
        $callback = $this->generateCallback($class);
        if (isset($this->injections[$originalClass])) {
            foreach ($this->injections[$originalClass] as $method => $instance) {
                $methodInfo = $this->getMethodInfo(new \ReflectionMethod($class, $method));
                foreach ($instance as $methodParameters) {
                    $methodParams = $this->getParameters($methodInfo, $methodParameters);
                    $callback = function ($params) use ($callback, $method, $methodParams) {
                        $object = $callback($params);
                        $object->$method(...$methodParams);
                        return $object;
                    };
                };
            }
        }
        return $callback;
    }

    protected function generateCallback(string $class): callable
    {
        $constructor = (new \ReflectionClass($class))->getConstructor();
        if (!$constructor || !$constructor->getNumberOfParameters()) {
            return function () use ($class) {
                return new $class;
            };
        }
        $constructorInfo = $this->getMethodInfo($constructor);
        $predefinedParams = $this->params[$class] ?? [];

        return function ($params) use ($class, $constructorInfo, $predefinedParams) {
            return new $class(...$this->getParameters($constructorInfo, $params + $predefinedParams));
        };
    }

    protected function getMethodInfo(\ReflectionMethod $method): array
    {
        $paramInfo = [];
        foreach ($method->getParameters() as $param) {
            $paramType = $param->hasType() ? $param->getType() : null;
            $paramType = $paramType ? $paramType->isBuiltin() ? null : $paramType->__toString() : null;
            $paramInfo[$param->name] = [
                'name' => $param->name,
                'optional' => $param->isOptional(),
                'default' => $param->isOptional() ? ($param->isVariadic() ? null : $param->getDefaultValue()) : null,
                'variadic' => $param->isVariadic(),
                'type' => $paramType,
            ];
        }
        return $paramInfo;
    }

    protected function getParameters(array $methodInfo, array $params): array
    {
        $parameters = [];
        foreach ($methodInfo as $param) {
            if (isset($params[$param['name']])) {
                array_push($parameters, ...$this->determineParameter($params[$param['name']], $param['variadic']));
            } elseif ($param['optional']) {
                if ($param['variadic']) {
                    break;
                }
                $parameters[] = $param['default'];
            } elseif ($param['type']) {
                $parameters[] = $this->create($param['type']);
            } else {
                throw new ContainerException('Required parameter $' . $param['name'] . ' is missing');
            }
        }
        return $parameters;
    }

    protected function determineParameter($param, bool $isVariadic): array
    {
        if (!is_array($param)) {
            return [$param];
        }
        if (isset($param['instanceOf'])) {
            return [$this->create($param['instanceOf'], $param['params'] ?? [])];
        }
        foreach ($param as $key => $val) {
            $param[$key] = $this->determineParameter($val, false)[0];
        }
        if (!$isVariadic) {
            return [$param];
        }
        return $param;
    }
}
