# DiMaria Dependency Injector

[![Build Status](https://travis-ci.org/dangerousdan/DiMaria.svg?branch=master)](https://travis-ci.org/dangerousdan/DiMaria)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dangerousdan/DiMaria/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/dangerousdan/DiMaria/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/dangerousdan/DiMaria/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/dangerousdan/DiMaria/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/dangerousdan/DiMaria/badges/build.png?b=master)](https://scrutinizer-ci.com/g/dangerousdan/DiMaria/build-status/master)
[![Code Climate](https://codeclimate.com/github/dangerousdan/DiMaria/badges/gpa.svg)](https://codeclimate.com/github/dangerousdan/DiMaria)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a0051470-aecd-45f2-ae62-04f1dd4d517e/mini.png)](https://insight.sensiolabs.com/projects/a0051470-aecd-45f2-ae62-04f1dd4d517e)

DiMaria is a Dependency Injection Container for PHP 7 with no dependencies. It's written to be extremely fast and lightweight.

## Installation
Fetch DiMaria with composer via packagist. Add it with
```
composer require dangerousdan/dimaria
```

## Usage
DiMaria should work out of the box. Just call `get()` with the class name you wish to create.
```
$di = new DD\DiMaria;
$object = $di->get('ClassName');
```

DiMaria implements the [container-interop](https://github.com/container-interop/container-interop) interface.

DiMaria can:
* Automatically fetch type-hinted dependencies in classes
* Set and override parameters and create aliases
* Set preferences for interfaces or classes
* Configure classes to return shared or new instances
* Support variadic parameters
* Configure Setter injection

For more info, see [the docs](http://dangerousdan.github.io/DiMaria/)

### Speed
This is considerably faster than [Zend/Di](https://github.com/zendframework/zend-di) (both runtime and compiled definitions) in tests.

![DiMaria](http://news.bbcimg.co.uk/media/images/75979000/jpg/_75979820_goalcelebs.jpg)
