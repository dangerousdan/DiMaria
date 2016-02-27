# DiMaria Dependency Injector

[![Build Status](https://travis-ci.org/dangerousdan/DiMaria.svg?branch=master)](https://travis-ci.org/dangerousdan/DiMaria)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dangerousdan/DiMaria/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/dangerousdan/DiMaria/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/dangerousdan/DiMaria/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/dangerousdan/DiMaria/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/dangerousdan/DiMaria/badges/build.png?b=master)](https://scrutinizer-ci.com/g/dangerousdan/DiMaria/build-status/master)
[![Code Climate](https://codeclimate.com/github/dangerousdan/DiMaria/badges/gpa.svg)](https://codeclimate.com/github/dangerousdan/DiMaria)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a0051470-aecd-45f2-ae62-04f1dd4d517e/mini.png)](https://insight.sensiolabs.com/projects/a0051470-aecd-45f2-ae62-04f1dd4d517e)

DiMaria is a Dependency Injection Container for PHP 7 with no dependencies. It's written to be extremely fast and lightweight.

## Installation
Fetch DiMaria via composer.

## Usage
DiMaria should work out of the box. Just call `get()` with the class name you wish to create.
```
$di = new DD\DiMaria;
$object = $di->get('ClassName');
```

### Automatic injection of Type Hinted Classes
If constructor parameters are type-hinted to classes, DiMaria will automatically fetch these dependencies and pass them to the constructor, resolving all dependencies down the chain.
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
Rules for parameter values can be set with `setParams()`. Any parameters without rules will fall back to the default value if set. `setParams()` takes two arguments: the class name, and an array of parameter names and their corresponding values.
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

### Overriding Parameters
Parameters can also be set during object creation. These will override any existing parameters. This is done by passing an array of parameter names and their corresponding values as a second argument, similar to how we set rules in `setParams`. In the example below, `$object` will be created with the values 'writeAccess', 'abcd' and 'localhost'.
```
$di = new DD\DiMaria;
$di->setParams('DbStorage', [
    'username' => 'readAccess',
    'password' => 'abcd'
]);
$object = $di->get('DbStorage', [
    'username' => 'writeAccess'
]);
```

### Defining Classes in config
To pass an instance of a class as a parameter (or override a class if it is typehinted), we need to set the parameter to `['instanceOf' => 'className']`.
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
Any rules passed to `get()` during object creation still have the last say.

### Preferences
Use `setPreference()` to set a preferred implementation of an interface or class.
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
$di->setPreference('StorageInterface', 'DbStorage');
$repository = $di->get('Repository');
```
A preference can then easily be overridden for particular classes like this:

```
$di->setParams('Repository', [
    'storage' => ['instanceOf' => 'AnotherStorage'],
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
Setter injection rules are appended instead of overwritten. This makes it possible to call the same setter multiple times.

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
$di->setAlias(My\StorageInterface::class, My\Storage\DbStorage::class);
```

Depending on your IDE, this means that you should be able to see [PHPDoc](http://www.phpdoc.org/) information and/or use IDE functions to [jump to the class declaration](https://www.jetbrains.com/phpstorm/help/navigating-to-declaration-or-type-declaration-of-a-symbol.html).

### Speed
This is considerably faster than [Zend/Di](https://github.com/zendframework/zend-di) (both runtime and compiled definitions) in tests.

![DiMaria](http://news.bbcimg.co.uk/media/images/75979000/jpg/_75979820_goalcelebs.jpg)
