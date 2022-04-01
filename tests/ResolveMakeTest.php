<?php

use Carton\Container;
use PHPUnit\Framework\TestCase;
use Nimbly\Resolve\Resolve;

/**
 * @covers Nimbly\Resolve\Resolve
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