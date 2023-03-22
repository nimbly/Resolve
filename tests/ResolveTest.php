<?php

use Carton\Container;
use Nimbly\Resolve\CallableResolutionException;
use Nimbly\Resolve\ClassResolutionException;
use Nimbly\Resolve\ParameterResolutionException;
use Nimbly\Resolve\Resolve;
use Nimbly\Resolve\Tests\Fixtures\ConstructorClass;
use Nimbly\Resolve\Tests\Fixtures\InvokableClass;
use Nimbly\Resolve\Tests\Fixtures\NonConstructorClass;
use Nimbly\Resolve\Tests\Fixtures\StaticMethodClass;
use Nimbly\Resolve\Tests\Fixtures\TestAbstract;
use Nimbly\Resolve\Tests\Fixtures\TestInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers Nimbly\Resolve\Resolve
 */
class ResolveTest extends TestCase
{
	public function test_get_reflection_parameters_for_callable_on_array(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("getReflectionParametersForCallable");
		$reflectionMethod->setAccessible(true);

		$parameters = $reflectionMethod->invoke($resolve, [new NonConstructorClass, "getEvent"]);

		$this->assertCount(2, $parameters);

		$this->assertEquals(
			"name",
			$parameters[0]->getName()
		);

		$this->assertEquals(
			"string",
			$parameters[0]->getType()->getName()
		);

		$this->assertEquals(
			"start_at",
			$parameters[1]->getName()
		);

		$this->assertEquals(
			"DateTime",
			$parameters[1]->getType()->getName()
		);
	}

	public function test_get_reflection_parameters_for_callable_on_invokeable(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("getReflectionParametersForCallable");
		$reflectionMethod->setAccessible(true);

		$parameters = $reflectionMethod->invoke($resolve, new InvokableClass);

		$this->assertCount(2, $parameters);

		$this->assertEquals(
			"name",
			$parameters[0]->getName()
		);

		$this->assertEquals(
			"string",
			$parameters[0]->getType()->getName()
		);

		$this->assertEquals(
			"start_at",
			$parameters[1]->getName()
		);

		$this->assertEquals(
			"DateTime",
			$parameters[1]->getType()->getName()
		);
	}

	public function test_get_reflection_parameters_for_callable_on_function(): void
	{
		function getEvent(string $name, DateTime $start_at) {
			return [
				"name" => $name,
				"start_at" => $start_at
			];
		};

		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("getReflectionParametersForCallable");
		$reflectionMethod->setAccessible(true);

		$parameters = $reflectionMethod->invoke($resolve, "getEvent");

		$this->assertCount(2, $parameters);

		$this->assertEquals(
			"name",
			$parameters[0]->getName()
		);

		$this->assertEquals(
			"string",
			$parameters[0]->getType()->getName()
		);

		$this->assertEquals(
			"start_at",
			$parameters[1]->getName()
		);

		$this->assertEquals(
			"DateTime",
			$parameters[1]->getType()->getName()
		);
	}

	public function test_call_on_array_callable(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("call");
		$reflectionMethod->setAccessible(true);
		$event = $reflectionMethod->invoke(
			$resolve,
			[new NonConstructorClass, "getEvent"],
			new Container,
			["name" => "My Event", "start_at" => new DateTime("Jan 1, 2020")]
		);

		$this->assertEquals(
			[
				"name" => "My Event",
				"start_at" => new DateTime("Jan 1, 2020")
			],
			$event
		);
	}

	public function test_call_on_invokable_class(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("call");
		$reflectionMethod->setAccessible(true);
		$event = $reflectionMethod->invoke(
			$resolve,
			new InvokableClass,
			new Container,
			["name" => "My Event", "start_at" => new DateTime("Jan 1, 2020")]
		);

		$this->assertEquals(
			[
				"name" => "My Event",
				"start_at" => new DateTime("Jan 1, 2020")
			],
			$event
		);
	}

	public function test_call_on_static_method(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("call");
		$reflectionMethod->setAccessible(true);
		$event = $reflectionMethod->invoke(
			$resolve,
			[StaticMethodClass::class, "getEvent"],
			new Container,
			["name" => "My Event", "start_at" => new DateTime("Jan 1, 2020")]
		);

		$this->assertEquals(
			[
				"name" => "My Event",
				"start_at" => new DateTime("Jan 1, 2020")
			],
			$event
		);
	}

