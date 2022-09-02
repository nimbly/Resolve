<?php

use Carton\Container;
use Nimbly\Resolve\CallableResolutionException;
use Nimbly\Resolve\ClassResolutionException;
use PHPUnit\Framework\TestCase;
use Nimbly\Resolve\Resolve;
use Nimbly\Resolve\Tests\Fixtures\ConstructorClass;
use Nimbly\Resolve\Tests\Fixtures\InvokableClass;
use Nimbly\Resolve\Tests\Fixtures\NonConstructorClass;
use Nimbly\Resolve\Tests\Fixtures\StaticMethodClass;
use Nimbly\Resolve\Tests\Fixtures\TestAbstract;
use Nimbly\Resolve\Tests\Fixtures\TestInterface;

/**
 * @covers Nimbly\Resolve\Resolve
 */
class ResolveTest extends TestCase
{
	public function test_get_reflection_parameters_for_callable_on_array(): void
	{
		$resolve = new Resolve;
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
		$resolve = new Resolve;
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

		$resolve = new Resolve;
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
		$resolve = new Resolve(
			new Container
		);

		$event = $resolve->call(
			[new NonConstructorClass, "getEvent"],
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
		$resolve = new Resolve(
			new Container
		);

		$event = $resolve->call(
			new InvokableClass,
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
		$resolve = new Resolve(
			new Container
		);

		$event = $resolve->call(
			[StaticMethodClass::class, "getEvent"],
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
		$resolve = new Resolve(
			new Container
		);

		$value = $resolve->call(
			"strtolower",
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
		$resolve = new Resolve;

		$callable = $resolve->makeCallable(InvokableClass::class);

		$this->assertIsCallable($callable);
	}

	public function test_make_callable_instance_method(): void
	{
		$resolve = new Resolve;

		$callable = $resolve->makeCallable(NonConstructorClass::class . "@getEvent");

		$this->assertIsCallable($callable);
	}

	public function test_non_callable_string_throws_callable_resolution_exception(): void
	{
		$resolve = new Resolve;
		$this->expectException(CallableResolutionException::class);
		$callable = $resolve->makeCallable(NonConstructorClass::class . "@notAMethod");
	}

	public function test_callable_passes_through(): void
	{
		$resolve = new Resolve;

		$input = [new NonConstructorClass, "getEvent"];

		$callable = $resolve->makeCallable($input);

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

		$resolve = new Resolve($container);

		$resolved_instance = $resolve->make(ConstructorClass::class);

		$this->assertSame(
			$instance,
			$resolved_instance
		);
	}

	public function test_make_with_interface_throws_class_resolution_exception(): void
	{
		$resolve = new Resolve;

		$this->expectException(ClassResolutionException::class);

		$resolve->make(TestInterface::class);
	}

	public function test_make_with_abstract_throws_class_resolution_exception(): void
	{
		$resolve = new Resolve;

		$this->expectException(ClassResolutionException::class);

		$resolve->make(TestAbstract::class);
	}

	public function test_make_with_no_constructor(): void
	{
		$resolve = new Resolve;

		$instance = $resolve->make(NonConstructorClass::class);

		$this->assertInstanceOf(
			NonConstructorClass::class,
			$instance
		);
	}

	public function test_make_with_constructor(): void
	{
		$resolve = new Resolve;

		$instance = $resolve->make(
			ConstructorClass::class,
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
		$resolve = new Resolve;

		$this->expectException(ClassResolutionException::class);

		$resolve->make("NonExistentClass");
	}
}