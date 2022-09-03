[![Latest Stable Version](https://img.shields.io/packagist/v/nimbly/resolve.svg?style=flat-square)](https://packagist.org/packages/nimbly/Resolve)
[![Build Status](https://img.shields.io/travis/nimbly/Resolve.svg?style=flat-square)](https://travis-ci.com/nimbly/Resolve)
[![Code Coverage](https://img.shields.io/coveralls/github/nimbly/Resolve.svg?style=flat-square)](https://coveralls.io/github/nimbly/Resolve)
[![License](https://img.shields.io/github/license/nimbly/Resolve.svg?style=flat-square)](https://packagist.org/packages/nimbly/Resolve)

# Resolve

Resolve is an autowiring and dependency resolver able to call functions or methods or make new instances of classes with the aid of a PSR-11 compliant container.

Use Resolve in your own project or library when you would like to leverage dependency injection decoupled from a specific `ContainerInterface` implmentation.

## Installation

```bash
composer require nimbly/resolve
```

## Requirements

* PHP >= 8.0

## Container support

Resolve can optionally be passed a PSR-11 Container instance but does not ship with an implementation.

You can try one of these:

* [php-di/php-di](https://github.com/PHP-DI/PHP-DI)
* [league/container](https://github.com/thephpleague/container)
* [aura/di](https://github.com/auraphp/Aura.Di)
* [nimbly/carton](https://github.com/nimbly/carton)

## Usage

Instantiate Resolve with or without a PSR-11 container instance.

```php
$resolve = new Resolve($container);
```

## Make

The `make` method can instantiate any class you may need and resolve the constructor dependencies automatically from both the container instance and the optional parameters you provide.

```php
$instance = $resolve->make(
    FooHandler::class,
    ["additional_parameter" => "Foo"]
);
```

## Make a thing callable

Often you would like to make something that represents a callable into an actual `callable` type. You can pass a string that represents a callable or an actual `callable` into the `makeCallable` method.

```php
$invokable = $resolve->makeCallable(Foo::class);
```

```php
$instance_method = $resolve->makeCallable("\Http\Handlers\FooHandler@createNewFoo");
```

## Call

The `call` method will call any `callable` you pass in, resolve the dependencies of that callable from both the container and the optional set of parameters passed, and invoke that `callable`.

If a dependency cannot be resolved from the container or optional parameters, Resolve will attempt to `make` one for you automatically.

## Callable types

### Instance method

```php
$resolve->call(
	[new FooHandler, "findById"],
	[
		ServerRequestInteface::class => $serverRequest,
		"id" => "3122accd-e640-4c4c-b299-ccad074cb077"
	]
);
```
### Static method

```php
$resolve->call(
	[FooHandler::class, "findById"],
	[
		ServerRequestInteface::class => $serverRequest,
		"id" => "3122accd-e640-4c4c-b299-ccad074cb077"
	]
);
```

### Invokable

```php
$resolve->call(
	new FooHandler,
	[
		ServerRequestInteface::class => $serverRequest,
		"id" => "3122accd-e640-4c4c-b299-ccad074cb077"
	]
);
```

### Function

```php
$resolve->call(
	"\Handlers\Foo\findById",
	[
		ServerRequestInteface::class => $serverRequest,
		"id" => "3122accd-e640-4c4c-b299-ccad074cb077"
	]
);
```