	public function test_call_on_function(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("call");
		$reflectionMethod->setAccessible(true);
		$value = $reflectionMethod->invoke(
			$resolve,
			"strtolower",
			new Container,
			[
				"string" => "RESOLVE" // PHP >= 8.0 support
			]
		);

		$this->assertEquals(
			"resolve",
			$value
		);
	}

	public function test_make_callable_invokable(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("makeCallable");
		$reflectionMethod->setAccessible(true);
		$callable = $reflectionMethod->invoke(
			$resolve,
			InvokableClass::class
		);

		$this->assertIsCallable($callable);
	}

	public function test_make_callable_instance_method(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("makeCallable");
		$reflectionMethod->setAccessible(true);
		$callable = $reflectionMethod->invoke(
			$resolve,
			NonConstructorClass::class . "@getEvent"
		);

		$this->assertIsCallable($callable);
	}

	public function test_non_callable_string_throws_callable_resolution_exception(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("makeCallable");
		$reflectionMethod->setAccessible(true);

		$this->expectException(CallableResolutionException::class);
		$callable = $reflectionMethod->invoke(
			$resolve,
			NonConstructorClass::class . "@notAMethod"
		);
	}

	public function test_callable_passes_through(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$input = [new NonConstructorClass, "getEvent"];

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("makeCallable");
		$reflectionMethod->setAccessible(true);
		$callable = $reflectionMethod->invoke(
			$resolve,
			$input
		);

		$this->assertIsCallable($callable);
		$this->assertSame($input, $callable);
	}

	public function test_make_checks_container_for_match(): void
	{
		$instance = new ConstructorClass("Foo", new DateTime);

		$container = new Container;
		$container->set(
			ConstructorClass::class,
			$instance
		);

		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("make");
		$reflectionMethod->setAccessible(true);
		$resolved_instance = $reflectionMethod->invoke(
			$resolve,
			ConstructorClass::class,
			$container
		);

		$this->assertSame(
			$instance,
			$resolved_instance
		);
	}

	public function test_make_with_interface_throws_class_resolution_exception(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("make");
		$reflectionMethod->setAccessible(true);

		$this->expectException(ClassResolutionException::class);
		$instance = $reflectionMethod->invoke(
			$resolve,
			TestInterface::class
		);
	}

	public function test_make_with_abstract_throws_class_resolution_exception(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("make");
		$reflectionMethod->setAccessible(true);

		$this->expectException(ClassResolutionException::class);
		$instance = $reflectionMethod->invoke(
			$resolve,
			TestAbstract::class
		);
	}

	public function test_make_with_no_constructor(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("make");
		$reflectionMethod->setAccessible(true);
		$instance = $reflectionMethod->invoke(
			$resolve,
			NonConstructorClass::class
		);

		$this->assertInstanceOf(
			NonConstructorClass::class,
			$instance
		);
	}

	public function test_make_with_constructor(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("make");
		$reflectionMethod->setAccessible(true);
		$instance = $reflectionMethod->invoke(
			$resolve,
			ConstructorClass::class,
			new Container,
			[
				"name" => "Foo",
				"start_at" => new DateTime
			]
		);

		$this->assertInstanceOf(
			ConstructorClass::class,
			$instance
		);
	}

	public function test_make_on_non_existent_class_throws_class_resolution_exception(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("make");
		$reflectionMethod->setAccessible(true);

		$this->expectException(ClassResolutionException::class);
		$instance = $reflectionMethod->invoke(
			$resolve,
			"NonExistentClass"
		);
	}


