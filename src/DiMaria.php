<?php
namespace DD;

use DD\DiMaria\Exception;
use DD\DiMaria\NotFoundException;

/**
 * DiMaria Dependency injector
 */
class DiMaria implements \Interop\Container\ContainerInterface
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
        return class_exists($class) ?: isset($this->aliases[$class]);
    }

    /**
     * Get an instance of a class
     * @param  string $class  the name of class/alias to create
     * @param  array $params  a key/value array of parameter names and values
     * @return mixed          an instance of the class requested
     */
    public function get($class, array $params = [])
    {
        if (! $this->has($class)) {
            throw new NotFoundException('Class or alias ' . $class . 'does not exist');
        }

        if (isset($this->shared[$class]) && isset($this->sharedInstance[$class])) {
            return $this->sharedInstance[$class];
        }
        while ($preference = $this->preferences[$class] ?? false) {
            $class = $preference;
        }
        $originalClass = $class;
        while ($alias = $this->aliases[$class] ?? false) {
            $params = $params + $alias['params'];
            $class = $alias['class'];
        }
        try {
            $callback = $this->cache[$originalClass] ?? $this->getCallback($class, $originalClass);
            $object = $callback($params);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        } catch (\Error $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
        if (isset($this->shared[$originalClass])) {
            $this->sharedInstance[$originalClass] = $object;
        }
        return $object;
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
        $this->cache[$originalClass] = $callback;
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
                $parameters[] = $this->get($param['type']);
            } else {
                throw new Exception('Required parameter $' . $param['name'] . ' is missing');
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
                    $params[] = isset($val['instanceOf']) ? $this->get($val['instanceOf'], $val['params'] ?? []) : $val;
                }
                return $params;
            }
            return isset($param['instanceOf']) ? [$this->get($param['instanceOf'], $param['params'] ?? [])] : [$param];
        }
        return [$param];
    }
}
