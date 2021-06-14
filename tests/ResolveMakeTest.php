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
class ResolveMakeTest extends TestCase
{
	public function test_make_throws_exception_if_class_not_found(): void
	{
		$plumb = new Resolve(
			new Container
		);

		$this->expectException(Exception::class);

		$plumb->make("NonExistentClass");
	}

}