	public function test_resolve_reflection_parameters_with_primitive_in_user_args(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("resolveReflectionParameters");
		$reflectionMethod->setAccessible(true);

		$callable = function(string $firstname, string $lastname): void {
			echo "{$firstname} {$lastname}";
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$dependencies = $reflectionMethod->invokeArgs($resolve, [$reflectionFunction->getParameters(), null, ["firstname" => "Nimbly", "lastname" => "Limber"]]);

		$this->assertEquals(
			["Nimbly", "Limber"],
			$dependencies
		);
	}

	public function test_resolve_reflection_parameters_with_primitive_using_default_value(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("resolveReflectionParameters");
		$reflectionMethod->setAccessible(true);

		$callable = function(string $firstname, string $lastname = "Limber"): void {
			echo "{$firstname} {$lastname}";
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$dependencies = $reflectionMethod->invokeArgs($resolve, [$reflectionFunction->getParameters(), null, ["firstname" => "Nimbly"]]);

		$this->assertEquals(
			["Nimbly", "Limber"],
			$dependencies
		);
	}

	public function test_resolve_reflection_parameters_with_primitive_using_optional_or_allows_null(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("resolveReflectionParameters");
		$reflectionMethod->setAccessible(true);

		$callable = function(string $firstname, ?string $lastname): void {
			echo "{$firstname} {$lastname}";
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$dependencies = $reflectionMethod->invokeArgs($resolve, [$reflectionFunction->getParameters(), null, ["firstname" => "Nimbly"]]);

		$this->assertEquals(
			["Nimbly", null],
			$dependencies
		);
	}

	public function test_resolve_reflection_parameters_with_class_using_container(): void
	{
		$container = new Container;
		$container->set(
			ConstructorClass::class,
			new ConstructorClass("Nimbly", new DateTime)
		);

		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("resolveReflectionParameters");
		$reflectionMethod->setAccessible(true);

		$callable = function(ConstructorClass $application): bool {
			return true;
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$dependencies = $reflectionMethod->invokeArgs($resolve, [$reflectionFunction->getParameters(), $container]);

		$this->assertEquals(
			[$container->get(ConstructorClass::class)],
			$dependencies
		);
	}

	public function test_resolve_reflection_parameters_with_making_class_with_constructor(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("resolveReflectionParameters");
		$reflectionMethod->setAccessible(true);

		$callable = function(ConstructorClass $class): void {
			echo $class->getEvent();
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$dependencies = $reflectionMethod->invokeArgs(
			$resolve,
			[
				$reflectionFunction->getParameters(),
				null,
				[
					"name" => ":name:",
					"date" => new DateTime
				]
			]
		);

		$this->assertInstanceOf(
			ConstructorClass::class,
			$dependencies[0]
		);
	}

	public function test_resolve_reflection_parameters_with_union_type(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("resolveReflectionParameters");
		$reflectionMethod->setAccessible(true);

		$callable = function(DateTime|DateTimeImmutable $dateTime): string {
			return "The date is now: " . $dateTime->format("c");
		};

		$dateTime = new DateTimeImmutable("2020-01-28T12:00:01-08:00");

		$reflectionFunction = new ReflectionFunction($callable);
		$result = $reflectionMethod->invoke(
			$resolve,
			$reflectionFunction->getParameters(),
			null,
			["dateTime" => $dateTime]
		);

		$this->assertSame(
			$dateTime,
			$result[0]
		);
	}

	public function test_resolve_reflection_parameters_with_unmakeable_throws_parameter_resolution_exception(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("resolveReflectionParameters");
		$reflectionMethod->setAccessible(true);

		$callable = function(ConstructorClass $request): void {
			echo "Hello world!";
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$this->expectException(ParameterResolutionException::class);
		$reflectionMethod->invokeArgs($resolve, [$reflectionFunction->getParameters()]);
	}

	public function test_resolve_reflection_parameters_with_unresolvable_throws_parameter_resolution_exception(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("resolveReflectionParameters");
		$reflectionMethod->setAccessible(true);

		$callable = function(string $dateTime): void {
			echo "The date is now: " . $dateTime;
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$this->expectException(ParameterResolutionException::class);
		$reflectionMethod->invokeArgs($resolve, [$reflectionFunction->getParameters()]);
	}

	public function test_resolve_reflection_parameters_with_default_values(): void
	{
		$resolve = new class {
			use Resolve;
		};

		$reflectionClass = new ReflectionClass($resolve);
		$reflectionMethod = $reflectionClass->getMethod("resolveReflectionParameters");
		$reflectionMethod->setAccessible(true);

		$callable = function(string $option = "opt1", ?string $option2 = null): void {
			echo "Hello world with " . $option;
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$parameters = $reflectionMethod->invokeArgs($resolve, [$reflectionFunction->getParameters()]);

		$this->assertEquals(
			["opt1", null],
			$parameters
		);
	}
}