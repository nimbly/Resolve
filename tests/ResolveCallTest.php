<?php

use Carton\Container;
use PHPUnit\Framework\TestCase;
use Resolve\Resolve;
use Resolve\Tests\Fixtures\InvokableClass;
use Resolve\Tests\Fixtures\NonConstructorClass;
use Resolve\Tests\Fixtures\StaticMethodClass;

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
			["str" => "PLUMB"]
		);

		$this->assertEquals(
			"plumb",
			$value
		);
	}
}