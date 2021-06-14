<?php

use Carton\Container;
use PHPUnit\Framework\TestCase;
use Nimbly\Resolve\Resolve;
use Nimbly\Resolve\Tests\Fixtures\InvokableClass;
use Nimbly\Resolve\Tests\Fixtures\NonConstructorClass;
use Nimbly\Resolve\Tests\Fixtures\StaticMethodClass;

/**
 * @covers Resolve\Resolve
 */
class ResolveCallTest extends TestCase
{
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
		$plumb = new Resolve(
			new Container
		);

		$value = $plumb->call(
			"strtolower",
			[
				"str" => "PLUMB", // PHP < 8.0 support
				"string" => "PLUMB" // PHP >= 8.0 support
			]
		);

		$this->assertEquals(
			"plumb",
			$value
		);
	}
}