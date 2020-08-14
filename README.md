# Resolve

Resolve is a dependency resolver able to call or make new instances with the aid of a PSR-11 compliant container.

## Installation

```bash
composer require nimbly/plumb
```

## Requirements

Resolve needs a PSR-11 compliant container instance and does not come bundled with one.

You can try one of these:

* nimbly/carton
*  phpdi/di

## Usage

Instantiate Resolve with your container instance.

```php
$plumb = new Resolve($container);
```

## Call

The `call` method will call any callable you pass in, collect the dependencies of that callable from either the container or the optional set of parameters passed, and invoke that callable.

If a dependency cannot be resolved from the container or optional parameters, Resolve will attempt to `make` one for you automatically.

## Callable types

### Instance method

```php
$plumb->call(
    [new FooHandler, "findById"],
    [
        ServerRequestInteface::class => $serverRequest,
        "id" => "3122accd-e640-4c4c-b299-ccad074cb077"
    ]
);
```
### Static method

```php
$plumb->call(
    [FooHandler::class, "findById"],
    [
        ServerRequestInteface::class => $serverRequest,
        "id" => "3122accd-e640-4c4c-b299-ccad074cb077"
    ]
);
```

### Invokable

```php
$plumb->call(
    new FooHandler,
    [
        ServerRequestInteface::class => $serverRequest,
        "id" => "3122accd-e640-4c4c-b299-ccad074cb077"
    ]
);
```

### Function

```php
$plumb->call(
    "\Handlers\Foo\findById",
    [
        ServerRequestInteface::class => $serverRequest,
        "id" => "3122accd-e640-4c4c-b299-ccad074cb077"
    ]
);
```

## Make

The `make` method can instantiate any class you may need and resolve the constructor dependencies automatically from either the container instance or the optional parameters you provide.

```php
$instance = $plumb->make(FooHandler::class);
```