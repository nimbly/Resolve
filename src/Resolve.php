<?php

namespace Resolve;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionObject;
use ReflectionParameter;

class Resolve
{
	/**
	 * ContainerInterface instance.
	 *
	 * @var ContainerInterface|null
	 */
	protected $container;

	/**
	 * Resolve constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct(ContainerInterface $container = null)
	{
		$this->container = $container;
	}

	/**
	 * Set the ContainerInterface instance.
	 *
	 * @param ContainerInterface $container
	 * @return void
	 */
	public function setContainer(ContainerInterface $container): void
	{
		$this->container = $container;
	}

	/**
	 * Call a callable with optional given parameters.
	 *
	 * @param callable $callable
	 * @param array<string,mixed> $parameters
	 * @throws CallableResolutionException
	 * @return mixed
	 */
	public function call(callable $callable, array $parameters = [])
	{
		return \call_user_func_array(
			$callable,
			$this->getCallableArguments($callable, $parameters)
		);
	}

	/**
	 * Make an instance of the given class.
	 *
	 * Parameters is a key => value pair array of parameters that will be used during
	 * dependency resolution.
	 *
	 * @param string $class_name
	 * @param array<string,mixed> $parameters Additional parameters to supply to the constructor.
	 * @throws ClassResolutionException
	 * @return object
	 */
	public function make(string $class_name, array $parameters = []): object
	{
		if( !\class_exists($class_name) ){
			throw new ClassResolutionException("Class \"{$class_name}\" does not exist.");
		}

		// Do some reflection to determine constructor parameters
		/** @psalm-suppress ArgumentTypeCoercion */
		$reflectionClass = new ReflectionClass($class_name);

		$constructor = $reflectionClass->getConstructor();

		if( empty($constructor) ){
			return $reflectionClass->newInstance();
		}

		$args = $this->resolveReflectionParameters(
			$constructor->getParameters(),
			$parameters
		);

		return $reflectionClass->newInstanceArgs($args);
	}

	/**
	 * Given a callable, get its arguments resolved using the container and optionally any
	 * user supplied parameters.
	 *
	 * @param callable $callable
	 * @param array $parameters
	 * @return array<mixed>
	 */
	public function getCallableArguments(callable $callable, array $parameters = []): array
	{
		if( \is_array($callable) ){
			[$class, $method] = $callable;

			/** @psalm-suppress ArgumentTypeCoercion */
			$reflectionClass = new ReflectionClass($class);
			$reflectionMethod = $reflectionClass->getMethod($method);
			$reflectionParameters = $reflectionMethod->getParameters();
		}

		elseif( \is_object($callable) && \method_exists($callable, "__invoke")) {

			$reflectionObject = new ReflectionObject($callable);
			$reflectionMethod = $reflectionObject->getMethod("__invoke");
			$reflectionParameters = $reflectionMethod->getParameters();
		}

		elseif( \is_string($callable)) {
			$reflectionFunction = new ReflectionFunction($callable);
			$reflectionParameters = $reflectionFunction->getParameters();
		}

		else {
			throw new CallableResolutionException("Resolve does not have support for this type of callable.");
		}

		return $this->resolveReflectionParameters(
			$reflectionParameters,
			$parameters
		);
	}

	/**
	 * Try to make something callable.
	 *
	 * Supports:
	 *  - Full\Qualified\Namespace\Classname@Method
	 *  - Full\Qualified\Namespace\Classname (if class has __invoke() method.)
	 *
	 * @param string|callable $callable
	 * @return callable
	 */
	public function makeCallable($callable): callable
	{
		if( \is_callable($callable) ){
			return $callable;
		}

		if( \preg_match("/^(.+)@(.+)$/", $callable, $match) ){
			return [
				$this->make($match[1]),
				$match[2]
			];
		}

		if( \is_string($callable) &&
			\class_exists($callable) ){
			$invokable = $this->make($callable);

			if( \is_callable($invokable) ){
				return $invokable;
			}
		}

		throw new CallableResolutionException("Resolve does not have support for this type of callable.");
	}

	/**
	 * Resolve parameters.
	 *
	 * @param array<ReflectionParameter> $reflectionParameters
	 * @param array<string,mixed> $parameters
	 * @throws ParameterResolutionException
	 * @return array<mixed>
	 */
	protected function resolveReflectionParameters(array $reflectionParameters, array $parameters = []): array
	{
		return \array_map(
			/**
			 * @return mixed
			 */
			function(ReflectionParameter $reflectionParameter) use ($parameters) {

				$parameterName = $reflectionParameter->getName();
				$parameterType = $reflectionParameter->getType();

				// Check parameters for a match by name.
				if( \array_key_exists($parameterName, $parameters) ){
					return $parameters[$parameterName];
				}

				// Check container and parameters for a match by type.
				if( $parameterType && !$parameterType->isBuiltin() ) {

					if( $this->container &&
						$this->container->has($parameterType->getName()) ){
						return $this->container->get($parameterType->getName());
					}

					// Try to find in the parameters supplied
					$match = \array_filter(
						$parameters,
						function($parameter) use ($parameterType) {
							$parameter_type_name = $parameterType->getName();
							return $parameter instanceof $parameter_type_name;
						}
					);

					if( $match ){
						return $match[
							\array_keys($match)[0]
						];
					}

					/**
					 * @psalm-suppress ArgumentTypeCoercion
					 */
					return $this->make($parameterType->getName(), $parameters);
				}

				// No type or the type is a primitive (built in)
				if( empty($parameterType) || $parameterType->isBuiltin() ){

					// Does parameter offer a default value?
					if( $reflectionParameter->isDefaultValueAvailable() ){
						return $reflectionParameter->getDefaultValue();
					}

					elseif( $reflectionParameter->allowsNull() ){
						return null;
					}
				}

				throw new ParameterResolutionException("Cannot resolve parameter \"{$parameterName}\".");
			},
			$reflectionParameters
		);
	}
}