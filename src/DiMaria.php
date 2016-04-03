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
    protected $preferences = [];
    protected $aliases = [];
    protected $cache = [];
    protected $injections = [];
    protected $params = [];
    protected $shared = [];
    protected $sharedInstance = [];

    /**
     * Set a preferred implementation of a class/interface
     * @param string $alias  the name of the alias
     * @param string $class  the name of the class/interface
     * @return self
     */
    public function setPreference(string $alias, string $class): self
    {
        $this->preferences[$alias] = $class;
        return $this;
    }

    /**
     * Alias a class/interface/alias to a string.
     * @param string $alias   the name of the alias
     * @param string $class   the name of the class
     * @param array  $params  a key/value array of parameter names and values
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
     * @param string $class the name of the class
     * @param string $method    the name of the method
     * @param array  $params    a key/value array of parameter names and values
     * @return self
     */
    public function setInjection(string $class, string $method, array $params = []): self
    {
        $this->injections[$class][$method][] = $params;
        return $this;
    }

    /**
     * Set parameters of a class
     * @param string $class the name of the class
     * @param array  $params    a key/value array of parameter names and values
     * @return self
     */
    public function setParams(string $class, array $params): self
    {
        $this->params[$class] = $params + ($this->params[$class] ?? []);
        return $this;
    }

    /**
     * Mark a class/alias as shared
     * @param string $class the name of class/alias
     * @return self
     */
    public function setShared(string $class): self
    {
        $this->shared[$class] = true;
        return $this;
    }

    public function has($class): bool
    {
        return class_exists($class) ?: isset($this->aliases[$class]) ?: isset($this->preferences[$class]);
    }

    public function set($key, $value): self
    {
        $this->sharedInstance[$key] = $value;
        return $this;
    }

    /**
     * Get an instance of a class
     * @param  string $class  the name of class/alias to create
     * @param  array $params  a key/value array of parameter names and values
     * @return mixed          an instance of the class requested
     */
    public function get($class, array $params = [])
    {
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
     * @param  string $class  the name of class/alias to create
     * @param  array $params  a key/value array of parameter names and values
     * @return mixed          an instance of the class requested
     */
    public function create(string $class, array $params = [])
    {
        if (isset($this->shared[$class])) {
            return $this->get($class, $params);
        }
        return $this->getObject($this->getClassName($class), $params);
    }

    protected function getClassName(string $class): string
    {
        if (! $this->has($class)) {
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
            $callback = $this->cache[$originalClass] ?? $this->cache[$originalClass] = $this->getCallback($class, $originalClass);
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
        if (! $constructor || ! $constructor->getNumberOfParameters()) {
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

    public function getParameters(array $methodInfo, array $params): array
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
                $parameters[] = $this->get($param['type']);
            } else {
                throw new ContainerException('Required parameter $' . $param['name'] . ' is missing');
            }
        }
        return $parameters;
    }

    protected function determineParameter($param, bool $isVariadic): array
    {
        if (is_array($param)) {
            if ($isVariadic) {
                $params = [];
                foreach ($param as $val) {
                    $params[] = isset($val['instanceOf']) ? $this->create($val['instanceOf'], $val['params'] ?? []) : $val;
                }
                return $params;
            }
            return isset($param['instanceOf']) ? [$this->create($param['instanceOf'], $param['params'] ?? [])] : [$param];
        }
        return [$param];
    }
}
