# DiMaria Dependency Injector

DiMaria is a Dependency Injection Container for PHP 7 with no dependencies. It's written to be extremely fast and lightweight.

## Installation
Fetch DiMaria via composer.

## Usage
`DiMaria` should work out of the box.
```
$di = new DD\DiMaria;
$object = $di->get('ClassName');
```

### Automatic injection of Type Hinted Classes
If a class parameter is type-hinted to a class, this class will automatically be fetched.
```
class Foo
{
    public function __construct(DbStorage $dbStorage)
    {
        ...
    }
}
```

```
$di = new DD\DiMaria;
$object = $di->get('Foo');
```

### Setting Parameters
Parameters can be set in by defining them with `setParams()`
```
class Credentials
{
    public function __construct($username, $password, $server = 'localhost')
    {
        ...
    }
}
```

```
$di = new DD\DiMaria;
$di->setParams('DbStorage', [
    'username' => 'readAccess',
    'password' => 'abcd'
]);
$object = $di->get('Credentials');
```
*Note that parameters with default values will be used if they are not set.*

### Overriding Parameters
Parameters can also be set during object creation. These will override any existing parameters.
```
$di = new DD\DiMaria;
$di->setParams('DbStorage', [
    'username' => 'readAccess',
    'password' => 'abcd'
]);
$object = $di->get('DbStorage', [
    'server'   => 'writeAccess',
    'password' => '1234'
]);
```

### Defining Classes in config
To pass an instance of a class as a parameter, we need to set the parameter to `['instanceOf' => 'className']`.
Otherwise DiMaria will attempt to pass the string `className` as its parameter.
```
class Repository
{
    public function __construct(StorageInterface $storage)
    {
        ...
    }
}
```

```
$di = new DD\DiMaria;
$di->setParams('Repository', [
    'storage' => ['instanceOf' => 'DbStorage'],
]);
$object = $di->get('Repository');
```

### Aliasing
Aliases are created in the same way as parameters are set, except we also pass an alias name. Setting parameters on aliases is optional. Any parameters set will override any parameters set on the class it is pointing to.

```
$di = new DD\DiMaria;
$di->setParams('DbStorage', [
    'username' => 'readAccess',
    'password' => 'abcd'
]);
$di->setAlias('DbStorageWithWritePermissions', 'DbStorage', [
    'username' => 'writeAccess',
    'password' => '1234'
]);

$readStorage = $di->get('DbStorage');
$writeStorage = $di->get('DbStorageWithWritePermissions');
```

#### Aliasception
It is also possible to alias an alias. Parameters will be merged together. The 'outer' alias parameters will take precedence.

#### Handling Interfaces with Aliases
Aliasing also allows you to set a preferred implementation of an interface.
```
class Repository
{
    public function __construct(StorageInterface $storage)
    {
        ...
    }
}
```

```
$di = new DD\DiMaria;
$di->setAlias('StorageInterface', 'DbStorage');
$repository = $di->get('Repository');
```
A preferred implementation of an interface can then easily be overridden like this:

```
$di->setParams('Repository', [
    'storage' => ['instanceOf' => 'DbStorage'],
]);
```

### Shared Instances
```
$di = new DD\DiMaria;
$di->setShared('ClassName');
$object1 = $di->get('ClassName');
$object2 = $di->get('ClassName');
// $object1 === $object2;
```
*Note that when we fetch `$object2`, this simply returns a cached instance and doesn't create a new object, so any parameters sent are ignored.*

We can also create shared instances of aliases.
```
$di = new DD\DiMaria;
$di->setParams('DbStorage', [
    'username' => 'readAccess',
    'password' => 'abcd'
]);
$di->setAlias('DbStorageWithWritePermissions', 'DbStorage', [
    'username' => 'writeAccess',
    'password' => '1234'
]);

$di->setShared('DbStorage');
$di->setShared('DbStorageWithWritePermissions');

$readStorage = $di->get('DbStorage');
$writeStorage = $di->get('DbStorageWithWritePermissions');
```
### Classes with Variadic Parameters
Setting variadic parameters is similar to how we currently set parameters, except we will pass a multidimensional array to the parameter.
Variadic parameters will work with aliases and shared instances.

```
class Ballpool
{
    public function __construct($height = 10, $width = 10, $depth = 10, Ball ...$balls)
    {
        ...
    }
}
```
```
$di = new DD\DiMaria;

$di->setAlias('RedBall', 'Ball', ['colour' => 'red']);

$di->setParams('Ballpool', [
    'height' => 20,
    'balls' => [
        ['instanceOf' => 'Ball'],
        ['instanceOf' => 'Ball'],
        ['instanceOf' => 'RedBall']
    ]
]);
$ballpool = $di->get('Ballpool', [
    'width' => 5
]);
```
*Note: Variadic functions cannot have a default value, but they are always optional. Therefore in the example above, if we didn't set the parameter 'balls', it will be given an empty array.*

### Setter Injection
Setters can be configured to automatically be called after creating an object.
Setter injection rules are appended. This makes it possible to call the same setter multiple times.

Injection rules are not inherited. So they only apply to the class or alias defined in the rule.
```
class Foo
{
    public function doSomething($bar = false)
    {
        ...
    }
}
```

```
$di = new DD\DiMaria;

$di->setInjection('Foo', 'doSomething');
$di->setInjection('Foo', 'doSomething', ['bar' => true]);
$foo = $di->get('Foo');

```

### Setting Multiple Configuration Rules at Once.
If there are lots of di rules you wish to apply, rules can be applied in a 'cleaner' way by calling `setRules()`.
This allows us to set as many rules as we like, in one method call.

```
$di = new DD\DiMaria;

$di->setRules([
    'aliases' => [
        'My\Service1\StorageInterface' => ['My\Service1\Storage\Db'],
        'My\Service2\StorageInterface' => ['My\Service2\Storage\Db'],
        'My\DbAdapterServer2' => [
            'My\DbAdapter', [
                'username' => 'abcd',
                'password' => '1234',
                'server' => '1.2.3.4'
            ]
        ]
    ],
    'params' => [
        'My\DbAdapter' => [
            'username' => 'abcd',
            'password' => '5678',
        ]
    ],
    'shared' => [
        'My\DbAdapter' => true,
        'My\DbAdapterServer2' => true
    ],
    'injections' => [
        'Foo' => [
            ['doSomething']
            ['doSomething', ['bar' => true]],
        ]
    ]
]);
```

### Pro Tip
Instead of writing config with strings, we can get the class name with `::class`.
```
$di->setAlias(My\StorageInterface::class, My\Storage\DbStorage::class);```

Depending on your IDE, this means that you should be able to see [PHPDoc](http://www.phpdoc.org/) information and/or use IDE functions to [jump to the class declaration](https://www.jetbrains.com/phpstorm/help/navigating-to-declaration-or-type-declaration-of-a-symbol.html).

### Probably won't implement
* Setter Injection
If we can set all parameters on an object during creation then we should do so, otherwise we've made it possible to create an incomplete object. If parameters are optional, we should set default values for parameters.

### Speed
This is considerably faster than [Zend/Di](https://github.com/zendframework/zend-di) (both runtime and compiled definitions) in tests.

![DiMaria](http://news.bbcimg.co.uk/media/images/75979000/jpg/_75979820_goalcelebs.jpg)
