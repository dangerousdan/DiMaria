<?php
namespace DD;

/**
 * DiMaria Dependency injector
 */
class DiMaria
{
    protected $preferences = [];
    protected $aliases = [];
    protected $cache = [];
    protected $injections = [];
    protected $params = [];
    protected $shared = [];
    protected $sharedInstance = [];

    /**
     * Set multiple di rules at once.
     * @param array $rules a multi-dimensional array of rules to set
     * @return self
     */
    public function setRules(array $rules): self
    {
        $rules['preferences'] = $rules['preferences'] ?? [];
        $rules['aliases'] = $rules['aliases'] ?? [];
        $rules['params'] = $rules['params'] ?? [];
        $rules['shared'] = $rules['shared'] ?? [];
        $rules['injections'] = $rules['injections'] ?? [];

        foreach ($rules['preferences'] as $interface => $class) {
            $this->setPreference($interface, $class);
        }
        foreach ($rules['aliases'] as $alias => $aliasConfig) {
            $this->setAlias($alias, ...$aliasConfig);
        };
        foreach ($rules['params'] as $instance => $params) {
            $this->setParams($instance, $params);
        }
        foreach ($rules['shared'] as $instance => $isShared) {
            if ($isShared) {
                $this->setShared($instance);
            }
        };
        foreach ($rules['injections'] as $instance => $config) {
            foreach ($config as $params) {
                $this->setInjection($instance, ...$params);
            }
        }
        return $this;
    }

    /**
     * Set a preferred implementation of a class/interface
     * @param string $alias     the name of the alias
     * @param string $className the name of the class/interface
     * @return self
     */
    public function setPreference(string $alias, string $className): self
    {
        $this->preferences[$alias] = $className;
        return $this;
    }

    /**
     * Alias a class/interface/alias to a string.
     * @param string $alias     the name of the alias
     * @param string $className the name of the class
     * @param array  $params    a key/value array of parameter names and values
     * @return self
     */
    public function setAlias(string $alias, string $className, array $params = []): self
    {
        $this->aliases[$alias] = [
            'className' => $className,
            'params' => $params
        ];
        return $this;
    }

    /**
     * Set rule to call a method after constructing a class
     * @param string $className the name of the class
     * @param string $method    the name of the method
     * @param array  $params    a key/value array of parameter names and values
     * @return self
     */
    public function setInjection(string $className, string $method, array $params = []): self
    {
        $this->injections[$className][$method][] = $params;
        return $this;
    }

    /**
     * Set parameters of a class
     * @param string $className the name of the class
     * @param array  $params    a key/value array of parameter names and values
     * @return self
     */
    public function setParams(string $className, array $params): self
    {
        $this->params[$className] = $params + ($this->params[$className] ?? []);
        return $this;
    }

    /**
     * Mark a class/alias as shared
     * @param string $className the name of class/alias
     * @return self
     */
    public function setShared(string $className): self
    {
        $this->shared[$className] = true;
        return $this;
    }

    /**
     * Get an instance of a class
     * @param  string $className the name of class/alias to create
     * @param  array $params     a key/value array of parameter names and values
     * @return mixed             an instance of the class requested
     */
    public function get(string $className, array $params = [])
    {
        if (isset($this->shared[$className]) && isset($this->sharedInstance[$className])) {
            return $this->sharedInstance[$className];
        }
        while ($preference = $this->preferences[$className] ?? false) {
            $className = $preference;
        }
        $originalClassName = $className;
        while ($alias = $this->aliases[$className] ?? false) {
            $params = $params + $alias['params'];
            $className = $alias['className'];
        }
        $callback = $this->cache[$originalClassName] ?? $this->getCallback($className, $originalClassName);
        $object = $callback($params);
        if (isset($this->shared[$originalClassName])) {
            $this->sharedInstance[$originalClassName] = $object;
        }
        return $object;
    }

    protected function getCallback(string $className, string $originalClassName): callable
    {
        $callback = $this->generateCallback($className);
        if (isset($this->injections[$originalClassName])) {
            foreach ($this->injections[$originalClassName] as $method => $instance) {
                $methodInfo = $this->getMethodInfo(new \ReflectionMethod($className, $method));
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
        $this->cache[$originalClassName] = $callback;
        return $callback;
    }

    protected function generateCallback(string $className): callable
    {
        $constructor = (new \ReflectionClass($className))->getConstructor();
        if (! $constructor || ! $constructor->getNumberOfParameters()) {
            return function () use ($className) {
                return new $className;
            };
        }
        $constructorInfo = $this->getMethodInfo($constructor);
        $predefinedParams = $this->params[$className] ?? [];
        return function ($params) use ($className, $constructorInfo, $predefinedParams) {
            return new $className(...$this->getParameters($constructorInfo, $params + $predefinedParams));
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
                throw new \Exception('Required parameter $' . $param['name'] . ' is missing');
            }
        }
        return $parameters;
    }

    protected function determineParameter($param, bool $isVariadic): array
    {
        if (is_array($param)) {
            if ($isVariadic) {
                $params = [];
                foreach ($param as $a) {
                    $params[] = isset($a['instanceOf']) ? $this->get($a['instanceOf']) : $a;
                }
                return $params;
            }
            return isset($param['instanceOf']) ? [$this->get($param['instanceOf'])] : [$param];
        }
        return [$param];
    }
}
