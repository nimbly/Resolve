<?php

namespace Nimbly\Resolve;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
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

				$parameter_name = $reflectionParameter->getName();

				// Check user arguments for a match by name.
				if( \array_key_exists($parameter_name, $parameters) ){
					return $parameters[$parameter_name];
				}

				$parameter_type = $reflectionParameter->getType();

				if( $parameter_type instanceof \ReflectionNamedType === false ) {
					throw new ParameterResolutionException("Cannot resolve union or intersection types");
				}

				/**
				 * Check container and parameters for a match by type.
				 */
				if( !$parameter_type->isBuiltin() ) {

					if( $container && $container->has($parameter_type->getName()) ){
						return $container->get($parameter_type->getName());
					}

					// Try to find in the parameters supplied
					$match = \array_filter(
						$parameters,
						function($parameter) use ($parameter_type): bool {
							$parameter_type_name = $parameter_type->getName();
							return $parameter instanceof $parameter_type_name;
						}
					);

					if( $match ){
						return $match[
							\array_keys($match)[0]
						];
					}

					try {

						return $this->make(
							$parameter_type->getName(),
							$container,
							$parameters
						);
					}
					catch( \Exception $exception ){}
				}

				/**
				 * If a default value is defined, use that, including a null value.
				 */
				if( $reflectionParameter->isDefaultValueAvailable() ){
					return $reflectionParameter->getDefaultValue();
				}
				elseif( $reflectionParameter->allowsNull() ){
					return null;
				}

				if( !empty($exception) ){
					throw $exception;
				}

				throw new ParameterResolutionException("Cannot resolve parameter \"{$parameter_name}\".");
			},
			$reflection_parameters
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