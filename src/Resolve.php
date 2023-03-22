<?php

namespace Nimbly\Resolve;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionParameter;

trait Resolve
{
	/**
	 * Get the reflection parameters for a callable.
	 *
	 * @param callable $callable
	 * @throws ParameterResolutionException
	 * @throws ReflectionException
	 * @return array<ReflectionParameter>
	 */
	protected function getReflectionParametersForCallable(callable $callable): array
	{
		if( \is_array($callable) ){
			[$class, $method] = $callable;

			$reflectionClass = new ReflectionClass($class);
			$reflector = $reflectionClass->getMethod($method);
		}
		elseif( \is_object($callable) && \method_exists($callable, "__invoke")) {
			$reflectionObject = new ReflectionObject($callable);
			$reflector = $reflectionObject->getMethod("__invoke");
		}

		elseif( \is_string($callable)) {
			$reflector = new ReflectionFunction($callable);
		}

		else {
			throw new ParameterResolutionException("Unknown callable type.");
		}

		return $reflector->getParameters();
	}

	/**
	 * Resolve an array of reflection parameters into an array of concrete instances/values.
	 *
	 * This will resolve dependencies from:
	 * 		o The ContainerInterface instance (if any)
	 * 		o The $parameters array
	 * 		o Try to recursively make() new instances
	 * 		o Default values provided by method/function signature
	 *
	 * @param array<ReflectionParameter> $reflection_parameters The parameters from the method/function/callable signature.
	 * @param ContainerInterface|null $container ContainerInterface instance.
	 * @param array<string,mixed> $parameters Additional named parameters and values to use during dependency resolution.
	 * @throws ParameterResolutionException
	 * @throws ClassResolutionException
	 * @return array<mixed> All resolved parameters in the order they appeared in $reflection_parameters
	 */
	protected function resolveReflectionParameters(array $reflection_parameters, ?ContainerInterface $container = null, array $parameters = []): array
	{
		return \array_map(
			/**
			 * @return mixed
			 */
			function(ReflectionParameter $reflectionParameter) use ($container, $parameters) {

				$reflectionParameterType = $reflectionParameter->getType();

				if( $reflectionParameterType instanceof \ReflectionIntersectionType ){
					throw new ParameterResolutionException("Cannot resolve intersection types.");
				}

				elseif( $reflectionParameterType instanceof \ReflectionUnionType ){
					return $this->resolveReflectionUnionType(
						$reflectionParameter,
						$reflectionParameterType,
						$container,
						$parameters
					);
				}

				elseif( empty($reflectionParameterType) || $reflectionParameterType instanceof \ReflectionNamedType ){
					return $this->resolveReflectionNamedType(
						$reflectionParameter,
						$reflectionParameterType,
						$container,
						$parameters
					);
				}

				throw new ParameterResolutionException(
					\sprintf(
						"Unknown or supported parameter type %s",
						$reflectionParameterType::class
					)
				);
			},
			$reflection_parameters
		);
	}

	/**
	 * Try an exact match against user parameters and container.
	 *
	 * @param string $parameter_name
	 * @param ReflectionNamedType|null $reflectionNamedType
	 * @param ContainerInterface|null $container
	 * @param array $parameters
	 * @return mixed
	 */
	private function tryExactMatch(
		string $parameter_name,
		?ReflectionNamedType $reflectionNamedType = null,
		?ContainerInterface $container = null,
		array $parameters = []): mixed
	{
		// If there is no type hint or the type is a built-in (string, int, etc) then try and match against name in the container.
		if( empty($reflectionNamedType) || $reflectionNamedType->isBuiltin() ) {

			// Try an exact match by name against the user parameters.
			if( \array_key_exists($parameter_name, $parameters) ){
				return $parameters[$parameter_name];
			}

			// Check the container
			if( $container && $container->has($parameter_name)) {
				return $container->get($parameter_name);
			}
		}

		// A type hint was provided that is not a built-in
		else {

			// Try an exact match by name and type against the user parameters.
			if( \array_key_exists($parameter_name, $parameters) &&
				$reflectionNamedType->getName() === $parameters[$parameter_name]::class ){
				return $parameters[$parameter_name];
			}

			// Try matching by just type
			$match = \array_filter(
				$parameters,
				function(mixed $parameter) use ($reflectionNamedType): bool {
					$parameter_type_name = $reflectionNamedType->getName();
					return $parameter instanceof $parameter_type_name;
				}
			);

			if( $match ){
				return $match[\array_keys($match)[0]];
			}

			// Try the container by type
			if( $container && $container->has($reflectionNamedType->getName()) ){
				return $container->get($reflectionNamedType->getName());
			}
		}

		throw new ParameterResolutionException(
			\sprintf(
				"Cannot resolve parameter \"%s\".",
				$parameter_name
			)
		);
	}

	/**
	 * Try default value matches.
	 *
	 * @param ReflectionParameter $reflectionParameter
	 * @return mixed
	 */
	private function tryDefaultValueMatch(ReflectionParameter $reflectionParameter): mixed
	{
		if( $reflectionParameter->isDefaultValueAvailable() ){
			return $reflectionParameter->getDefaultValue();
		}

		elseif( $reflectionParameter->allowsNull() ){
			return null;
		}

		throw new ParameterResolutionException(
			\sprintf(
				"Cannot resolve parameter \"%s\".",
				$reflectionParameter->getName()
			)
		);
	}

