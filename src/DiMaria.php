<?php
namespace DD;

class DiMaria
{
    protected $aliases = [];
    protected $cache = [];
    protected $params = [];
    protected $rules;
    protected $shared = [];
    protected $sharedInstance = [];

    /**
     * Set multiple di rules at once.
     * Rules are applied in the following format:
     * [
     *  'aliases' => ['aliasName' => ['instance', ['optional key/value array of params']]],
     *  'params' => ['instance' => ['key/value array of params']],
     *  'shared' => ['instance' => 'isShared']
     * ]
     *
     * @param array $rules a multi-dimensional array of rules to set
     * @return self
     */
    public function setRules(array $rules): self
    {
        if ($rules['aliases']) {
            foreach ($rules['aliases'] as $alias => $aliasConfig) {
                $this->setAlias($alias, ...$aliasConfig);
            }
        }
        if ($rules['params']) {
            foreach ($rules['params'] as $instance => $params) {
                $this->setParams($instance, $params);
            }
        }
        if ($rules['shared']) {
            foreach ($rules['shared'] as $instance => $isShared) {
                $this->setShared($instance, $isShared);
            }
        }
        return $this;
    }

    /**
     * Alias a class/interface/alias to another.
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
     * @param string  $className the name of class/alias
     * @param boolean $isShared  turn shared instances of this class on or off (default: true)
     * @return self
     */
    public function setShared(string $className, bool $isShared = true): self
    {
        if ($isShared) {
            $this->shared[$className] = true;
            return $this;
        }
        unset($this->shared[$className]);
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
        if ($this->isShared($className) && isset($this->sharedInstance[$className])) {
            return $this->sharedInstance[$className];
        }
        $originalClassName = $className;
        while ($alias = $this->getAlias($className)) {
            $params = $params + $alias['params'];
            $className = $alias['className'];
        }
        $callback = $this->cache[$originalClassName] ?? $this->getCallback($className, $originalClassName);
        $object = $callback($params);
        if ($this->isShared($originalClassName)) {
            $this->sharedInstance[$originalClassName] = $object;
        }
        return $object;
    }

    protected function getCallback(string $className, string $originalClassName): callable
    {
        $callback = $this->generateCallback($className);
        $this->cache[$originalClassName] = $callback;
        return $callback;
    }

    protected function generateCallback(string $className): callable
    {
        $constructor = (new \ReflectionClass($className))->getConstructor();

        if (! $constructor || ! $constructor->getNumberOfParameters()) {
            return function($params) use ($className) {
                return new $className;
            };
        }
        $constructorInfo = $this->getConstructorInfo($constructor);
        $predefinedParams = $this->getParams($className);

        return function($params) use ($className, $constructorInfo, $predefinedParams) {
            return new $className(...$this->getParameters($constructorInfo, $params + $predefinedParams));
        };
    }

    protected function getConstructorInfo(\ReflectionMethod $method): array
    {
        $paramInfo = [];
        foreach ($method->getParameters() as $param) {
            $paramType = $param->hasType() ? $param->getType() : null;
            $paramType = $paramType ? $paramType->isBuiltin() ? null : $paramType->__toString() : null;

            $paramInfo[$param->getName()] = [
                'name' => $param->getName(),
                'optional' => $param->isOptional(),
                'default' => $param->isOptional() ? ($param->isVariadic() ? null : $param->getDefaultValue()) : null,
                'variadic' => $param->isVariadic(),
                'type' => $paramType,
            ];
        }
        return $paramInfo;
    }

    protected function getParameters(array $constructorInfo, array $params): array
    {
        $parameters = [];
        foreach ($constructorInfo as $param) {
            if (isset($params[$param['name']])) {
                array_push($parameters, ...$this->determineParameter($params[$param['name']], $param['variadic']));
            } elseif ($param['optional']) {
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
                foreach ($param as $a) {
                    $params[] = isset($a['instanceOf']) ? $this->get($a['instanceOf']) : $a;
                }
                return $params;
            }
            return isset($param['instanceOf']) ? [$this->get($param['instanceOf'])] : [$param];
        }
        return [$param];
    }

    protected function isShared(string $className): bool
    {
        return isset($this->shared[$className]);
    }

    protected function getAlias(string $className): array
    {
        return $this->aliases[$className] ?? [];
    }

    protected function getParams(string $className): array
    {
        return $this->params[$className] ?? [];
    }
}
