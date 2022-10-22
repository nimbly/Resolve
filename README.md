[![Latest Stable Version](https://img.shields.io/packagist/v/nimbly/resolve.svg?style=flat-square)](https://packagist.org/packages/nimbly/Resolve)
[![Build Status](https://img.shields.io/travis/nimbly/Resolve.svg?style=flat-square)](https://app.travis-ci.com/nimbly/Resolve)
[![Code Coverage](https://img.shields.io/coveralls/github/nimbly/Resolve.svg?style=flat-square)](https://coveralls.io/github/nimbly/Resolve)
[![License](https://img.shields.io/github/license/nimbly/Resolve.svg?style=flat-square)](https://packagist.org/packages/nimbly/Resolve)

# Resolve

Resolve is an autowiring and dependency resolver trait able to call functions or methods or make new instances of classes with (or without) the aid of a PSR-11 compliant container.

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

Add the `Resolve` trait to anything you would like to add dependency injection capabilities to.

```php
class Dispatcher
{
    use Resolve;

    public function __construct(
        protected Router $router,
        protected ContainterInterface $container)
    {
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $route = $this->router->resolve($request);

        $handler = $this->makeCallable($route->getHandler());

        return $this->call(
            $handler,
            $this->container,
            [ServerRequestInterface::class => $request]
        );
    }
}
```

## Make

The `make` method can instantiate any class you may need and resolve the constructor dependencies automatically from both the container instance (if one was provided) and the optional parameters you provide.

```php
$instance = $this->make(
    FooHandler::class,
    $this->container,
    ["additional_parameter" => "Foo"]
);
```

## Make a thing callable

Often you would like to make something that represents a callable into an actual `callable` type. You can pass a string that represents a callable or an actual `callable` into the `makeCallable` method.


```php
// An invokable class.
$invokable = $this->makeCallable(Foo::class, $this->container);
```

You can pass in a fully qualified class namespace, an `@` symbol, and the method name. For example:

```php
// A class and method name string.
$instance_method = $this->makeCallable("\App\Http\Handlers\FooHandler@createNewFoo");
```

## Call

The `call` method will call any `callable` you pass in, resolve the dependencies of that callable from both the container and the optional set of parameters passed, and invoke that `callable`.

If a dependency cannot be resolved from the container or optional parameters, Resolve will attempt to `make` one for you automatically.

If making the dependecy fails or is not possible, an exception will be thrown.

## Callable types

### Instance method

```php
$this->call(
	[new FooHandler, "findById"],
    $this->container,
	[
		ServerRequestInteface::class => $serverRequest,
		"id" => "3122accd-e640-4c4c-b299-ccad074cb077"
	]
);
```
### Static method

```php
$this->call(
	[FooHandler::class, "findById"],
    $this->container,
	[
		ServerRequestInteface::class => $serverRequest,
		"id" => "3122accd-e640-4c4c-b299-ccad074cb077"
	]
);
```

### Invokable

```php
$this->call(
	new FooHandler,
    $this->container,
	[
		ServerRequestInteface::class => $serverRequest,
		"id" => "3122accd-e640-4c4c-b299-ccad074cb077"
	]
);
```

### Function

```php
$this->call(
	"\Handlers\Foo\findById",
    $this->container,
	[
		ServerRequestInteface::class => $serverRequest,
		"id" => "3122accd-e640-4c4c-b299-ccad074cb077"
	]
);
```