	/**
	 * Resolve a single type.
	 *
	 * @param ReflectionParameter $reflectionParameter
	 * @param ReflectionNamedType|null $reflectionNamedType
	 * @param ContainerInterface|null $container
	 * @param array<array-key,mixed> $parameters
	 * @return mixed
	 */
	private function resolveReflectionNamedType(
		ReflectionParameter $reflectionParameter,
		?ReflectionNamedType $reflectionNamedType = null,
		?ContainerInterface $container = null,
		array $parameters = []): mixed
	{
		try {

			return $this->tryExactMatch(
				$reflectionParameter->getName(),
				$reflectionNamedType,
				$container,
				$parameters
			);
		}
		catch( ParameterResolutionException ){}

		if( $reflectionNamedType && !$reflectionNamedType->isBuiltin() ){
			try {

				return $this->make(
					$reflectionNamedType->getName(),
					$container,
					$parameters
				);
			}
			catch( ParameterResolutionException|ClassResolutionException ){}
		}

		return $this->tryDefaultValueMatch(
			$reflectionParameter
		);
	}

	/**
	 * Resolve a union type.
	 *
	 * @param ReflectionParameter $reflectionParameter
	 * @param \ReflectionUnionType|null $reflectionUnionType
	 * @param ContainerInterface|null $container
	 * @param array $parameters
	 * @return mixed
	 */
	private function resolveReflectionUnionType(
		ReflectionParameter $reflectionParameter,
		\ReflectionUnionType $reflectionUnionType = null,
		?ContainerInterface $container = null,
		array $parameters = []): mixed
	{
		foreach( $reflectionUnionType->getTypes() as $reflectionNamedType ){
			try {

				return $this->tryExactMatch(
					$reflectionParameter->getName(),
					$reflectionNamedType,
					$container,
					$parameters
				);
			}
			catch( ParameterResolutionException ){}
		}

		foreach( $reflectionUnionType->getTypes() as $reflectionNamedType ){
			if( !$reflectionNamedType->isBuiltin() ){
				try {

					return $this->make(
						$reflectionNamedType->getName(),
						$container,
						$parameters
					);
				}
				catch( ParameterResolutionException|ClassResolutionException ){}
			}
		}

		foreach( $reflectionUnionType->getTypes() as $reflectionNamedType ){

			try {

				return $this->tryDefaultValueMatch(
					$reflectionParameter,
				);
			}
			catch( ParameterResolutionException ){}
		}

		throw new ParameterResolutionException(
			\sprintf(
				"Cannot resolve parameter \"%s\".",
				$reflectionParameter->getName()
			)
		);
	}

	/**
	 * Try to make a thing callable.
	 *
	 * You can pass something that PHP considers "callable" OR a string that represents
	 * a callable in the format: \Fully\Qualiafied\Namespace@methodName where method name could be an
	 * instance or static method OR an invokable class name.
	 *
	 * @param string|callable $thing
	 * @param ContainerInterface|null $container
	 * @param array<string,mixed> $parameters
	 * @throws ParameterResolutionException
	 * @throws ClassResolutionException
	 * @throws CallableResolutionException
	 * @return callable
	 */
	protected function makeCallable(string|callable $thing, ?ContainerInterface $container = null, array $parameters = []): callable
	{
		if( \is_callable($thing) ){
			return $thing;
		}

		$callable_thing = $thing;

		if( \class_exists($thing) ){
			$callable_thing = $this->make($thing, $container, $parameters);
		}

		elseif( \preg_match("/^(.+)@(.+)$/", $thing, $match) ){
			if( \class_exists($match[1]) ){
				$callable_thing = [$this->make($match[1], $container, $parameters), $match[2]];
			}
		}

		if( !\is_callable($callable_thing) ){
			throw new CallableResolutionException(
				\sprintf(
					"Cannot make %s callable.",
					$thing
				)
			);
		}

		return $callable_thing;
	}

	/**
	 * Call a callable with values from the container (if any) and optional given parameters.
	 *
	 * @param callable $callable
	 * @param array<string,mixed> $parameters Additional named parameters and values to use during dependency resolution.
	 * @throws ParameterResolutionException
	 * @return mixed
	 */
	protected function call(callable $callable, ?ContainerInterface $container = null, array $parameters = [])
	{
		$args = $this->resolveReflectionParameters(
			$this->getReflectionParametersForCallable($callable),
			$container,
			$parameters
		);

		return \call_user_func_array($callable, $args);
	}

	/**
	 * Make an instance of a class using autowiring with values from the container.
	 *
	 * @param string $class_name Fully qualified name of class to make.
	 * @param ContainerInterface|null $container Container instance to be used in dependency resolution.
	 * @param array<string,mixed> $parameters Additional named parameters and values to use during dependency resolution.
	 * @throws ParameterResolutionException
	 * @throws ClassResolutionException
	 * @return object
	 */
	protected function make(string $class_name, ?ContainerInterface $container = null, array $parameters = []): object
	{
		if( $container && $container->has($class_name) ){
			return $container->get($class_name);
		}

		try {

			/**
		 	* @psalm-suppress ArgumentTypeCoercion
		 	*/
			$reflectionClass = new ReflectionClass($class_name);
		}
		catch( ReflectionException $reflectionException ){
			throw new ClassResolutionException(
				"Failed to get reflection on \"" . $class_name . "\".",
				0,
				$reflectionException
			);
		}

		if( $reflectionClass->isInterface() || $reflectionClass->isAbstract() ){
			throw new ClassResolutionException("Cannot make \"" . $class_name . "\" as it is either an Interface or Abstract.");
		}

		$constructor = $reflectionClass->getConstructor();

		if( empty($constructor) ){
			return $reflectionClass->newInstance();
		}

		$resolved_arguments = $this->resolveReflectionParameters(
			$constructor->getParameters(),
			$container,
			$parameters
		);

		return $reflectionClass->newInstanceArgs($resolved_arguments);
	}
}