<?php

use Carton\Container;
use PHPUnit\Framework\TestCase;
use Nimbly\Resolve\Resolve;
use Nimbly\Resolve\Tests\Fixtures\InvokableClass;
use Nimbly\Resolve\Tests\Fixtures\NonConstructorClass;
use Nimbly\Resolve\Tests\Fixtures\StaticMethodClass;

/**
 * @covers Nimbly\Resolve\Resolve
 */
class ResolveTest extends TestCase
{
	public function test_get_reflection_parameters_for_callable_on_array(): void
	{
		$resolve = new Resolve;

		$parameters = $resolve->getReflectionParametersForCallable([new NonConstructorClass, "getEvent"]);

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

		$parameters = $resolve->getReflectionParametersForCallable(new InvokableClass);

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
		$parameters = $resolve->getReflectionParametersForCallable("getEvent");

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
		$plumb = new Resolve(
			new Container
		);

		$event = $plumb->call(
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
		$plumb = new Resolve(
			new Container
		);

		$event = $plumb->call(
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
		$plumb = new Resolve(
			new Container
		);

		$event = $plumb->call(
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

	public function test_make_throws_exception_if_class_not_found(): void
	{
		$plumb = new Resolve(
			new Container
		);

		$this->expectException(Exception::class);

		$plumb->make("NonExistentClass");
	}
}