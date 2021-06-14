<?php

namespace Nimbly\Resolve\Tests\Fixtures;

use DateTime;

class StaticMethodClass
{
	public static function getEvent(string $name, DateTime $start_at): array
	{
		return [
			"name" => $name,
			"start_at" => $start_at
		];
	}
}