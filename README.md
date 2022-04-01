# Resolve

Resolve is a dependency resolver able to call functions or methods or make new instances of classes with the aid of a PSR-11 compliant container.

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

Instantiate Resolve with or without a container instance.

```php
$resolve = new Resolve($container);
```

## Make

The `make` method can instantiate any class you may need and resolve the constructor dependencies automatically from either the container instance or the optional parameters you provide.

```php
$instance = $resolve->make(FooHandler::class, ["additional_parameter" => "Foo"]);
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

The `call` method will call any `callable` you pass in, collect the dependencies of that callable from either the container or the optional set of parameters passed, and invoke that `callable`.

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