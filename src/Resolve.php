<?php

namespace Nimbly\Resolve;

use Nimbly\Resolve\ClassResolutionException;
use Nimbly\Resolve\ParameterResolutionException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionObject;
use ReflectionParameter;

class Resolve
{
	public function __construct(
		protected ?ContainerInterface $container = null
	)
	{
	}

	/**
	 * Get the reflection parameters for a callable.
	 *
	 * @param callable $callable
	 * @throws ParameterResolutionException
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
			throw new ParameterResolutionException("Given callable is not callable.");
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
	 * @param array<string,mixed> $parameters Additional named parameters and values to use during dependency resolution.
	 * @throws ParameterResolutionException
	 * @throws ClassResolutionException
	 * @return array<mixed> All resolved parameters in the order they appeared in $reflection_parameters
	 */
	protected function resolveReflectionParameters(array $reflection_parameters, array $parameters = []): array
	{
		return \array_map(
			/**
			 * @return mixed
			 */
			function(ReflectionParameter $reflectionParameter) use ($parameters) {

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

					if( $this->container && $this->container->has($parameter_type->getName()) ){
						return $this->container->get($parameter_type->getName());
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
	 * instance or static method.
	 *
	 * @param string|callable $thing
	 * @throws ParameterResolutionException
	 * @throws ClassResolutionException
	 * @throws CallableResolutionException
	 * @return callable
	 */
	public function makeCallable(string|callable $thing): callable
	{
		if( \is_string($thing) ){

			if( \class_exists($thing) ){
				$thing = $this->make($thing);
			}

			elseif( \preg_match("/^(.+)@(.+)$/", $thing, $match) ){
				if( \class_exists($match[1]) ){
					$thing = [$this->make($match[1]), $match[2]];
				}
			}
		}

		if( !\is_callable($thing) ){
			throw new CallableResolutionException("Cannot make callable");
		}

		return $thing;
	}

	/**
	 * Call a callable with optional given parameters.
	 *
	 * @param callable $callable
	 * @param array<string,mixed> $parameters Additional named parameters and values to use during dependency resolution.
	 * @return mixed
	 */
	public function call(callable $callable, array $parameters = [])
	{
		$args = $this->resolveReflectionParameters(
			$this->getReflectionParametersForCallable($callable),
			$parameters
		);

		return \call_user_func_array($callable, $args);
	}

	/**
	 * Make an instance of a class using autowiring with values from the container.
	 *
	 * @param string $class_name Fully qualified namespace of class to make.
	 * @param array<string,mixed> $parameters Additional named parameters and values to use during dependency resolution.
	 * @throws ParameterResolutionException
	 * @throws ClassResolutionException
	 * @return object
	 */
	public function make(string $class_name, array $parameters = []): object
	{
		if( $this->container &&
			$this->container->has($class_name) ){
			return $this->container->get($class_name);
		}

		try {

			/**
		 	* @psalm-suppress ArgumentTypeCoercion
		 	*/
			$reflectionClass = new ReflectionClass($class_name);
		}
		catch( ReflectionException $reflectionException ){
			throw new ClassResolutionException(
				$reflectionException->getMessage(),
				$reflectionException->getCode(),
				$reflectionException
			);
		}

		if( $reflectionClass->isInterface() || $reflectionClass->isAbstract() ){
			throw new ClassResolutionException("Cannot make an instance of an Interface or Abstract.");
		}

		$constructor = $reflectionClass->getConstructor();

		if( empty($constructor) ){
			return $reflectionClass->newInstance();
		}

		$resolved_arguments = $this->resolveReflectionParameters(
			$constructor->getParameters(),
			$parameters
		);

		return $reflectionClass->newInstanceArgs($resolved_arguments);
	}
}